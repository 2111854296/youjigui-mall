<?php
/**
 * VIP entitlement: limits column, user grade fields, unlock/quota tables, 4-tier seed.
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

function tableExists(PDO $pdo, string $table): bool
{
    $st = $pdo->prepare('SHOW TABLES LIKE ?');
    $st->execute([$table]);

    return (bool) $st->fetch();
}

// ---- vip_grades ----
if (!tableExists($pdo, 'vip_grades')) {
    $pdo->exec(<<<SQL
CREATE TABLE `vip_grades` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price_year` decimal(12,2) NOT NULL DEFAULT 0,
  `level` tinyint unsigned NOT NULL DEFAULT 0,
  `perks` text,
  `limits` json DEFAULT NULL,
  `sort` int unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vip_grades_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);
    echo "CREATE vip_grades\n";
} else {
    echo "OK vip_grades\n";
    ensureCol($pdo, 'vip_grades', 'limits', '`limits` json DEFAULT NULL AFTER `perks`');
}

if (!tableExists($pdo, 'vip_applications')) {
    $pdo->exec(<<<SQL
CREATE TABLE `vip_applications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `vip_grade_id` bigint unsigned NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `contact_name` varchar(100) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `mobile` varchar(30) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `qq` varchar(30) DEFAULT NULL,
  `remark` text,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_vip_app_user` (`user_id`),
  KEY `idx_vip_app_grade` (`vip_grade_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);
    echo "CREATE vip_applications\n";
} else {
    echo "OK vip_applications\n";
    ensureCol($pdo, 'vip_applications', 'vip_grade_id', '`vip_grade_id` bigint unsigned NULL AFTER `user_id`');
    ensureCol($pdo, 'vip_applications', 'contact_name', '`contact_name` varchar(100) NULL AFTER `company_name`');
    ensureCol($pdo, 'vip_applications', 'mobile', '`mobile` varchar(30) NULL AFTER `phone`');
    ensureCol($pdo, 'vip_applications', 'email', '`email` varchar(120) NULL AFTER `mobile`');
    ensureCol($pdo, 'vip_applications', 'qq', '`qq` varchar(30) NULL AFTER `email`');
    ensureCol($pdo, 'vip_applications', 'remark', '`remark` text NULL AFTER `qq`');
}

ensureCol($pdo, 'users', 'vip_grade_code', "`vip_grade_code` varchar(30) NULL DEFAULT NULL AFTER `level`");
ensureCol($pdo, 'users', 'contact_bonus_left', '`contact_bonus_left` int NOT NULL DEFAULT 0 AFTER `vip_expire_at`');
ensureCol($pdo, 'users', 'contact_bonus_expire', '`contact_bonus_expire` datetime NULL DEFAULT NULL AFTER `contact_bonus_left`');

$pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS `vip_contact_unlocks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_user_id` bigint unsigned NOT NULL,
  `target_type` varchar(32) NOT NULL,
  `target_id` bigint unsigned NOT NULL,
  `unlocked_by` bigint unsigned NOT NULL,
  `month_key` varchar(7) NOT NULL,
  `contact_payload` json DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_company_target` (`company_user_id`,`target_type`,`target_id`),
  KEY `idx_month` (`company_user_id`,`month_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);
echo "OK vip_contact_unlocks\n";

$pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS `vip_quota_usage` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_user_id` bigint unsigned NOT NULL,
  `quota_type` varchar(32) NOT NULL DEFAULT 'contact',
  `month_key` varchar(7) NOT NULL,
  `used` int unsigned NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_quota` (`company_user_id`,`quota_type`,`month_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);
echo "OK vip_quota_usage\n";

$grades = [
    [
        'code' => 'free',
        'name' => '普通企业会员',
        'price_year' => 0,
        'level' => 0,
        'sort' => 10,
        'limits' => [
            'contact_quota_month' => 0,
            'quote_history_days' => 7,
            'exposure_weight' => 0,
            'badge' => '',
            'top_price' => 1000,
            'seat_count' => 1,
            'cert_bonus_contacts' => 5,
            'cert_bonus_days' => 30,
            'matrix' => [
                'quote_card' => true,
                'news_browse' => true,
                'buy_list' => true,
                'publish_unlimited' => true,
                'share_unlock' => true,
                'industry_report' => false,
                'supplier_recommend' => false,
                'exclusive_cs' => false,
            ],
        ],
        'perks' => [
            '实时报价 / 今日行情',
            '行业资讯浏览',
            '行情深度：近7天（不可导出）',
            '查看采购需求标题',
            '发布信息不限（曝光：基础）',
            '首次企业认证赠送联系方式查看 5 次（30天有效）',
        ],
    ],
    [
        'code' => 'silver',
        'name' => '白银企业会员',
        'price_year' => 1980,
        'level' => 1,
        'sort' => 20,
        'limits' => [
            'contact_quota_month' => 15,
            'quote_history_days' => 365,
            'exposure_weight' => 10,
            'badge' => '白银',
            'top_price' => 500,
            'seat_count' => 1,
            'matrix' => [
                'quote_card' => true,
                'news_browse' => true,
                'buy_list' => true,
                'publish_unlimited' => true,
                'share_unlock' => true,
                'industry_report' => false,
                'supplier_recommend' => false,
                'exclusive_cs' => false,
            ],
        ],
        'perks' => [
            '采购联系方式 15 次/月',
            '行情深度：近1年',
            '信息曝光：标准加权',
            '置顶加急 500元/条',
            '白银会员标识',
        ],
    ],
    [
        'code' => 'gold',
        'name' => '黄金企业会员',
        'price_year' => 3980,
        'level' => 2,
        'sort' => 30,
        'limits' => [
            'contact_quota_month' => 30,
            'quote_history_days' => 730,
            'exposure_weight' => 20,
            'badge' => '黄金',
            'top_price' => 300,
            'seat_count' => 2,
            'matrix' => [
                'quote_card' => true,
                'news_browse' => true,
                'buy_list' => true,
                'publish_unlimited' => true,
                'share_unlock' => true,
                'industry_report' => true,
                'supplier_recommend' => false,
                'exclusive_cs' => false,
            ],
        ],
        'perks' => [
            '采购联系方式 30 次/月',
            '行情深度：近2年',
            '信息曝光：进阶加权',
            '置顶加急 300元/条',
            '行业报告可读',
            '黄金会员标识',
        ],
    ],
    [
        'code' => 'diamond',
        'name' => '星钻企业会员',
        'price_year' => 6980,
        'level' => 3,
        'sort' => 40,
        'limits' => [
            'contact_quota_month' => 60,
            'quote_history_days' => 1825,
            'exposure_weight' => 30,
            'badge' => '星钻',
            'top_price' => 0,
            'top_gift_per_month' => 1,
            'seat_count' => 3,
            'matrix' => [
                'quote_card' => true,
                'news_browse' => true,
                'buy_list' => true,
                'publish_unlimited' => true,
                'share_unlock' => true,
                'industry_report' => true,
                'supplier_recommend' => true,
                'exclusive_cs' => true,
            ],
        ],
        'perks' => [
            '采购联系方式 60 次/月',
            '行情深度：近5年',
            '信息曝光：优先加权',
            '每月赠送置顶 1 条（3天）',
            '优质供应商推荐',
            '一对一专属客服',
            '星钻会员标识',
        ],
    ],
];

$now = date('Y-m-d H:i:s');
$sel = $pdo->prepare('SELECT id FROM vip_grades WHERE code = ? LIMIT 1');
$ins = $pdo->prepare('INSERT INTO vip_grades (code,name,price_year,level,perks,limits,sort,is_active,created_at,updated_at) VALUES (?,?,?,?,?,?,?,1,?,?)');
$upd = $pdo->prepare('UPDATE vip_grades SET name=?, price_year=?, level=?, perks=?, limits=?, sort=?, is_active=1, updated_at=? WHERE code=?');

foreach ($grades as $g) {
    $perksJson = json_encode($g['perks'], JSON_UNESCAPED_UNICODE);
    $limitsJson = json_encode($g['limits'], JSON_UNESCAPED_UNICODE);
    $sel->execute([$g['code']]);
    $row = $sel->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $upd->execute([
            $g['name'], $g['price_year'], $g['level'], $perksJson, $limitsJson, $g['sort'], $now, $g['code'],
        ]);
        echo "UPDATE grade {$g['code']}\n";
    } else {
        $ins->execute([
            $g['code'], $g['name'], $g['price_year'], $g['level'], $perksJson, $limitsJson, $g['sort'], $now, $now,
        ]);
        echo "INSERT grade {$g['code']}\n";
    }
}

// Deactivate legacy grades not in the rights matrix
$keep = ['free', 'silver', 'gold', 'diamond'];
$in = implode(',', array_map(fn ($c) => $pdo->quote($c), $keep));
$pdo->exec("UPDATE vip_grades SET is_active=0, updated_at={$pdo->quote($now)} WHERE code NOT IN ({$in})");
echo "DEACTIVATE legacy grades\n";

echo "VIP_ENTITLEMENT_OK\n";
