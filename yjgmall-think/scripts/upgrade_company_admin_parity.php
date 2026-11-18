<?php
/**
 * Company admin Destoon parity fields + company_honors lifecycle.
 * Safe to re-run.
 */
$envFile = __DIR__ . '/../.env';
if (!is_file($envFile)) {
    $envFile = '/www/wwwroot/g.aifeili.cn/.env';
}
$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if ($line === '' || ($line[0] ?? '') === '#' || !str_contains($line, '=')) continue;
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

function hasColumn(PDO $pdo, string $table, string $col): bool
{
    $st = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
    $st->execute([$table, $col]);
    return (int) $st->fetchColumn() > 0;
}

function ensureTable(PDO $pdo, string $sql): void
{
    $pdo->exec($sql);
}

$companyCols = [
    'reg_year' => "SMALLINT UNSIGNED NULL COMMENT '成立年份' AFTER capital",
    'zip' => "VARCHAR(16) NULL COMMENT '邮编' AFTER address",
    'fax' => "VARCHAR(64) NULL COMMENT '传真' AFTER phone",
    'email' => "VARCHAR(120) NULL COMMENT '企业邮箱' AFTER fax",
    'sell_products' => "VARCHAR(500) NULL COMMENT '销售产品|分隔' AFTER business",
    'buy_products' => "VARCHAR(500) NULL COMMENT '采购产品|分隔' AFTER sell_products",
    'capital_unit' => "VARCHAR(16) NOT NULL DEFAULT '人民币' COMMENT '注册资本单位' AFTER capital",
    'vip_theory' => "TINYINT NOT NULL DEFAULT 0 COMMENT 'VIP理论/赠送值' AFTER vip_level",
    'vip_correct' => "TINYINT NOT NULL DEFAULT 0 COMMENT 'VIP修正值' AFTER vip_theory",
    'vip_from' => "DATETIME NULL COMMENT 'VIP开通时间' AFTER vip_correct",
    'views' => "INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '人气' AFTER vip_from",
];

foreach ($companyCols as $name => $ddl) {
    if (hasColumn($pdo, 'companies', $name)) {
        echo "skip companies.$name\n";
        continue;
    }
    $pdo->exec("ALTER TABLE companies ADD COLUMN `$name` $ddl");
    echo "add companies.$name\n";
}

ensureTable($pdo, "CREATE TABLE IF NOT EXISTS company_honors (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id INT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  image VARCHAR(500) NULL,
  authority VARCHAR(255) NULL,
  issued_at DATE NULL,
  expires_at DATE NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'approved',
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  KEY idx_company(company_id),
  KEY idx_status(status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$honorCols = [
    'expires_at' => "DATE NULL COMMENT '到期日期空=永久' AFTER issued_at",
    'status' => "VARCHAR(32) NOT NULL DEFAULT 'approved' COMMENT 'pending|approved|rejected|expired|offline' AFTER expires_at",
];
foreach ($honorCols as $name => $ddl) {
    if (hasColumn($pdo, 'company_honors', $name)) {
        echo "skip company_honors.$name\n";
        continue;
    }
    $pdo->exec("ALTER TABLE company_honors ADD COLUMN `$name` $ddl");
    echo "add company_honors.$name\n";
}

// widen business_mode for multi values
try {
    $pdo->exec("ALTER TABLE companies MODIFY COLUMN business_mode VARCHAR(64) NULL COMMENT '经营模式可多选逗号'");
    echo "widen business_mode\n";
} catch (Throwable $e) {
    echo "business_mode widen skip: ".$e->getMessage()."\n";
}

$pdo->exec("UPDATE company_honors SET status='approved' WHERE status IS NULL OR status=''");
echo "DONE\n";
