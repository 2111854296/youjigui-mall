<?php
/**
 * 按原站商品页上的卖家主页，把 products.company_id 记到对应企业。
 * 对不上的商品保持为空，不写死公司名。
 * php scripts/link_mall_product_companies.php
 */
$envFile = __DIR__ . '/../.env';
if (!is_file($envFile)) {
    $envFile = '/www/wwwroot/g.aifeili.cn/.env';
}
$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
        continue;
    }
    [$k, $v] = array_map('trim', explode('=', $line, 2));
    $env[$k] = trim($v, "\"'");
}
$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $env['DB_HOST'] ?? '127.0.0.1', $env['DB_PORT'] ?? '3306', $env['DB_NAME'] ?? 'yjgmall'),
    $env['DB_USER'] ?? $env['DB_USERNAME'] ?? 'root',
    $env['DB_PASS'] ?? $env['DB_PASSWORD'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$col = $pdo->query("SHOW COLUMNS FROM products LIKE 'company_id'")->fetch();
if (!$col) {
    $pdo->exec('ALTER TABLE products ADD COLUMN company_id INT UNSIGNED NULL AFTER user_id');
    echo "added products.company_id\n";
}
$originCol = $pdo->query("SHOW COLUMNS FROM products LIKE 'origin_itemid'")->fetch();
if (!$originCol) {
    echo "no origin_itemid, skip\n";
    exit(0);
}

function http_get(string $url): string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
    return is_string($body) ? $body : '';
}

function seller_of(string $html): array
{
    $src = $html;
    if (preg_match('/id="contact"(.{0,1800})/s', $html, $b)) {
        $src = $b[1];
    }
    if (!preg_match('/homepage=([A-Za-z0-9_]+)[^>]*>([^<]{2,80})</u', $src, $m)) {
        return ['', ''];
    }
    $name = trim(html_entity_decode(strip_tags($m[2]), ENT_QUOTES, 'UTF-8'));
    $name = preg_replace('/\s+/u', '', $name) ?? $name;

    return [$m[1], $name];
}

$byHp = $pdo->prepare('SELECT id FROM companies WHERE origin_homepage=? LIMIT 1');
$byName = $pdo->prepare("SELECT id FROM companies WHERE name=? AND status IN ('approved','offline')");
$setHp = $pdo->prepare("UPDATE companies SET origin_homepage=? WHERE id=? AND (origin_homepage IS NULL OR origin_homepage='')");
$setCo = $pdo->prepare('UPDATE products SET company_id=? WHERE id=?');

$rows = $pdo->query("SELECT id, origin_itemid FROM products WHERE origin_itemid IS NOT NULL AND origin_itemid<>0")->fetchAll(PDO::FETCH_ASSOC);
$linked = 0;
$miss = 0;
foreach ($rows as $row) {
    $html = http_get('https://www.yjgmall.com/mall/show.php?itemid=' . (int) $row['origin_itemid']);
    [$hp, $name] = seller_of($html);
    if ($hp === '') {
        $miss++;
        echo "miss item {$row['origin_itemid']} product {$row['id']}\n";
        continue;
    }
    $byHp->execute([$hp]);
    $cid = $byHp->fetchColumn();
    if (!$cid && $name !== '') {
        $byName->execute([$name]);
        $ids = $byName->fetchAll(PDO::FETCH_COLUMN);
        if (count($ids) === 1) {
            $cid = $ids[0];
            $setHp->execute([$hp, $cid]);
        }
    }
    if (!$cid) {
        $miss++;
        echo "unmatched {$hp} {$name} product {$row['id']}\n";
        continue;
    }
    $setCo->execute([(int) $cid, (int) $row['id']]);
    $linked++;
    usleep(60000);
}
echo "linked={$linked} miss={$miss} total=" . count($rows) . "\n";
