<?php
/** php scripts/upgrade_member_verify.php */
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
$pdo->exec("CREATE TABLE IF NOT EXISTS member_verifications (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `type` VARCHAR(32) NOT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'pending',
  `mobile` VARCHAR(32) NULL,
  `truename` VARCHAR(64) NULL,
  `bank_name` VARCHAR(128) NULL,
  `bank_account` VARCHAR(64) NULL,
  `admin_remark` VARCHAR(255) NULL,
  `reviewed_at` DATETIME NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_type` (`user_id`, `type`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
foreach ([
    'users' => '`id_card` VARCHAR(18) NULL',
    'member_verifications' => '`id_card` VARCHAR(18) NULL',
] as $table => $ddl) {
    $col = 'id_card';
    $q = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $q->execute([$table, $col]);
    if (!(int) $q->fetchColumn()) {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN $ddl");
    }
}
echo "MEMBER_VERIFY_OK\n";
