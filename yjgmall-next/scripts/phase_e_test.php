<?php

$base = 'http://127.0.0.1:8000/api/v1';

function req($method, $path, $token = null, $body = null)
{
    global $base;
    $ch = curl_init($base.$path);
    $headers = ['Accept: application/json', 'Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer '.$token;
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 15,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) {
        return [0, ['error' => $err]];
    }
    $raw = preg_replace('/^\xEF\xBB\xBF/', '', (string) $raw);

    return [$code, json_decode($raw, true) ?: ['raw' => $raw]];
}

[$c, $login] = req('POST', '/auth/login', null, ['login' => '2111854296', 'password' => '123456']);
echo "login $c group=".($login['data']['group_code'] ?? '?')."\n";
$token = $login['token'] ?? null;
if (! $token) {
    echo "FAIL login\n";
    print_r($login);
    exit(1);
}

$tests = [
    ['GET', '/member/profile-deep'],
    ['PUT', '/member/passwords', ['pay_password' => '654321', 'pay_password_confirmation' => '654321']],
    ['POST', '/member/validations', ['type' => 'mobile', 'mobile' => '13800138000']],
    ['POST', '/member/alerts', ['module' => 'sell', 'keyword' => '有机硅']],
    ['PUT', '/member/mail', ['email' => 'demo@yjgmall.test', 'topics' => ['sell'], 'is_active' => true]],
    ['POST', '/member/products', ['title' => '测试商品E', 'price' => 99.5, 'stock' => 5, 'summary' => 'phase e']],
    ['POST', '/member/articles', ['title' => '测试资讯E', 'summary' => 's', 'content' => 'c']],
    ['POST', '/member/deposit', ['amount' => 50]],
    ['POST', '/member/points/buy', ['points' => 20]],
    ['POST', '/member/asks', ['title' => '测试问客服', 'content' => 'hello']],
    ['POST', '/member/chats', ['peer_id' => 1]],
];

foreach ($tests as $t) {
    [$method, $path, $body] = array_pad($t, 3, null);
    [$code, $res] = req($method, $path, $token, $body);
    $ok = $code >= 200 && $code < 300 ? 'OK' : 'FAIL';
    $msg = $res['message'] ?? ($res['data']['id'] ?? ($res['data']['title'] ?? ''));
    if (is_array($msg)) {
        $msg = json_encode($msg, JSON_UNESCAPED_UNICODE);
    }
    echo "$ok $code $method $path $msg\n";
    if ($ok === 'FAIL') {
        echo '  '.substr(json_encode($res, JSON_UNESCAPED_UNICODE), 0, 300)."\n";
    }
}

if (! empty($res) && isset($res['data']['id']) && str_contains($path, 'chats')) {
    $chatId = $res['data']['id'];
    [$code, $m] = req('POST', "/member/chats/{$chatId}/messages", $token, ['content' => '你好，站内交谈测试']);
    echo (($code >= 200 && $code < 300) ? 'OK' : 'FAIL')." $code POST chat message\n";
}

echo "done\n";
