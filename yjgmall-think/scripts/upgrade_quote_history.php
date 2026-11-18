<?php
/**
 * Create quote_product_prices + seed ~30 days history from current quote_products.
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

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $env['DB_HOST'] ?? '127.0.0.1',
    $env['DB_PORT'] ?? '3306',
    $env['DB_NAME'] ?? ($env['DATABASE_NAME'] ?? 'yjgmall_g')
);
$pdo = new PDO($dsn, $env['DB_USER'] ?? $env['DB_USERNAME'] ?? 'root', $env['DB_PASS'] ?? $env['DB_PASSWORD'] ?? '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$pdo->exec("CREATE TABLE IF NOT EXISTS quote_product_prices (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `quoted_on` DATE NOT NULL,
  `price_avg` DECIMAL(12,2) NULL,
  `price_min` DECIMAL(12,2) NULL,
  `price_max` DECIMAL(12,2) NULL,
  `market` VARCHAR(64) NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_product_date` (`product_id`, `quoted_on`),
  KEY `idx_quoted_on` (`quoted_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "table ok\n";

function seedHash($id, $name): int
{
    $s = (string) ($id ?: $name);
    $h = 0;
    $len = strlen($s);
    for ($i = 0; $i < $len; $i++) {
        $h = ($h * 31 + ord($s[$i])) & 0xFFFFFFFF;
    }
    return $h ?: 1;
}

function syntheticPoint(int $seed, float $avg, float $min, float $max, int $dayOffset): float
{
    $span = max($max - $min, $avg * 0.08, 1.0);
    $wave = sin(($seed % 17) + $dayOffset * 0.55) * 0.35;
    $noise = (($seed >> ($dayOffset % 8)) & 7) / 7 - 0.5;
    $v = $avg + $span * ($wave * 0.5 + $noise * 0.25);
    return max(0.01, round($v, 2));
}

$products = $pdo->query('SELECT id, name, price_avg, price_min, price_max, market, quoted_on FROM quote_products WHERE is_active = 1')->fetchAll(PDO::FETCH_ASSOC);
$ins = $pdo->prepare(
    'INSERT INTO quote_product_prices (product_id, quoted_on, price_avg, price_min, price_max, market, created_at, updated_at)
     VALUES (?,?,?,?,?,?,NOW(),NOW())
     ON DUPLICATE KEY UPDATE price_avg=VALUES(price_avg), price_min=VALUES(price_min), price_max=VALUES(price_max), market=VALUES(market), updated_at=NOW()'
);

$days = 30;
foreach ($products as $p) {
    $id = (int) $p['id'];
    $avg = (float) ($p['price_avg'] ?? $p['price_min'] ?? 100);
    $min = (float) ($p['price_min'] ?? $avg * 0.92);
    $max = (float) ($p['price_max'] ?? $avg * 1.08);
    $seed = seedHash($id, (string) $p['name']);
    $cnt = 0;
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = (new DateTimeImmutable('today'))->modify("-{$i} days")->format('Y-m-d');
        if ($i === 0) {
            $price = round($avg, 2);
            $pMin = $min ?: null;
            $pMax = $max ?: null;
        } else {
            $price = syntheticPoint($seed, $avg, $min, $max, $i);
            $pMin = round($price * 0.98, 2);
            $pMax = round($price * 1.02, 2);
        }
        $ins->execute([$id, $date, $price, $pMin, $pMax, $p['market'] ?: null]);
        $cnt++;
    }
    echo "seed product {$id} {$p['name']} days={$cnt}\n";
}

echo "DONE\n";
