<?php
/** 商品上架标记。已有商品默认上架，避免商城被清空。 */
$envFile = is_file(__DIR__ . '/../.env') ? __DIR__ . '/../.env' : '/www/wwwroot/g.aifeili.cn/.env';
$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
    [$k, $v] = array_map('trim', explode('=', $line, 2));
    $env[$k] = trim($v, "\"'");
}
$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $env['DB_HOST'] ?? '127.0.0.1', $env['DB_PORT'] ?? '3306', $env['DB_NAME'] ?? 'yjgmall'),
    $env['DB_USER'] ?? $env['DB_USERNAME'] ?? 'root',
    $env['DB_PASS'] ?? $env['DB_PASSWORD'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$has = $pdo->query("SHOW COLUMNS FROM products LIKE 'is_listed'")->fetch();
if ($has) {
    echo "exists is_listed\n";
} else {
    $pdo->exec("ALTER TABLE products ADD COLUMN is_listed TINYINT(1) NOT NULL DEFAULT 1");
    echo "added is_listed\n";
}
echo "LISTED_OK\n";
