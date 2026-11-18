<?php
/**
 * 资金对公充值订单表
 * Safe to re-run.
 */
$envFile = dirname(__DIR__) . '/.env';
if (!is_file($envFile)) {
    fwrite(STDERR, "missing .env\n");
    exit(1);
}
$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
        continue;
    }
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, " \t\"'");
}
$pdo = new PDO(
    sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $env['DB_HOST'] ?? '127.0.0.1',
        $env['DB_PORT'] ?? '3306',
        $env['DB_DATABASE'] ?? $env['DB_NAME'] ?? ''
    ),
    $env['DB_USERNAME'] ?? $env['DB_USER'] ?? 'root',
    $env['DB_PASSWORD'] ?? $env['DB_PASS'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

function tableExists(PDO $pdo, string $table): bool
{
    $st = $pdo->prepare('SHOW TABLES LIKE ?');
    $st->execute([$table]);
    return (bool) $st->fetch();
}

if (!tableExists($pdo, 'fund_pay_orders')) {
    $pdo->exec("CREATE TABLE `fund_pay_orders` (
      `id` bigint unsigned NOT NULL AUTO_INCREMENT,
      `order_no` varchar(32) NOT NULL,
      `user_id` bigint unsigned NOT NULL,
      `pay_type` varchar(32) NOT NULL DEFAULT 'charge',
      `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
      `title` varchar(128) NOT NULL DEFAULT '账户充值',
      `remark` text,
      `status` varchar(32) NOT NULL DEFAULT 'pending_pay',
      `expire_at` datetime NOT NULL,
      `voucher_url` varchar(512) DEFAULT NULL,
      `voucher_uploaded_at` datetime DEFAULT NULL,
      `bank_name` varchar(191) DEFAULT NULL,
      `bank_account` varchar(64) DEFAULT NULL,
      `bank_account_name` varchar(128) DEFAULT NULL,
      `transfer_note` varchar(64) DEFAULT NULL,
      `admin_remark` varchar(255) DEFAULT NULL,
      `reviewed_at` datetime DEFAULT NULL,
      `reviewed_by` bigint unsigned DEFAULT NULL,
      `created_at` datetime DEFAULT NULL,
      `updated_at` datetime DEFAULT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uk_order_no` (`order_no`),
      KEY `idx_user_status` (`user_id`,`status`),
      KEY `idx_status_expire` (`status`,`expire_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "created fund_pay_orders\n";
} else {
    echo "fund_pay_orders exists\n";
}

echo "FUND_PAY_UPGRADE_OK\n";

function colExists(PDO $pdo, string $table, string $col): bool
{
    $st = $pdo->prepare('SHOW COLUMNS FROM `'.$table.'` LIKE ?');
    $st->execute([$col]);
    return (bool) $st->fetch();
}
if (!colExists($pdo, 'fund_pay_orders', 'biz_key')) {
    $pdo->exec("ALTER TABLE `fund_pay_orders` ADD `biz_key` varchar(80) DEFAULT NULL AFTER `pay_type`");
    echo "add biz_key\n";
}
if (!colExists($pdo, 'fund_pay_orders', 'payload')) {
    $pdo->exec("ALTER TABLE `fund_pay_orders` ADD `payload` text NULL AFTER `remark`");
    echo "add payload\n";
}
echo "FUND_PAY_COLS_OK\n";
