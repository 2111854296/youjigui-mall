<?php
/**
 * Create site_settings + seed WeChat QR defaults.
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

$pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (
  `key` VARCHAR(64) NOT NULL PRIMARY KEY,
  `value` TEXT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$defaults = [
    'wechat_subscribe_qr' => '/img/qr/weima.jpg',
    'wechat_subscribe_label' => '扫一扫订阅号',
    'wechat_service_qr' => '/img/qr/weimas.jpg',
    'wechat_service_label' => '扫一扫服务号',
    'header_cta_text' => '免费发布',
    'header_cta_link' => '/member',
    'site_name' => '有机硅商城',
    'site_domain' => 'g.aifeili.cn',
];

$ins = $pdo->prepare('INSERT INTO site_settings (`key`,`value`,`created_at`,`updated_at`) VALUES (?,?,NOW(),NOW())
  ON DUPLICATE KEY UPDATE `key`=`key`');
foreach ($defaults as $k => $v) {
    $ins->execute([$k, $v]);
    echo "seed $k\n";
}

// Force migrate old demo domain → production host
$force = $pdo->prepare('UPDATE site_settings SET `value`=?, `updated_at`=NOW() WHERE `key`=?');
$force->execute(['g.aifeili.cn', 'site_domain']);
echo "force site_domain=g.aifeili.cn\n";
echo "DONE\n";
