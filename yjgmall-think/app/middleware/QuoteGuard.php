<?php
namespace app\middleware;

use app\common\QuoteAccess;
use app\common\SlideCaptcha;
use Closure;
use think\facade\Cache;
use think\Request;
use think\Response;

/**
 * 行情 JSON 防护（仅 API，不挡 HTML/蜘蛛页面）：
 * 1) 短时 HMAC 票据
 * 2) 行为异常 → 要求滑块
 * 3) 限流（游客更严）
 */
class QuoteGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user ?? null;
        $ip = (string) ($request->ip() ?: '0.0.0.0');
        $bucket = $user ? ('u'.(int) $user->id) : ('ip:'.md5($ip));

        // 滑块一次通过 → 5 分钟行为放行
        $captchaHeader = (string) $request->header(QuoteAccess::HEADER_CAPTCHA, '');
        if ($captchaHeader !== '' && SlideCaptcha::consumeToken($captchaHeader)) {
            Cache::set('quote_captcha_pass:'.$bucket, 1, 300);
            Cache::delete('quote_captcha_need:'.$bucket);
        }
        $hasCaptchaPass = (bool) Cache::get('quote_captcha_pass:'.$bucket);

        // 1) HMAC 票据
        $ticket = (string) $request->header(QuoteAccess::HEADER_TICKET, '');
        $ts = (string) $request->header(QuoteAccess::HEADER_TS, '');
        $sign = (string) $request->header(QuoteAccess::HEADER_SIGN, '');
        if (!QuoteAccess::verifyHeaders($ticket, $ts, $sign)) {
            return json([
                'message' => '访问凭证无效或已过期，请刷新重试',
                'code' => 'quote_ticket_required',
            ], 403);
        }

        // 2) 已标记需验证且未通过
        if (!$hasCaptchaPass && Cache::get('quote_captcha_need:'.$bucket)) {
            return json([
                'message' => '请完成安全验证',
                'code' => 'captcha_required',
            ], 403);
        }

        // 3) 限流
        $perMin = $user ? 120 : 40;
        $burstMax = $user ? 30 : 12;
        $minKey = 'rl:quote:m:'.$bucket.':'.date('YmdHi');
        $burstKey = 'rl:quote:b:'.$bucket.':'.(int) floor(time() / 10);
        $minCount = (int) Cache::get($minKey, 0);
        $burstCount = (int) Cache::get($burstKey, 0);
        if ($minCount >= $perMin || $burstCount >= $burstMax) {
            if (!$hasCaptchaPass) {
                Cache::set('quote_captcha_need:'.$bucket, 1, 600);
                return json([
                    'message' => '请求过于频繁，请完成安全验证',
                    'code' => 'captcha_required',
                ], 403);
            }
            return json_error('请求过于频繁，请稍后再试', 429);
        }
        Cache::set($minKey, $minCount + 1, 90);
        Cache::set($burstKey, $burstCount + 1, 15);

        // 4) 行为：短时扫多品种 / 近乎固定间隔
        if (!$hasCaptchaPass && $this->isSuspicious($request, $bucket, (bool) $user)) {
            Cache::set('quote_captcha_need:'.$bucket, 1, 600);
            return json([
                'message' => '请完成安全验证',
                'code' => 'captcha_required',
            ], 403);
        }

        return $next($request);
    }

    private function isSuspicious(Request $request, string $bucket, bool $loggedIn): bool
    {
        $now = microtime(true);

        // 近乎等间隔连打（脚本特征）
        $gapKey = 'beh:quote:gaps:'.$bucket;
        $gaps = Cache::get($gapKey, []);
        if (!is_array($gaps)) {
            $gaps = [];
        }
        $lastTs = isset($gaps['_t']) ? (float) $gaps['_t'] : 0.0;
        $intervals = isset($gaps['i']) && is_array($gaps['i']) ? $gaps['i'] : [];
        if ($lastTs > 0) {
            $intervals[] = $now - $lastTs;
            if (count($intervals) > 8) {
                $intervals = array_slice($intervals, -8);
            }
        }
        Cache::set($gapKey, ['_t' => $now, 'i' => $intervals], 120);

        if (count($intervals) >= 6) {
            $avg = array_sum($intervals) / count($intervals);
            // 固定间隔约 0.05~0.8s，且方差极小
            if ($avg >= 0.05 && $avg <= 0.8) {
                $var = 0.0;
                foreach ($intervals as $g) {
                    $var += ($g - $avg) ** 2;
                }
                $var /= count($intervals);
                if ($var < 0.0025) { // stdev < 50ms
                    return true;
                }
            }
        }

        // 短时间扫很多详情品种
        $path = (string) $request->pathinfo();
        if (!preg_match('#quotes/(\d+)#', $path, $m)) {
            // 也试 route 参数
            $idParam = $request->param('id');
            if ($idParam === null || $idParam === '' || !ctype_digit((string) $idParam)) {
                return false;
            }
            $pid = (string) (int) $idParam;
        } else {
            $pid = $m[1];
        }

        $idsKey = 'beh:quote:ids:'.$bucket;
        $ids = Cache::get($idsKey, []);
        if (!is_array($ids)) {
            $ids = [];
        }
        $ids[$pid] = 1;
        Cache::set($idsKey, $ids, 60);
        $uniq = count($ids);
        $limit = $loggedIn ? 18 : 10;

        return $uniq >= $limit;
    }
}
