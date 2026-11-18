<?php
namespace app\middleware;

use Closure;
use think\facade\Cache;
use think\Request;
use think\Response;

/**
 * 行情接口限流（应用层，不依赖 Nginx/宝塔）
 * - 未登录：40 次/分钟，短时突发 12 次/10 秒
 * - 已登录：120 次/分钟，短时突发 30 次/10 秒
 */
class QuoteRateLimit
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user ?? null;
        $ip = (string) ($request->ip() ?: '0.0.0.0');
        $bucket = $user ? ('u'.(int) $user->id) : ('ip:'.md5($ip));

        $perMin = $user ? 120 : 40;
        $burstMax = $user ? 30 : 12;
        $minKey = 'rl:quote:m:'.$bucket.':'.date('YmdHi');
        $burstKey = 'rl:quote:b:'.$bucket.':'.(int) floor(time() / 10);

        $minCount = (int) Cache::get($minKey, 0);
        $burstCount = (int) Cache::get($burstKey, 0);

        if ($minCount >= $perMin || $burstCount >= $burstMax) {
            return json_error('请求过于频繁，请稍后再试', 429);
        }

        Cache::set($minKey, $minCount + 1, 90);
        Cache::set($burstKey, $burstCount + 1, 15);

        return $next($request);
    }
}
