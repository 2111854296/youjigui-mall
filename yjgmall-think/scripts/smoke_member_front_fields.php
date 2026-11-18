<?php
$ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
$list = json_decode(file_get_contents('https://g.aifeili.cn/api/v1/companies?page=1', false, $ctx), true);
$slug = $list['data'][0]['slug'] ?? '';
echo "slug={$slug}\n";
if ($slug === '') {
    fwrite(STDERR, "no company\n");
    exit(1);
}
$detail = json_decode(file_get_contents('https://g.aifeili.cn/api/v1/companies/'.rawurlencode($slug), false, $ctx), true);
$d = $detail['data'] ?? [];
foreach (['reg_year', 'capital_unit', 'capital_display', 'fax', 'email', 'sell_products', 'zip', 'buy_products'] as $k) {
    echo $k.'='.(array_key_exists($k, $d) ? 'yes' : 'NO')."\n";
}
$h = $d['honors'][0] ?? null;
if ($h) {
    echo 'honor_expires='.(array_key_exists('expires_at', $h) ? 'yes' : 'NO')."\n";
    echo 'honor_status='.(array_key_exists('status', $h) ? 'yes' : 'NO')."\n";
} else {
    echo "no_honors\n";
}
echo "SMOKE_OK\n";
