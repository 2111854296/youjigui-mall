<?php
/**
 * VIP phase-2: seats, invite codes, job quotas, contact packs.
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

ensureCol($pdo, 'users', 'vip_owner_id', '`vip_owner_id` bigint unsigned NULL DEFAULT NULL AFTER `vip_grade_code`');
ensureCol($pdo, 'users', 'invite_code', '`invite_code` varchar(16) NULL DEFAULT NULL AFTER `vip_owner_id`');
ensureCol($pdo, 'users', 'invited_by_user_id', '`invited_by_user_id` bigint unsigned NULL DEFAULT NULL AFTER `invite_code`');
ensureCol($pdo, 'users', 'job_sticky_used_month', '`job_sticky_used_month` varchar(7) NULL DEFAULT NULL AFTER `contact_bonus_expire`');
ensureCol($pdo, 'users', 'job_sticky_used_count', '`job_sticky_used_count` int NOT NULL DEFAULT 0 AFTER `job_sticky_used_month`');

$st = $pdo->query("SHOW TABLES LIKE 'job_posts'");
if ($st && $st->fetch()) {
    ensureCol($pdo, 'job_posts', 'is_sticky', '`is_sticky` tinyint(1) NOT NULL DEFAULT 0');
    ensureCol($pdo, 'job_posts', 'sticky_until', '`sticky_until` datetime NULL DEFAULT NULL');
} else {
    echo "SKIP job_posts sticky\n";
}

try {
    $pdo->exec('CREATE UNIQUE INDEX uniq_users_invite_code ON users (invite_code)');
    echo "INDEX invite_code\n";
} catch (Throwable $e) {
    echo "OK invite_code index\n";
}

$pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS `vip_seats` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `owner_user_id` bigint unsigned NOT NULL,
  `member_user_id` bigint unsigned NULL,
  `mobile` varchar(30) NULL,
  `email` varchar(120) NULL,
  `invite_token` varchar(64) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `accepted_at` datetime NULL,
  `revoked_at` datetime NULL,
  `created_at` datetime NULL,
  `updated_at` datetime NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_invite_token` (`invite_token`),
  KEY `idx_owner` (`owner_user_id`),
  KEY `idx_member` (`member_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);
echo "OK vip_seats\n";

$pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS `vip_invite_rewards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `inviter_user_id` bigint unsigned NOT NULL,
  `invitee_user_id` bigint unsigned NOT NULL,
  `reward_type` varchar(30) NOT NULL,
  `days_granted` int NOT NULL DEFAULT 0,
  `note` varchar(255) NULL,
  `created_at` datetime NULL,
  `updated_at` datetime NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_reward` (`inviter_user_id`,`invitee_user_id`,`reward_type`),
  KEY `idx_inviter` (`inviter_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);
echo "OK vip_invite_rewards\n";

$pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS `vip_contact_packs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL,
  `name` varchar(80) NOT NULL,
  `times` int unsigned NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `sort` int NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NULL,
  `updated_at` datetime NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_pack_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);
echo "OK vip_contact_packs\n";

$pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS `vip_contact_pack_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `pack_id` bigint unsigned NOT NULL,
  `times` int unsigned NOT NULL,
  `list_price` decimal(12,2) NOT NULL,
  `pay_price` decimal(12,2) NOT NULL,
  `discount` decimal(4,2) NOT NULL DEFAULT 1.00,
  `payment_method` varchar(30) NOT NULL DEFAULT 'balance',
  `status` varchar(20) NOT NULL DEFAULT 'paid',
  `created_at` datetime NULL,
  `updated_at` datetime NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);
echo "OK vip_contact_pack_orders\n";

$now = date('Y-m-d H:i:s');
$packs = [
    ['code' => 'pack2', 'name' => '联系方式 2 次包', 'times' => 2, 'price' => 100, 'sort' => 10],
    ['code' => 'pack10', 'name' => '联系方式 10 次包', 'times' => 10, 'price' => 500, 'sort' => 20],
];
$sel = $pdo->prepare('SELECT id FROM vip_contact_packs WHERE code=?');
$ins = $pdo->prepare('INSERT INTO vip_contact_packs (code,name,times,price,sort,is_active,created_at,updated_at) VALUES (?,?,?,?,?,1,?,?)');
$upd = $pdo->prepare('UPDATE vip_contact_packs SET name=?, times=?, price=?, sort=?, is_active=1, updated_at=? WHERE code=?');
foreach ($packs as $p) {
    $sel->execute([$p['code']]);
    if ($sel->fetch()) {
        $upd->execute([$p['name'], $p['times'], $p['price'], $p['sort'], $now, $p['code']]);
        echo "UPDATE pack {$p['code']}\n";
    } else {
        $ins->execute([$p['code'], $p['name'], $p['times'], $p['price'], $p['sort'], $now, $now]);
        echo "INSERT pack {$p['code']}\n";
    }
}

// Patch grade limits with job quotas + pack discounts + invite flags
$jobLimits = [
    'free' => ['resume_contact_quota_month' => 1, 'job_sticky_per_month' => 0, 'pack_discount' => 1.0, 'job_module_visible' => false],
    'silver' => ['resume_contact_quota_month' => 10, 'job_sticky_per_month' => 0, 'pack_discount' => 0.9, 'job_module_visible' => false],
    'gold' => ['resume_contact_quota_month' => 25, 'job_sticky_per_month' => 1, 'pack_discount' => 0.8, 'job_module_visible' => false],
    'diamond' => ['resume_contact_quota_month' => 50, 'job_sticky_per_month' => 3, 'pack_discount' => 0.7, 'job_module_visible' => false],
];
$gst = $pdo->query('SELECT id, code, limits FROM vip_grades');
$gupd = $pdo->prepare('UPDATE vip_grades SET limits=?, updated_at=? WHERE id=?');
while ($row = $gst->fetch(PDO::FETCH_ASSOC)) {
    $code = $row['code'];
    if (!isset($jobLimits[$code])) {
        continue;
    }
    $lim = json_decode((string) $row['limits'], true) ?: [];
    $lim = array_merge($lim, $jobLimits[$code]);
    $gupd->execute([json_encode($lim, JSON_UNESCAPED_UNICODE), $now, $row['id']]);
    echo "PATCH limits {$code}\n";
}

// Backfill invite codes for existing users missing one
$users = $pdo->query("SELECT id FROM users WHERE invite_code IS NULL OR invite_code=''");
$uset = $pdo->prepare('UPDATE users SET invite_code=? WHERE id=?');
while ($u = $users->fetch(PDO::FETCH_ASSOC)) {
    $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    try {
        $uset->execute([$code, $u['id']]);
    } catch (Throwable $e) {
        $code = strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
        $uset->execute([$code, $u['id']]);
    }
}
echo "BACKFILL invite_codes\n";
echo "VIP_PHASE2_OK\n";
