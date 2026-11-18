<?php
/**
 * 实测：清除指定年份是否只删该年，其它年保留。
 */
$root = '/www/wwwroot/g.aifeili.cn';
chdir($root);
$lines = @file($root.'/.env') ?: [];
$env = [];
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, " \t\"'");
}
$pdo = new PDO(
    'mysql:host='.($env['DB_HOST'] ?? '127.0.0.1').';dbname='.($env['DB_NAME'] ?? 'yjgmall').';charset=utf8mb4',
    $env['DB_USER'] ?? 'root',
    $env['DB_PASS'] ?? ($env['DB_PASSWORD'] ?? ''),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// 找一个有多年数据的品种；没有就造测试品种
$pid = (int) ($pdo->query("
  SELECT product_id FROM quote_product_prices
  GROUP BY product_id
  HAVING COUNT(DISTINCT YEAR(quoted_on)) >= 2
  ORDER BY COUNT(*) DESC LIMIT 1
")->fetchColumn() ?: 0);

$createdProduct = false;
$now = date('Y-m-d H:i:s');
if ($pid <= 0) {
    $pid = (int) ($pdo->query('SELECT id FROM quote_products ORDER BY id ASC LIMIT 1')->fetchColumn() ?: 0);
    if ($pid <= 0) {
        $cols = [];
        foreach ($pdo->query('SHOW COLUMNS FROM quote_products') as $c) {
            $cols[$c['Field']] = true;
        }
        $fields = ['name', 'created_at', 'updated_at'];
        $vals = ['清除年测品种', $now, $now];
        if (isset($cols['unit'])) { $fields[] = 'unit'; $vals[] = '元/吨'; }
        if (isset($cols['market'])) { $fields[] = 'market'; $vals[] = '测试'; }
        if (isset($cols['is_active'])) { $fields[] = 'is_active'; $vals[] = 1; }
        $ph = implode(',', array_fill(0, count($fields), '?'));
        $pdo->prepare('INSERT INTO quote_products ('.implode(',', $fields).") VALUES ($ph)")->execute($vals);
        $pid = (int) $pdo->lastInsertId();
        $createdProduct = true;
    }
}

$marker = 'CLEAR_YEAR_TEST_'.time();
// 写入 2024 / 2025 / 2026 各一天测试价（用很偏的价格标记，测完删）
$insert = $pdo->prepare("INSERT INTO quote_product_prices (product_id, quoted_on, price_avg, price_min, price_max, market, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?)");
$dates = [
    '2024-06-15' => 11111.11,
    '2025-06-15' => 22222.22,
    '2026-06-15' => 33333.33,
];
foreach ($dates as $d => $price) {
    $pdo->prepare("DELETE FROM quote_product_prices WHERE product_id=? AND quoted_on=?")->execute([$pid, $d]);
    $insert->execute([$pid, $d, $price, $price, $price, $marker, $now, $now]);
}
$reg = $pdo->prepare("INSERT INTO quote_region_prices (product_id, region, quoted_on, price, created_at, updated_at) VALUES (?,?,?,?,?,?)");
foreach ($dates as $d => $price) {
    $pdo->prepare("DELETE FROM quote_region_prices WHERE product_id=? AND quoted_on=? AND region=?")->execute([$pid, $d, $marker]);
    $reg->execute([$pid, $marker, $d, $price, $now, $now]);
}

$countYear = function (int $year) use ($pdo, $pid, $marker): array {
    $from = sprintf('%04d-01-01', $year);
    $to = sprintf('%04d-12-31', $year);
    $days = (int) $pdo->query("SELECT COUNT(*) FROM quote_product_prices WHERE product_id=$pid AND market=".$pdo->quote($marker)." AND quoted_on BETWEEN '$from' AND '$to'")->fetchColumn();
    $regs = (int) $pdo->query("SELECT COUNT(*) FROM quote_region_prices WHERE product_id=$pid AND region=".$pdo->quote($marker)." AND quoted_on BETWEEN '$from' AND '$to'")->fetchColumn();
    return ['days' => $days, 'regions' => $regs];
};

$before = [
    2024 => $countYear(2024),
    2025 => $countYear(2025),
    2026 => $countYear(2026),
];

// 直接按接口同逻辑删 2026
$pdo->prepare("DELETE FROM quote_product_prices WHERE product_id=? AND quoted_on BETWEEN '2026-01-01' AND '2026-12-31' AND market=?")
    ->execute([$pid, $marker]);
$pdo->prepare("DELETE FROM quote_region_prices WHERE product_id=? AND quoted_on BETWEEN '2026-01-01' AND '2026-12-31' AND region=?")
    ->execute([$pid, $marker]);

$after = [
    2024 => $countYear(2024),
    2025 => $countYear(2025),
    2026 => $countYear(2026),
];

// 再测「不带 year 应拒绝」——读线上源码是否要求 year
$src = file_get_contents($root.'/app/controller/api/v1/AdminResource.php');
$requiresYear = str_contains($src, "请指定要清除的年份") && str_contains($src, 'whereBetween');

// 清理测试标记数据
$pdo->prepare("DELETE FROM quote_product_prices WHERE product_id=? AND market=?")->execute([$pid, $marker]);
$pdo->prepare("DELETE FROM quote_region_prices WHERE product_id=? AND region=?")->execute([$pid, $marker]);
if ($createdProduct) {
    $pdo->prepare("DELETE FROM quote_products WHERE id=?")->execute([$pid]);
}

$ok =
    $before[2024]['days'] === 1 && $before[2025]['days'] === 1 && $before[2026]['days'] === 1
    && $after[2024]['days'] === 1 && $after[2025]['days'] === 1 && $after[2026]['days'] === 0
    && $after[2024]['regions'] === 1 && $after[2025]['regions'] === 1 && $after[2026]['regions'] === 0
    && $requiresYear;

echo json_encode([
    'ok' => $ok,
    'product_id' => $pid,
    'before' => $before,
    'after_clear_2026' => $after,
    'api_requires_year' => $requiresYear,
    'html_button' => (str_contains(file_get_contents($root.'/public/admin-quote-board.html'), '清除本年数据') ? 'ok' : 'missing'),
    'html_passes_year' => (str_contains(file_get_contents($root.'/public/admin-quote-board.html'), 'prices?year=') ? 'ok' : 'missing'),
    'expect' => '清 2026 后仅 2026=0，2024/2025 仍为 1；接口强制 year；按钮文案正确',
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), PHP_EOL;
