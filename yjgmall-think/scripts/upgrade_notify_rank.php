<?php
/**
 * 邮件订阅水位、贸易提醒去重、关键词推广字段。
 * php scripts/upgrade_notify_rank.php
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
    $env['DB_NAME'] ?? 'yjgmall_g'
);
$pdo = new PDO($dsn, $env['DB_USER'] ?? 'root', $env['DB_PASS'] ?? '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

function cols(PDO $pdo, string $table): array
{
    try {
        return $pdo->query("DESCRIBE `$table`")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {
        return [];
    }
}

function ensureCol(PDO $pdo, string $table, string $col, string $ddl): void
{
    $c = cols($pdo, $table);
    if (!$c) {
        echo "skip $table.$col\n";
        return;
    }
    if (in_array($col, $c, true)) {
        echo "ok $table.$col\n";
        return;
    }
    $pdo->exec("ALTER TABLE `$table` ADD COLUMN $ddl");
    echo "added $table.$col\n";
}

$pdo->exec("CREATE TABLE IF NOT EXISTS spreads (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `module` VARCHAR(32) NOT NULL DEFAULT 'sell',
  `keyword` VARCHAR(64) NOT NULL DEFAULT '',
  `bid` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `days` INT NOT NULL DEFAULT 30,
  `title` VARCHAR(255) NULL,
  `target_id` INT UNSIGNED NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'pending',
  `starts_at` DATETIME NULL,
  `ends_at` DATETIME NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rank` (`status`, `module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "spreads ok\n";
ensureCol($pdo, 'spreads', 'keyword', '`keyword` VARCHAR(64) NOT NULL DEFAULT ""');
ensureCol($pdo, 'spreads', 'bid', '`bid` DECIMAL(12,2) NOT NULL DEFAULT 0');
ensureCol($pdo, 'spreads', 'days', '`days` INT NOT NULL DEFAULT 30');
ensureCol($pdo, 'spreads', 'starts_at', '`starts_at` DATETIME NULL');
ensureCol($pdo, 'spreads', 'ends_at', '`ends_at` DATETIME NULL');

ensureCol($pdo, 'mail_subscriptions', 'email', '`email` VARCHAR(191) NULL');
ensureCol($pdo, 'mail_subscriptions', 'last_digest_at', '`last_digest_at` DATETIME NULL');
ensureCol($pdo, 'mail_subscriptions', 'enabled', '`enabled` TINYINT(1) NOT NULL DEFAULT 1');
ensureCol($pdo, 'mail_subscriptions', 'modules', '`modules` JSON NULL');

ensureCol($pdo, 'trade_alerts', 'keyword', '`keyword` VARCHAR(128) NOT NULL DEFAULT ""');
ensureCol($pdo, 'trade_alerts', 'module', '`module` VARCHAR(32) NOT NULL DEFAULT "sell"');
ensureCol($pdo, 'trade_alerts', 'area', '`area` VARCHAR(64) NULL');
ensureCol($pdo, 'trade_alerts', 'price_min', '`price_min` DECIMAL(12,2) NULL');
ensureCol($pdo, 'trade_alerts', 'price_max', '`price_max` DECIMAL(12,2) NULL');
ensureCol($pdo, 'trade_alerts', 'status', '`status` VARCHAR(32) NOT NULL DEFAULT "active"');

$pdo->exec("CREATE TABLE IF NOT EXISTS trade_alert_hits (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `alert_id` BIGINT UNSIGNED NOT NULL,
  `target_type` VARCHAR(32) NOT NULL,
  `target_id` BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_hit` (`alert_id`, `target_type`, `target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "trade_alert_hits ok\n";
echo "NOTIFY_RANK_OK\n";
