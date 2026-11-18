<?php
/**
 * Add filterable columns on job_posts; backfill salary_min/max from salary text.
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

$st = $pdo->query("SHOW TABLES LIKE 'job_posts'");
if (!$st || !$st->fetch()) {
    echo "SKIP no job_posts\n";
    exit(0);
}

ensureCol($pdo, 'job_posts', 'job_category', "`job_category` varchar(64) NULL DEFAULT NULL COMMENT '领域分类'");
ensureCol($pdo, 'job_posts', 'job_role', "`job_role` varchar(32) NULL DEFAULT NULL COMMENT '职位职能：管理/研发/销售等'");
ensureCol($pdo, 'job_posts', 'job_nature', "`job_nature` varchar(32) NULL DEFAULT '全职' COMMENT '全职/兼职/实习'");
ensureCol($pdo, 'job_posts', 'company_size', "`company_size` varchar(32) NULL DEFAULT NULL COMMENT '企业规模'");
ensureCol($pdo, 'job_posts', 'salary_min', "`salary_min` int NULL DEFAULT NULL COMMENT '月薪下限(K)'");
ensureCol($pdo, 'job_posts', 'salary_max', "`salary_max` int NULL DEFAULT NULL COMMENT '月薪上限(K)'");

// 旧数据按标题粗分职能；专属词优先，避免「质检工程师」被「工程师」归到研发
$roleMap = [
    '前台' => ['前台', '接待'],
    '品控' => ['品控', '质检', 'QC', 'QA', '检测'],
    '外贸' => ['外贸', '出口', '跟单', '国际贸易'],
    '销售' => ['销售代表', '销售', '业务员', '客户经理', '商务'],
    '管理' => ['经理', '总监', '主管', '管理', '厂长', '老板'],
    '生产' => ['生产', '车间', '操作工', '班长'],
    '文员' => ['文员', '内勤', '助理'],
    '行政' => ['行政', '人事', 'HR', '后勤'],
    '研发' => ['研发', '配方', '研究员', '工艺', '工程师', '技术'],
];
if (colExists($pdo, 'job_posts', 'job_role')) {
    $force = in_array('--force-role', $argv ?? [], true);
    $sql = $force
        ? 'SELECT id, title, job_role FROM job_posts'
        : "SELECT id, title, job_role FROM job_posts WHERE job_role IS NULL OR job_role='' OR job_role='其他'";
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $upd = $pdo->prepare('UPDATE job_posts SET job_role=? WHERE id=?');
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
    echo "BACKFILL job_role {$n}\n";
}

function parseSalaryK(string $raw): array
{
    $s = trim($raw);
    if ($s === '') {
        return [null, null];
    }
    if (preg_match('/(\d+(?:\.\d+)?)\s*[-~～到至]\s*(\d+(?:\.\d+)?)/u', $s, $m)) {
        $a = (float) $m[1];
        $b = (float) $m[2];
        if (preg_match('/万/u', $s)) {
            $a *= 10;
            $b *= 10;
        } elseif (!preg_match('/[kK千]/u', $s) && $a >= 1000) {
            $a = round($a / 1000);
            $b = round($b / 1000);
        }
        return [(int) round($a), (int) round($b)];
    }
    if (preg_match('/(\d+(?:\.\d+)?)/u', $s, $m)) {
        $n = (float) $m[1];
        if (preg_match('/万/u', $s)) {
            $n *= 10;
        } elseif (!preg_match('/[kK千]/u', $s) && $n >= 1000) {
            $n = round($n / 1000);
        }
        return [(int) round($n), (int) round($n)];
    }
    return [null, null];
}

$rows = $pdo->query('SELECT id, salary, salary_min, salary_max FROM job_posts')->fetchAll(PDO::FETCH_ASSOC);
$upd = $pdo->prepare('UPDATE job_posts SET salary_min=?, salary_max=? WHERE id=?');
$n = 0;
foreach ($rows as $row) {
    if ($row['salary_min'] !== null && $row['salary_max'] !== null) {
        continue;
    }
    [$min, $max] = parseSalaryK((string) ($row['salary'] ?? ''));
    if ($min === null && $max === null) {
        continue;
    }
    $upd->execute([$min, $max, $row['id']]);
    $n++;
}
echo "BACKFILL salary {$n}\n";
echo "JOB_FILTER_FIELDS_OK\n";
