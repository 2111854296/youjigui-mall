<?php
/**
 * 同步主流有机硅行情品种（名称 + 排序），共 15 个。
 * 用法：php scripts/sync_quote_catalog.php
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
$pdo = new PDO($dsn, $env['DB_USER'] ?? $env['DB_USERNAME'] ?? 'root', $env['DB_PASS'] ?? $env['DB_PASSWORD'] ?? '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$catalog = [
    ['二甲基环硅氧烷混合物（DMC）', 29800],
    ['八甲基环四硅氧烷（D4）', 29500],
    ['甲基乙烯基硅橡胶（生胶）', 30800],
    ['107室温硫化硅橡胶（107胶）', 28200],
    ['二甲基硅油', 26800],
    ['外资品牌二甲基硅油', 38500],
    ['硅橡胶混炼胶', 24500],
    ['气相法二氧化硅（气相白炭黑）', 22000],
    ['沉淀二氧化硅（沉淀白炭黑）', 6800],
    ['421#金属硅', 12800],
    ['工业硅期货', 9800],
    ['一氯甲烷', 3200],
    ['有机硅助剂', 15600],
    ['裂解料二甲基硅油', 18500],
    ['废硅胶毛边', 4200],
];

$aliases = [
    'DMC' => '二甲基环硅氧烷混合物（DMC）',
    'D4' => '八甲基环四硅氧烷（D4）',
    '生胶' => '甲基乙烯基硅橡胶（生胶）',
    '基乙烯基硅橡胶（生胶）' => '甲基乙烯基硅橡胶（生胶）',
    '107胶' => '107室温硫化硅橡胶（107胶）',
    '气相白炭黑' => '气相法二氧化硅（气相白炭黑）',
    '沉淀白炭黑' => '沉淀二氧化硅（沉淀白炭黑）',
];

$today = (new DateTimeImmutable('today'))->format('Y-m-d');

// rename aliases to canonical names
$updName = $pdo->prepare('UPDATE quote_products SET name = ? WHERE name = ?');
foreach ($aliases as $from => $to) {
    $updName->execute([$to, $from]);
}

$find = $pdo->prepare('SELECT id FROM quote_products WHERE name = ? LIMIT 1');
$ins = $pdo->prepare(
    'INSERT INTO quote_products (name, unit, price_avg, price_min, price_max, market, quoted_on, sort, is_active, created_at, updated_at)
     VALUES (?,?,?,?,?,?,?,?,1,NOW(),NOW())'
);
$upd = $pdo->prepare(
    'UPDATE quote_products
     SET unit=?, price_avg=COALESCE(price_avg,?), price_min=COALESCE(price_min,?), price_max=COALESCE(price_max,?),
         market=COALESCE(NULLIF(market,""),?), quoted_on=COALESCE(quoted_on,?), sort=?, is_active=1, updated_at=NOW()
     WHERE id=?'
);

$keepIds = [];
foreach ($catalog as $i => [$name, $avg]) {
    $sort = $i + 1;
    $min = round($avg * 0.985, 2);
    $max = round($avg * 1.015, 2);
    $find->execute([$name]);
    $id = $find->fetchColumn();
    if ($id) {
        $upd->execute(['元/吨', $avg, $min, $max, '华东', $today, $sort, $id]);
        $keepIds[] = (int) $id;
        echo "upd #{$id} sort={$sort} {$name}\n";
    } else {
        $ins->execute([$name, '元/吨', $avg, $min, $max, '华东', $today, $sort]);
        $newId = (int) $pdo->lastInsertId();
        $keepIds[] = $newId;
        echo "ins #{$newId} sort={$sort} {$name}\n";
    }
}

if ($keepIds) {
    $in = implode(',', array_map('intval', $keepIds));
    $n = $pdo->exec("UPDATE quote_products SET is_active=0, updated_at=NOW() WHERE id NOT IN ({$in}) AND is_active=1");
    echo "deactivated extras: {$n}\n";
}

echo "DONE catalog=" . count($catalog) . "\n";
