<?php
/**
 * Ensure chat_sessions / chat_messages schema matches MemberParity API.
 * Run on server: php scripts/upgrade_chat_tables.php
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
        echo "skip ensureCol $table.$col (no table)\n";
        return;
    }
    if (in_array($col, $c, true)) {
        echo "ok $table.$col\n";
        return;
    }
    $pdo->exec("ALTER TABLE `$table` ADD COLUMN $ddl");
    echo "added $table.$col\n";
}

$pdo->exec("CREATE TABLE IF NOT EXISTS chat_sessions (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `peer_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_peer` (`peer_id`),
  KEY `idx_pair` (`user_id`, `peer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "chat_sessions ok\n";

$pdo->exec("CREATE TABLE IF NOT EXISTS chat_messages (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id` BIGINT UNSIGNED NULL,
  `from_user_id` INT UNSIGNED NOT NULL,
  `to_user_id` INT UNSIGNED NOT NULL,
  `content` TEXT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_session` (`session_id`),
  KEY `idx_to_unread` (`to_user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "chat_messages ok\n";

ensureCol($pdo, 'chat_messages', 'session_id', '`session_id` BIGINT UNSIGNED NULL AFTER `id`');
ensureCol($pdo, 'chat_messages', 'from_user_id', '`from_user_id` INT UNSIGNED NOT NULL DEFAULT 0');
ensureCol($pdo, 'chat_messages', 'to_user_id', '`to_user_id` INT UNSIGNED NOT NULL DEFAULT 0');
ensureCol($pdo, 'chat_messages', 'content', '`content` TEXT NULL');
ensureCol($pdo, 'chat_messages', 'is_read', '`is_read` TINYINT(1) NOT NULL DEFAULT 0');
ensureCol($pdo, 'chat_messages', 'created_at', '`created_at` DATETIME NULL');
ensureCol($pdo, 'chat_messages', 'updated_at', '`updated_at` DATETIME NULL');

ensureCol($pdo, 'chat_sessions', 'user_id', '`user_id` INT UNSIGNED NOT NULL DEFAULT 0');
ensureCol($pdo, 'chat_sessions', 'peer_id', '`peer_id` INT UNSIGNED NOT NULL DEFAULT 0');
ensureCol($pdo, 'chat_sessions', 'created_at', '`created_at` DATETIME NULL');
ensureCol($pdo, 'chat_sessions', 'updated_at', '`updated_at` DATETIME NULL');

// 旧库字段是 chat_session_id，代码用 session_id：回填
$cols = cols($pdo, 'chat_messages');
if (in_array('chat_session_id', $cols, true) && in_array('session_id', $cols, true)) {
    $n = $pdo->exec('UPDATE chat_messages SET session_id = chat_session_id WHERE (session_id IS NULL OR session_id = 0) AND chat_session_id IS NOT NULL');
    echo "backfill session_id rows={$n}\n";
}

// Also ensure other MemberParity tables exist (common 500 sources)
$pdo->exec("CREATE TABLE IF NOT EXISTS trade_alerts (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `keyword` VARCHAR(128) NOT NULL,
  `module` VARCHAR(32) NOT NULL DEFAULT 'sell',
  `area` VARCHAR(64) NULL,
  `price_min` DECIMAL(12,2) NULL,
  `price_max` DECIMAL(12,2) NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'active',
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "trade_alerts ok\n";

$pdo->exec("CREATE TABLE IF NOT EXISTS mail_subscriptions (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `modules` JSON NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "mail_subscriptions ok\n";

$pdo->exec("CREATE TABLE IF NOT EXISTS ask_tickets (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NULL,
  `contact_name` VARCHAR(64) NULL,
  `contact_phone` VARCHAR(64) NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'pending',
  `reply` TEXT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "ask_tickets ok\n";

echo "DONE\n";
echo "chat_messages columns: " . implode(',', cols($pdo, 'chat_messages')) . "\n";
echo "chat_sessions columns: " . implode(',', cols($pdo, 'chat_sessions')) . "\n";
