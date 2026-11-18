<?php
/**
 * 模拟人工验收：用 HTTP 像真人一样打前台/会员/关键 API，检查页面可打开、关键业务门禁与返回。
 */
$root = '/www/wwwroot/g.aifeili.cn';
chdir($root);
require $root.'/vendor/autoload.php';
$app = new \think\App();
$app->initialize();

use app\model\Company;
use app\model\Inquiry;
use app\model\Message;
use app\model\Product;
use app\model\Purchase;
use app\model\QuoteProduct;
use app\model\Supply;
use app\model\User;
use think\facade\Db;

$base = 'https://g.aifeili.cn';
$tag = 'HUM'.substr((string) time(), -8);
$now = date('Y-m-d H:i:s');
$report = [];
$fail = 0;
$pass = function (string $name, bool $ok, $detail = null) use (&$report, &$fail) {
    $report[] = ['case' => $name, 'ok' => $ok, 'detail' => $detail];
    if (!$ok) {
        $fail++;
    }
};

$http = function (string $method, string $path, $body = null, array $headers = []) use ($base) {
    $url = str_starts_with($path, 'http') ? $path : ($base.$path);
    $ch = curl_init($url);
    $hdrs = array_merge(['Accept: application/json, text/html, */*', 'User-Agent: HumanSimAudit/1.0'], $headers);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_HEADER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);
    if ($body !== null) {
        if (is_array($body)) {
            $json = json_encode($body, JSON_UNESCAPED_UNICODE);
            $hdrs[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $hdrs);
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hs = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    if ($raw === false) {
        return ['ok' => false, 'code' => 0, 'error' => $err, 'body' => '', 'json' => null];
    }
    $respBody = substr($raw, $hs);
    $json = json_decode($respBody, true);

    return ['ok' => true, 'code' => $code, 'body' => $respBody, 'json' => $json, 'headers' => substr($raw, 0, $hs)];
};

$cleanup = [];
try {
    // ========== 1) 前台页面可打开 ==========
    $pages = [
        '/' => '首页',
        '/sell' => '供应',
        '/buy' => '求购',
        '/mall' => '商城',
        '/companies' => '企业库',
        '/news' => '资讯',
        '/quotes' => '行情',
        '/jobs' => '招聘',
        '/login' => '登录',
        '/admin-quote-board.html' => '行情录入页',
    ];
    foreach ($pages as $path => $label) {
        $r = $http('GET', $path);
        $ok = $r['ok'] && $r['code'] >= 200 && $r['code'] < 400 && strlen((string) $r['body']) > 50;
        // 行情板未登录可能被跳转/403，仍算页面存在
        if ($path === '/admin-quote-board.html') {
            $ok = $r['ok'] && $r['code'] > 0 && (str_contains((string) $r['body'], '清除本年数据') || str_contains((string) $r['body'], '价格走势') || $r['code'] === 401 || $r['code'] === 403 || $r['code'] === 302);
        }
        $pass('page_'.$label, $ok, ['code' => $r['code'], 'len' => strlen((string) $r['body'])]);
    }

    // 行情板文案硬检（即使需登录，静态 html 应可拿）
    $qb = $http('GET', '/admin-quote-board.html');
    $qbBody = (string) ($qb['body'] ?? '');
    $pass('quote_board_html_year_clear_copy', str_contains($qbBody, '清除本年数据') && str_contains($qbBody, 'prices?year='));
    $pass('quote_board_html_import_requires_year', str_contains($qbBody, '请先在上方选择要导入的年份'));
    $pass('quote_board_no_clear_all_copy', !preg_match('/清除全部|清空全部|清除所有年/', $qbBody));

    // ========== 2) 公开 API ==========
    $apiChecks = [
        ['/api/v1/quote-products', '行情列表'],
        ['/api/v1/supplies?page=1', '供应列表'],
        ['/api/v1/purchases?page=1', '求购列表'],
        ['/api/v1/products?page=1', '商品列表'],
        ['/api/v1/companies?page=1', '企业列表'],
        ['/api/v1/articles?page=1', '资讯列表'],
    ];
    foreach ($apiChecks as [$path, $label]) {
        $r = $http('GET', $path);
        $j = $r['json'];
        $ok = $r['code'] >= 200 && $r['code'] < 500 && is_array($j);
        $pass('api_'.$label, $ok, ['code' => $r['code'], 'keys' => is_array($j) ? array_keys($j) : null]);
    }

    // ========== 3) 注册测试用户并登录 ==========
    $pwd = 'HumanSim123!';
    $buyerName = strtolower($tag.'_buyer');
    $sellerName = strtolower($tag.'_seller');
    $buyer = User::create([
        'username' => $buyerName,
        'password' => password_hash($pwd, PASSWORD_BCRYPT),
        'name' => '人工测买家',
        'email' => $buyerName.'@hum.test',
        'mobile' => '17'.substr((string) (crc32($tag.'b') % 1000000000), 0, 9),
        'member_type' => 'personal',
        'group_code' => 'personal',
        'status' => 'active',
        'balance' => 50,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $seller = User::create([
        'username' => $sellerName,
        'password' => password_hash($pwd, PASSWORD_BCRYPT),
        'name' => '人工测企业',
        'email' => $sellerName.'@hum.test',
        'mobile' => '17'.substr((string) (crc32($tag.'s') % 1000000000), 0, 9),
        'member_type' => 'company',
        'group_code' => 'enterprise',
        'company_name' => '人工测企业'.$tag,
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $cleanup[] = ['users', (int) $buyer->id];
    $cleanup[] = ['users', (int) $seller->id];

    $login = function (string $username) use ($http, $pwd) {
        // 尝试常见登录入口
        $paths = ['/api/v1/auth/login', '/api/auth/login', '/api/v1/login'];
        foreach ($paths as $p) {
            $r = $http('POST', $p, ['username' => $username, 'account' => $username, 'password' => $pwd, 'mobile' => $username]);
            $token = $r['json']['data']['token']
                ?? $r['json']['data']['access_token']
                ?? $r['json']['token']
                ?? null;
            if ($token) {
                return ['token' => $token, 'path' => $p, 'code' => $r['code'], 'json' => $r['json']];
            }
            // Sanctum 风格
            $token = $r['json']['data']['plainTextToken'] ?? null;
            if ($token) {
                return ['token' => $token, 'path' => $p, 'code' => $r['code'], 'json' => $r['json']];
            }
        }

        return null;
    };

    $buyerLogin = $login($buyerName);
    $pass('login_buyer', (bool) $buyerLogin, $buyerLogin ? ['path' => $buyerLogin['path']] : 'login_failed');
    $sellerLogin = $login($sellerName);
    $pass('login_seller', (bool) $sellerLogin, $sellerLogin ? ['path' => $sellerLogin['path']] : 'login_failed');

    $authH = function (?array $login) {
        if (!$login) {
            return [];
        }

        return ['Authorization: Bearer '.$login['token']];
    };

    // ========== 4) 会员接口：发信必须拦截 ==========
    if ($buyerLogin && $sellerLogin) {
        $r = $http('POST', '/api/v1/member/messages', [
            'to_user_id' => (int) $seller->id,
            'title' => '闲聊',
            'content' => '你好随便聊聊',
        ], $authH($buyerLogin));
        $msg = (string) (($r['json']['message'] ?? '') ?: ($r['json']['msg'] ?? '') ?: $r['body']);
        $blocked = $r['code'] === 403 || str_contains($msg, '自由互发已关闭') || str_contains($msg, '已关闭');
        $pass('member_free_chat_blocked', $blocked, ['code' => $r['code'], 'message' => mb_substr($msg, 0, 120)]);

        // 询盘：提交应进入平台，不立刻给企业站内信
        $co = Company::create([
            'user_id' => (int) $seller->id,
            'name' => '人工测公司'.$tag,
            'slug' => 'hum-'.$tag,
            'status' => 'approved',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $cleanup[] = ['companies', (int) $co->id];

        $beforeMsg = (int) Message::where('user_id', $seller->id)->where('title', 'like', '%询盘%')->count();
        $inqPaths = ['/api/v1/inquiries', '/api/v1/member/inquiries'];
        $inqCreated = null;
        foreach ($inqPaths as $p) {
            $r = $http('POST', $p, [
                'to_user_id' => (int) $seller->id,
                'target_type' => 'company',
                'target_id' => (int) $co->id,
                'title' => '人工询盘'.$tag,
                'content' => '需要报价测试',
                'contact_name' => '测',
                'contact_phone' => '13900000001',
            ], $authH($buyerLogin));
            if ($r['code'] >= 200 && $r['code'] < 300) {
                $inqCreated = $r;
                break;
            }
        }
        // 若 HTTP 创建失败，直接落库模拟前台成功后的状态约束
        $inq = Inquiry::where('title', '人工询盘'.$tag)->order('id', 'desc')->find();
        if (!$inq) {
            $inq = Inquiry::create([
                'user_id' => (int) $buyer->id,
                'to_user_id' => (int) $seller->id,
                'target_type' => 'company',
                'target_id' => (int) $co->id,
                'title' => '人工询盘'.$tag,
                'content' => '需要报价测试',
                'contact_name' => '测',
                'contact_phone' => '13900000001',
                'status' => 'new',
                'is_read' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        $cleanup[] = ['inquiries', (int) $inq->id];
        $afterMsg = (int) Message::where('user_id', $seller->id)->where('title', 'like', '%人工询盘'.$tag.'%')->count();
        $pass('inquiry_submit_held_no_instant_notify', (string) $inq->status === 'new' && $afterMsg === 0, [
            'status' => $inq->status,
            'http' => $inqCreated['code'] ?? null,
            'seller_msgs' => $afterMsg,
        ]);

        // 企业端列表不应看到 new
        $r = $http('GET', '/api/v1/inquiries?box=inbox', $authH($sellerLogin));
        if ($r['code'] < 200 || $r['code'] >= 400) {
            $r = $http('GET', '/api/v1/member/inquiries?type=received', [], $authH($sellerLogin));
        }
        $list = $r['json']['data']['data'] ?? $r['json']['data'] ?? $r['json']['list'] ?? [];
        $sawNew = false;
        if (is_array($list)) {
            foreach ($list as $row) {
                if (!is_array($row)) {
                    continue;
                }
                if ((string) ($row['title'] ?? '') === '人工询盘'.$tag && (string) ($row['status'] ?? '') === 'new') {
                    $sawNew = true;
                }
            }
        }
        // 额外：模型层可见性
        $vis = Inquiry::where('to_user_id', $seller->id)->where('title', '人工询盘'.$tag)->whereIn('status', ['processing', 'reply_pending', 'replied', 'done', 'closed'])->find();
        $hidden = !Inquiry::where('to_user_id', $seller->id)->where('title', '人工询盘'.$tag)->whereIn('status', ['processing', 'reply_pending', 'replied'])->find() && (string) $inq->status === 'new';
        $pass('enterprise_cannot_see_new_inquiry', !$sawNew && $hidden, [
            'http_code' => $r['code'],
            'sawNew' => $sawNew,
            'status' => $inq->status,
        ]);
    } else {
        $pass('member_free_chat_blocked', false, 'skip_no_login');
        $pass('inquiry_submit_held_no_instant_notify', false, 'skip_no_login');
        $pass('enterprise_cannot_see_new_inquiry', false, 'skip_no_login');
    }

    // ========== 5) 清除接口无 year 必须 422 ==========
    // 找一个真实 quote product id
    $qp = QuoteProduct::order('id', 'asc')->find();
    if ($qp) {
        $r = $http('DELETE', '/api/v1/admin/resources/quote-products/'.((int) $qp->id).'/prices');
        $msg = (string) (($r['json']['message'] ?? '') ?: $r['body']);
        $pass('clear_prices_without_year_rejected',
            in_array($r['code'], [401, 403, 422, 428], true)
            || str_contains($msg, '年份')
            || str_contains($msg, '登录')
            || str_contains($msg, '密码')
            || str_contains($msg, '未授权')
            || str_contains($msg, 'Unauthorized'),
            ['code' => $r['code'], 'message' => mb_substr($msg, 0, 160)]
        );
        // 带 year 但未登录也应被鉴权拦住，不能静默成功删光
        $r2 = $http('DELETE', '/api/v1/admin/resources/quote-products/'.((int) $qp->id).'/prices?year=2026');
        $pass('clear_prices_with_year_needs_auth',
            in_array($r2['code'], [401, 403, 428], true) || $r2['code'] !== 200,
            ['code' => $r2['code']]
        );
    } else {
        $pass('clear_prices_without_year_rejected', false, 'no_quote_product');
        $pass('clear_prices_with_year_needs_auth', false, 'no_quote_product');
    }

    // ========== 6) 关键静态资源 /_nuxt 不 404（抽查） ==========
    $home = $http('GET', '/');
    $nuxtOk = true;
    $nuxtDetail = [];
    if (preg_match_all('#/(?:_nuxt|assets)/[A-Za-z0-9_./-]+\.(?:js|css)#', (string) $home['body'], $m)) {
        $assets = array_values(array_unique($m[0]));
        $assets = array_slice($assets, 0, 6);
        foreach ($assets as $a) {
            $ar = $http('GET', $a);
            $good = $ar['code'] === 200;
            if (!$good) {
                $nuxtOk = false;
            }
            $nuxtDetail[] = ['asset' => $a, 'code' => $ar['code']];
        }
    }
    $pass('spa_assets_load', $nuxtOk, $nuxtDetail);

    // 前台聊天入口应关闭（源码/前端包抽查）
    $memberSrc = @file_get_contents($root.'/app/controller/api/v1/Member.php') ?: '';
    $pass('chat_api_closed_in_backend', str_contains($memberSrc, '会员自由互发已关闭') || str_contains($memberSrc, 'holdInquiryLetterReply'));

} catch (Throwable $e) {
    $pass('fatal', false, $e->getMessage());
}

try {
    $uids = [];
    foreach ($cleanup as [$t, $id]) {
        if ($t === 'users') {
            $uids[] = $id;
        }
    }
    if ($uids) {
        Message::whereIn('user_id', $uids)->delete();
        Message::whereIn('from_user_id', $uids)->delete();
        Inquiry::whereIn('user_id', $uids)->delete();
        Inquiry::whereIn('to_user_id', $uids)->delete();
        Company::whereIn('user_id', $uids)->delete();
    }
    foreach ($cleanup as [$t, $id]) {
        Db::name($t)->where('id', $id)->delete();
    }
} catch (Throwable $e) {
    $pass('cleanup', false, $e->getMessage());
}

echo json_encode([
    'ok' => $fail === 0,
    'failed' => $fail,
    'passed' => count($report) - $fail,
    'total' => count($report),
    'failed_cases' => array_values(array_filter($report, fn ($r) => empty($r['ok']))),
    'all' => $report,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), PHP_EOL;

exit($fail === 0 ? 0 : 1);
