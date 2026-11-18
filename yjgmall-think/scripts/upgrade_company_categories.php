<?php
/**
 * Seed Destoon-like company industry categories (module=company).
 * Distinct from mall/sell floors: 硅烷+硅树脂 merge into 硅烷/硅树脂.
 * Remaps companies.category_id from sell tree → company tree.
 * Safe to re-run.
 */
$envFile = __DIR__ . '/../.env';
if (!is_file($envFile)) {
    $envFile = '/www/wwwroot/g.aifeili.cn/.env';
}
$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
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

$tree = [
    ['name' => '有机硅单体/中间体', 'children' => ['甲基中间体', '甲基单体', '苯基单体', '氟硅单体']],
    ['name' => '硅油', 'children' => ['甲基硅油', '含氢硅油', '苯基硅油', '改性硅油']],
    ['name' => '硅橡胶', 'children' => ['固体胶', '液体胶', '苯基硅橡胶']],
    ['name' => '有机硅助剂/填料', 'children' => ['硅胶助剂', '硅胶色母', '白炭黑', '乙烯基单/双封头']],
    ['name' => '机械设备', 'children' => ['硅橡胶设备', '有机硅全套设备', '有机硅配套设备']],
    ['name' => '硅烷/硅树脂', 'children' => ['硅烷偶联剂', '硅烷交联剂', '硅树脂']],
    ['name' => '胶粘剂', 'children' => ['硅酮密封胶', '电子硅胶']],
];

function slugify(string $name, int $n = 0): string
{
    $base = 'co-' . substr(md5($name), 0, 8);
    return $n > 0 ? $base . '-' . $n : $base;
}

function ensureCategory(PDO $pdo, string $name, string $module, ?int $parentId, int $sort): int
{
    $sql = 'SELECT id FROM categories WHERE module=? AND name=? AND ' . ($parentId === null ? 'parent_id IS NULL' : 'parent_id=?') . ' LIMIT 1';
    $st = $pdo->prepare($sql);
    $parentId === null ? $st->execute([$module, $name]) : $st->execute([$module, $name, $parentId]);
    $id = $st->fetchColumn();
    if ($id) {
        $upd = $pdo->prepare('UPDATE categories SET is_active=1, sort=?, parent_id=? WHERE id=?');
        $upd->execute([$sort, $parentId, $id]);
        return (int) $id;
    }
    $slug = slugify($name);
    $n = 0;
    while (true) {
        try {
            $ins = $pdo->prepare('INSERT INTO categories (parent_id, name, slug, sort, module, is_active) VALUES (?,?,?,?,?,1)');
            $ins->execute([$parentId, $name, $slug, $sort, $module]);
            return (int) $pdo->lastInsertId();
        } catch (PDOException $e) {
            if (++$n > 5) throw $e;
            $slug = slugify($name, $n);
        }
    }
}

$companyRoots = []; // name => id
$sort = 1;
foreach ($tree as $root) {
    $rid = ensureCategory($pdo, $root['name'], 'company', null, $sort++);
    $companyRoots[$root['name']] = $rid;
    echo "root {$root['name']} => {$rid}\n";
    $cs = 1;
    foreach ($root['children'] as $child) {
        $cid = ensureCategory($pdo, $child, 'company', $rid, $cs++);
        echo "  child {$child} => {$cid}\n";
    }
}

// Map sell category id → company root id
$sellRoots = $pdo->query("SELECT id, name FROM categories WHERE module='sell' AND parent_id IS NULL")->fetchAll(PDO::FETCH_KEY_PAIR);
$sellToCompany = [];
foreach ($sellRoots as $sid => $sname) {
    if ($sname === '硅烷' || $sname === '硅树脂') {
        $sellToCompany[(int) $sid] = $companyRoots['硅烷/硅树脂'];
    } elseif (isset($companyRoots[$sname])) {
        $sellToCompany[(int) $sid] = $companyRoots[$sname];
    }
}

// Build sell id → sell root id (self or parent chain one level)
$allSell = $pdo->query("SELECT id, parent_id FROM categories WHERE module='sell'")->fetchAll(PDO::FETCH_ASSOC);
$parentOf = [];
foreach ($allSell as $row) {
    $parentOf[(int) $row['id']] = $row['parent_id'] !== null ? (int) $row['parent_id'] : null;
}
function sellRootId(int $id, array $parentOf): int
{
    $seen = [];
    while (isset($parentOf[$id]) && $parentOf[$id] !== null) {
        if (isset($seen[$id])) break;
        $seen[$id] = true;
        $id = $parentOf[$id];
    }
    return $id;
}

$companies = $pdo->query('SELECT id, category_id FROM companies WHERE category_id IS NOT NULL')->fetchAll(PDO::FETCH_ASSOC);
$updated = 0;
$upd = $pdo->prepare('UPDATE companies SET category_id=? WHERE id=?');
foreach ($companies as $co) {
    $cid = (int) $co['category_id'];
    // already on company module?
    $mod = $pdo->prepare('SELECT module FROM categories WHERE id=?');
    $mod->execute([$cid]);
    $module = $mod->fetchColumn();
    if ($module === 'company') {
        continue;
    }
    $root = sellRootId($cid, $parentOf);
    if (!isset($sellToCompany[$root])) {
        continue;
    }
    $newId = $sellToCompany[$root];
    if ($newId === $cid) continue;
    $upd->execute([$newId, $co['id']]);
    $updated++;
}
echo "remapped companies: {$updated}\n";
echo "DONE\n";
