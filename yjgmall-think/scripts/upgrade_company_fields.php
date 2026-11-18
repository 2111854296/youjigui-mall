<?php
/**
 * One-shot schema upgrade for company advanced filters + shop banner/credit fields.
 * Safe to re-run (checks information_schema).
 */
$envFile = __DIR__ . '/../.env';
if (!is_file($envFile)) {
    $envFile = '/www/wwwroot/g.aifeili.cn/.env';
}
$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if ($line[0] === '#' || !str_contains($line, '=')) continue;
    [$k, $v] = array_map('trim', explode('=', $line, 2));
    $env[$k] = trim($v, "\"'");
}

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $env['DB_HOST'] ?? '127.0.0.1',
    $env['DB_PORT'] ?? '3306',
    $env['DB_NAME'] ?? ($env['DATABASE_NAME'] ?? 'yjgmall_g')
);
$pdo = new PDO($dsn, $env['DB_USER'] ?? $env['DB_USERNAME'] ?? 'root', $env['DB_PASS'] ?? $env['DB_PASSWORD'] ?? '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

function hasColumn(PDO $pdo, string $table, string $col): bool {
    $st = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
    $st->execute([$table, $col]);
    return (int) $st->fetchColumn() > 0;
}

$cols = [
    'company_type' => "VARCHAR(32) NULL COMMENT '企业类型' AFTER business",
    'business_mode' => "VARCHAR(32) NULL COMMENT '经营模式' AFTER company_type",
    'scale' => "VARCHAR(32) NULL COMMENT '人员规模' AFTER business_mode",
    'capital' => "VARCHAR(64) NULL COMMENT '注册资本' AFTER scale",
    'vip_level' => "TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'VIP指数0-10' AFTER is_certified",
    'banner' => "VARCHAR(500) NULL COMMENT '旺铺横幅' AFTER logo",
];

foreach ($cols as $name => $ddl) {
    if (hasColumn($pdo, 'companies', $name)) {
        echo "skip $name\n";
        continue;
    }
    $pdo->exec("ALTER TABLE companies ADD COLUMN `$name` $ddl");
    echo "add $name\n";
}

// seed demo values for existing approved companies
$pdo->exec("UPDATE companies SET company_type=COALESCE(NULLIF(company_type,''),'企业单位'), business_mode=COALESCE(NULLIF(business_mode,''),'制造商'), scale=COALESCE(NULLIF(scale,''),'50-99人'), capital=COALESCE(NULLIF(capital,''),'500万人民币'), vip_level=IF(vip_level>0,vip_level,IF(is_certified=1,5,IF(is_featured=1,3,1))) WHERE status='approved'");
echo "seeded\n";
echo "DONE\n";
