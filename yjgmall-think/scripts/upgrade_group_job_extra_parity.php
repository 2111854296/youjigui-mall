<?php
/**
 * Group orders + resumes/job applications + exhibit/brand user_id.
 * Safe to re-run.
 */
$envFile = dirname(__DIR__) . '/.env';
if (!is_file($envFile)) {
    fwrite(STDERR, "missing .env\n");
    exit(1);
}
$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, " \t\"'");
}
$host = $env['DB_HOST'] ?? '127.0.0.1';
$port = $env['DB_PORT'] ?? '3306';
$db = $env['DB_DATABASE'] ?? $env['DB_NAME'] ?? '';
$user = $env['DB_USERNAME'] ?? $env['DB_USER'] ?? 'root';
$pass = $env['DB_PASSWORD'] ?? $env['DB_PASS'] ?? '';
$pdo = new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

function colExists(PDO $pdo, string $table, string $col): bool
{
    $st = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
    $st->execute([$col]);
    return (bool) $st->fetch();
}

function ensureCol(PDO $pdo, string $table, string $col, string $ddl): void
{
    if (!colExists($pdo, $table, $col)) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN {$ddl}");
        echo "ADD {$table}.{$col}\n";
    } else {
        echo "OK {$table}.{$col}\n";
    }
}

$pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS `group_orders` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `group_deal_id` int unsigned NOT NULL,
  `buyer_user_id` int unsigned NOT NULL,
  `seller_user_id` int unsigned DEFAULT NULL,
  `title` varchar(200) NOT NULL DEFAULT '',
  `thumb` varchar(500) DEFAULT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT 0,
  `quantity` int unsigned NOT NULL DEFAULT 1,
  `amount` decimal(12,2) NOT NULL DEFAULT 0,
  `receiver_name` varchar(80) DEFAULT NULL,
  `receiver_phone` varchar(40) DEFAULT NULL,
  `receiver_address` varchar(255) DEFAULT NULL,
  `remark` varchar(500) DEFAULT NULL,
  `tracking_no` varchar(80) DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'paid',
  `payment_method` varchar(40) DEFAULT 'alipay_mock',
  `paid_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_buyer` (`buyer_user_id`),
  KEY `idx_seller` (`seller_user_id`),
  KEY `idx_deal` (`group_deal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);
echo "OK group_orders\n";

$pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS `resumes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `title` varchar(120) NOT NULL DEFAULT '',
  `truename` varchar(80) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `education` varchar(40) DEFAULT NULL,
  `experience` varchar(40) DEFAULT NULL,
  `minsalary` int DEFAULT NULL,
  `maxsalary` int DEFAULT NULL,
  `mobile` varchar(40) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `area` varchar(80) DEFAULT NULL,
  `summary` varchar(500) DEFAULT NULL,
  `content` mediumtext,
  `thumb` varchar(500) DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'approved',
  `open` varchar(20) NOT NULL DEFAULT 'public',
  `views` int unsigned NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);
echo "OK resumes\n";

$pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS `job_applications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `job_post_id` int unsigned NOT NULL,
  `resume_id` int unsigned NOT NULL,
  `employer_user_id` int unsigned DEFAULT NULL,
  `applicant_user_id` int unsigned NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'pending',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_job_applicant` (`job_post_id`,`applicant_user_id`),
  KEY `idx_employer` (`employer_user_id`),
  KEY `idx_applicant` (`applicant_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);
echo "OK job_applications\n";

ensureCol($pdo, 'exhibits', 'user_id', '`user_id` int unsigned DEFAULT NULL AFTER `id`');
ensureCol($pdo, 'brands', 'user_id', '`user_id` int unsigned DEFAULT NULL AFTER `id`');
ensureCol($pdo, 'job_posts', 'apply_count', '`apply_count` int unsigned NOT NULL DEFAULT 0');

echo "DONE\n";
