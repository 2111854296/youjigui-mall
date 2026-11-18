<?php
/**
 * Add companies.tags + seed from business/area for existing rows.
 * Safe to re-run.
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
    $env['DB_NAME'] ?? ($env['DATABASE_NAME'] ?? 'yjgmall_g')
);
$pdo = new PDO(
    $dsn,
    $env['DB_USER'] ?? $env['DB_USERNAME'] ?? 'root',
    $env['DB_PASS'] ?? $env['DB_PASSWORD'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

function hasColumn(PDO $pdo, string $table, string $col): bool
{
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?'
    );
    $st->execute([$table, $col]);

    return (int) $st->fetchColumn() > 0;
}

if (!hasColumn($pdo, 'companies', 'tags')) {
    $pdo->exec("ALTER TABLE companies ADD COLUMN `tags` VARCHAR(255) NULL COMMENT '名企展示标签，逗号分隔最多4个' AFTER business");
    echo "add tags\n";
} else {
    echo "skip tags\n";
}

$keywordPairs = [
    ['硅胶制品', '/硅胶制品|硅胶产品/u'],
    ['研发生产', '/研发|生产与销售|研发生产/u'],
    ['进出口', '/进出口/u'],
    ['展览展示', '/展览展示|展览/u'],
    ['特装搭建', '/特装|搭建/u'],
    ['展会招展', '/招展|展会/u'],
    ['硫化剂', '/硫化剂/u'],
    ['食品级', '/食品级/u'],
    ['生产销售', '/生产[、，,]?销售/u'],
    ['国内贸易', '/国内贸易/u'],
    ['电子电器', '/电子电器|消费电子/u'],
    ['新能源汽车', '/新能源|汽车/u'],
    ['LED照明', '/LED|照明/u'],
    ['光学填充胶', '/光学填充|填充胶/u'],
    ['导热凝胶', '/导热/u'],
    ['中空辅材', '/中空辅材|中空/u'],
];

$rows = $pdo->query(
    "SELECT id, area, business, tags, scale, business_mode, company_type, vip_level, is_featured, is_certified
     FROM companies WHERE status IN ('approved','published')"
)->fetchAll(PDO::FETCH_ASSOC);

$hasSell = hasColumn($pdo, 'companies', 'sell_products');
$upd = $pdo->prepare(
    'UPDATE companies SET tags=?, business_mode=?, scale=?, vip_level=? WHERE id=?'
);

$seeded = 0;
foreach ($rows as $r) {
    $tags = trim((string) ($r['tags'] ?? ''));
    $list = [];
    if ($tags !== '') {
        $list = array_values(array_filter(array_map('trim', preg_split('/[,|，、;；]+/u', $tags))));
    }
    $raw = trim((string) ($r['business'] ?? ''));
    if ($hasSell) {
        // optional column on some envs
        $st = $pdo->prepare('SELECT sell_products FROM companies WHERE id=?');
        $st->execute([$r['id']]);
        $raw = trim($raw . ' ' . (string) ($st->fetchColumn() ?: ''));
    }
    if (count($list) < 1) {
        $area = trim((string) ($r['area'] ?? ''));
        if ($area !== '') {
            $list[] = mb_strlen($area) > 6 ? mb_substr($area, 0, 6) : $area;
        }
        foreach ($keywordPairs as [$label, $re]) {
            if (count($list) >= 4) {
                break;
            }
            if ($raw !== '' && preg_match($re, $raw) && !in_array($label, $list, true)) {
                $list[] = $label;
            }
        }
        if (count($list) < 4 && $raw !== '') {
            $parts = preg_split('/[、，,；;·|\\/｜\\s]+/u', $raw) ?: [];
            foreach ($parts as $p) {
                $p = trim($p);
                if ($p === '' || mb_strlen($p) < 2 || mb_strlen($p) > 6) {
                    continue;
                }
                if (preg_match('/^[A-Za-z0-9._-]+$/', $p)) {
                    continue;
                }
                if (preg_match('/\\d/', $p) && preg_match('/[A-Za-z]/', $p)) {
                    continue;
                }
                if (!preg_match('/[\\x{4e00}-\\x{9fff}]/u', $p)) {
                    continue;
                }
                if (!in_array($p, $list, true)) {
                    $list[] = $p;
                }
                if (count($list) >= 4) {
                    break;
                }
            }
        }
    }
    $list = array_slice($list, 0, 4);
    $tagStr = implode(',', $list);

    $mode = trim((string) ($r['business_mode'] ?? ''));
    if ($mode === '' && $raw !== '') {
        if (preg_match('/贸易|经销|代理|进出口/u', $raw)) {
            $mode = '贸易商';
        } elseif (preg_match('/服务|物流|检测|展览|搭建|会议/u', $raw)) {
            $mode = '服务商';
        } elseif (preg_match('/生产|制造|研发/u', $raw)) {
            $mode = '制造商';
        }
    }

    $scale = trim((string) ($r['scale'] ?? ''));
    // scale left empty if unknown — do not invent

    $vip = (int) ($r['vip_level'] ?? 0);
    if ($vip <= 0) {
        if (!empty($r['is_certified'])) {
            $vip = 5;
        } elseif (!empty($r['is_featured'])) {
            $vip = 3;
        }
    }

    $upd->execute([
        $tagStr !== '' ? $tagStr : null,
        $mode !== '' ? $mode : null,
        $scale !== '' ? $scale : null,
        $vip,
        $r['id'],
    ]);
    $seeded++;
}

echo "seeded_rows=$seeded\n";
echo "DONE\n";
