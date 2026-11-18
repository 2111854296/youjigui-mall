<?php
/**
 * VIP 对公转账待支付订单表 + 站点收款配置键
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

if (!tableExists($pdo, 'vip_pay_orders')) {
    $pdo->exec("CREATE TABLE `vip_pay_orders` (
      `id` bigint unsigned NOT NULL AUTO_INCREMENT,
      `order_no` varchar(32) NOT NULL,
      `user_id` bigint unsigned NOT NULL,
      `vip_grade_id` int unsigned NOT NULL,
      `grade_code` varchar(32) NOT NULL DEFAULT '',
      `grade_name` varchar(64) NOT NULL DEFAULT '',
      `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
      `company_name` varchar(191) DEFAULT NULL,
      `contact_name` varchar(64) DEFAULT NULL,
      `mobile` varchar(32) DEFAULT NULL,
      `phone` varchar(32) DEFAULT NULL,
      `email` varchar(128) DEFAULT NULL,
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
    echo "created vip_pay_orders\n";
} else {
    echo "vip_pay_orders exists\n";
}

// seed site_settings bank keys if missing
$defaults = [
    'vip_bank_account_name' => '',
    'vip_bank_name' => '',
    'vip_bank_account' => '',
    'vip_pay_hint' => '请使用对公账户转账，并在备注中填写订单号；转账后上传凭证，客服审核通过后开通会员。开票请在备注说明抬头。',
    'vip_pay_expire_minutes' => '30',
];
$st = $pdo->prepare('SELECT `key` FROM site_settings WHERE `key` = ?');
$ins = $pdo->prepare('INSERT INTO site_settings (`key`, `value`, `created_at`, `updated_at`) VALUES (?, ?, NOW(), NOW())');
foreach ($defaults as $k => $v) {
    $st->execute([$k]);
    if (!$st->fetch()) {
        $ins->execute([$k, $v]);
        echo "seed setting {$k}\n";
    }
}

echo "VIP_PAY_UPGRADE_OK\n";
