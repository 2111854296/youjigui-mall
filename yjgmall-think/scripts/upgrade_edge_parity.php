<?php
/**
 * Password reset codes + download perms + special items.
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
CREATE TABLE IF NOT EXISTS `verification_codes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `channel` varchar(20) NOT NULL DEFAULT 'reset',
  `account` varchar(120) NOT NULL,
  `user_id` int unsigned DEFAULT NULL,
  `code_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_account_channel` (`account`,`channel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);
echo "OK verification_codes\n";

$pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS `special_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `special_id` int unsigned NOT NULL,
  `item_type` varchar(32) NOT NULL DEFAULT 'article',
  `item_id` int unsigned DEFAULT NULL,
  `title` varchar(200) NOT NULL DEFAULT '',
  `thumb` varchar(500) DEFAULT NULL,
  `summary` varchar(500) DEFAULT NULL,
  `link` varchar(500) DEFAULT NULL,
  `sort` int NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'published',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_special` (`special_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);
echo "OK special_items\n";

$pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS `download_logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `download_id` int unsigned NOT NULL,
  `user_id` int unsigned DEFAULT NULL,
  `points` int NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_dl_user` (`download_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);
echo "OK download_logs\n";

ensureCol($pdo, 'downloads', 'points_cost', '`points_cost` int NOT NULL DEFAULT 0');
ensureCol($pdo, 'downloads', 'require_login', '`require_login` tinyint(1) NOT NULL DEFAULT 0');
ensureCol($pdo, 'downloads', 'min_group', "`min_group` varchar(32) NOT NULL DEFAULT 'guest'");
ensureCol($pdo, 'orders', 'refund_reason', '`refund_reason` varchar(500) DEFAULT NULL');
ensureCol($pdo, 'group_orders', 'refund_reason', '`refund_reason` varchar(500) DEFAULT NULL');

echo "DONE\n";
