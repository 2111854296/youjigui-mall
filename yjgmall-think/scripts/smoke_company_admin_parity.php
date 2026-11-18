<?php
$login = json_decode(file_get_contents('https://g.aifeili.cn/api/v1/auth/login', false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => json_encode(['login' => 'admin@yjgmall.test', 'password' => 'admin123']),
        'ignore_errors' => true,
    ],
    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
])), true);
$token = $login['token'] ?? ($login['data']['token'] ?? '');
if ($token === '') {
    // fallback username
    $login = json_decode(file_get_contents('https://g.aifeili.cn/api/v1/auth/login', false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode(['login' => 'admin', 'password' => 'admin123']),
            'ignore_errors' => true,
        ],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    ])), true);
    $token = $login['token'] ?? ($login['data']['token'] ?? '');
}
if ($token === '') {
    fwrite(STDERR, "login failed: ".json_encode($login)."\n");
    exit(1);
}
echo "login=ok\n";

function api(string $path, string $token, string $method = 'GET', $body = null): array {
    $opts = [
        'http' => [
            'method' => $method,
            'header' => "Authorization: Bearer {$token}\r\nContent-Type: application/json\r\nAccept: application/json\r\n",
            'ignore_errors' => true,
        ],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    ];
    if ($body !== null) {
        $opts['http']['content'] = json_encode($body);
    }
    $raw = file_get_contents('https://g.aifeili.cn'.$path, false, stream_context_create($opts));
    return json_decode($raw ?: 'null', true) ?: [];
}

$meta = api('/api/v1/admin/resources/companies/meta', $token);
$cols = array_column($meta['data']['columns'] ?? [], 'key');
$fields = array_column($meta['data']['fields'] ?? [], 'name');
echo 'columns='.implode(',', $cols)."\n";
foreach (['vip_theory','vip_correct','reg_year','sell_products','user_mobile_verified','capital_unit'] as $n) {
    echo $n.'='.(in_array($n, $fields, true) ? 'yes' : 'NO')."\n";
}

$vip = api('/api/v1/admin/resources/companies?vip=1', $token);
echo 'vip_total='.($vip['meta']['total'] ?? '?')."\n";
$row = $vip['data'][0] ?? null;
if ($row) {
    echo 'vip_row_has_theory='.(array_key_exists('vip_theory', $row) ? 'yes' : 'NO')."\n";
}

$honMeta = api('/api/v1/admin/resources/company-honors/meta', $token);
$hFields = array_column($honMeta['data']['fields'] ?? [], 'name');
echo 'honor_expires='.(in_array('expires_at', $hFields, true) ? 'yes' : 'NO')."\n";
echo 'honor_status='.(in_array('status', $hFields, true) ? 'yes' : 'NO')."\n";
$pending = api('/api/v1/admin/resources/company-honors?status=pending', $token);
echo 'pending_total='.($pending['meta']['total'] ?? '?')."\n";

$filtered = api('/api/v1/admin/resources/companies?is_featured=1', $token);
echo 'featured_total='.($filtered['meta']['total'] ?? '?')."\n";

echo "SMOKE_OK\n";
