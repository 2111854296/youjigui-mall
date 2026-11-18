<?php
/**
 * companies.env_images — 厂房环境图 JSON 数组（最多 8 张）
 * Safe to re-run.
 */
$envFile = __DIR__ . '/../.env';
if (!is_file($envFile)) {
    $envFile = '/www/wwwroot/g.aifeili.cn/.env';
}
$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if ($line[0] === '#' || !str_contains($line, '=')) continue;
    [$k, $v] = array_map('trim', explode('=', $line, 2));
    $env[$k] = trim($v, "\"'");
}

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $env['DB_HOST'] ?? '127.0.0.1',
    $env['DB_PORT'] ?? '3306',
    $env['DB_NAME'] ?? ($env['DATABASE_NAME'] ?? 'yjgmall_g')
);
$pdo = new PDO($dsn, $env['DB_USER'] ?? $env['DB_USERNAME'] ?? 'root', $env['DB_PASS'] ?? $env['DB_PASSWORD'] ?? '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$st = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
$st->execute(['companies', 'env_images']);
if ((int) $st->fetchColumn() > 0) {
    echo "skip env_images\n";
} else {
    $pdo->exec("ALTER TABLE companies ADD COLUMN `env_images` TEXT NULL COMMENT '厂房环境图JSON最多8张' AFTER `banner`");
    echo "add env_images\n";
}
echo "DONE\n";
