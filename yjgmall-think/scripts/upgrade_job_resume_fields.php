<?php
/**
 * Resume is_default + ensure open column semantics for talent pool.
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

if (!colExists($pdo, 'resumes', 'job_category')) {
    $pdo->exec("ALTER TABLE `resumes` ADD COLUMN `job_category` varchar(80) NULL DEFAULT NULL AFTER `area`");
    echo "ADD resumes.job_category\n";
} else {
    echo "OK resumes.job_category\n";
}
if (!colExists($pdo, 'resumes', 'job_role')) {
    $pdo->exec("ALTER TABLE `resumes` ADD COLUMN `job_role` varchar(32) NULL DEFAULT NULL COMMENT '职位职能：管理/研发/销售等' AFTER `job_category`");
    echo "ADD resumes.job_role\n";
} else {
    echo "OK resumes.job_role\n";
}
if (!colExists($pdo, 'resumes', 'is_default')) {
    $pdo->exec("ALTER TABLE `resumes` ADD COLUMN `is_default` tinyint(1) NOT NULL DEFAULT 0 AFTER `open`");
    echo "ADD resumes.is_default\n";
} else {
    echo "OK resumes.is_default\n";
}

// 旧简历按标题粗分职能，保证企业招人「职位」筛选有反应
$roleMap = [
    '前台' => ['前台', '接待'],
    '品控' => ['品控', '质检', 'QC', 'QA', '检测'],
    '外贸' => ['外贸', '出口', '跟单', '国际贸易'],
    '销售' => ['销售代表', '销售', '业务员', '客户经理', '商务'],
    '管理' => ['经理', '总监', '主管', '管理', '厂长'],
    '生产' => ['生产', '车间', '操作工', '班长'],
    '文员' => ['文员', '内勤', '助理'],
    '行政' => ['行政', '人事', 'HR', '后勤'],
    '研发' => ['研发', '配方', '研究员', '工艺', '工程师', '技术'],
];
if (colExists($pdo, 'resumes', 'job_role')) {
    $rows = $pdo->query("SELECT id, title, job_role FROM resumes WHERE job_role IS NULL OR job_role='' OR job_role='其他'")->fetchAll(PDO::FETCH_ASSOC);
    $upd = $pdo->prepare('UPDATE resumes SET job_role=? WHERE id=?');
    $n = 0;
    foreach ($rows as $row) {
        $title = (string) ($row['title'] ?? '');
        $hit = '其他';
        foreach ($roleMap as $role => $keys) {
            foreach ($keys as $k) {
                if ($k !== '' && mb_stripos($title, $k) !== false) {
                    $hit = $role;
                    break 2;
                }
            }
        }
        if ($hit === (string) ($row['job_role'] ?? '')) {
            continue;
        }
        $upd->execute([$hit, $row['id']]);
        $n++;
    }
    echo "BACKFILL resumes.job_role {$n}\n";
}

// Normalize open values: 1/0 -> public/private
try {
    $pdo->exec("UPDATE resumes SET open='public' WHERE open IS NULL OR open='' OR open='1' OR open=1");
    $pdo->exec("UPDATE resumes SET open='private' WHERE open='0' OR open=0 OR open='hide' OR open='hidden'");
    echo "NORMALIZE resumes.open\n";
} catch (Throwable $e) {
    echo "SKIP open normalize: " . $e->getMessage() . "\n";
}

echo "JOB_RESUME_FIELDS_OK\n";
