<?php
/**
 * Re-assign scraped mall/company/supply records onto Destoon-like floors
 * by title/business keywords, scrape sell listings, and ensure no empty floors.
 */
$envFile = is_file(__DIR__ . '/../.env') ? __DIR__ . '/../.env' : '/www/wwwroot/g.aifeili.cn/.env';
$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
    [$k, $v] = array_map('trim', explode('=', $line, 2));
    $env[$k] = trim($v, "\"'");
}
$pdo = new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $env['DB_HOST'] ?? '127.0.0.1', $env['DB_PORT'] ?? '3306', $env['DB_NAME'] ?? 'yjgmall_g'
), $env['DB_USER'] ?? 'root', $env['DB_PASS'] ?? $env['DB_PASSWORD'] ?? '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
]);
$public = is_dir('/www/wwwroot/g.aifeili.cn/public') ? '/www/wwwroot/g.aifeili.cn/public' : realpath(__DIR__ . '/../public');
$origin = 'https://www.yjgmall.com';

function http_get(string $url): string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 25, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_USERAGENT => 'Mozilla/5.0 YjgMallFill/1.0', CURLOPT_ENCODING => '',
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($body && $code < 400) ? $body : '';
}
function to_utf8(string $html): string
{
    if (preg_match('/charset\s*=\s*["\']?(gbk|gb2312)/i', substr($html, 0, 800))) {
        $c = @mb_convert_encoding($html, 'UTF-8', 'GBK');
        return $c !== false ? $c : $html;
    }
    return $html;
}
function destoon_rel(string $url): ?string
{
    if (!preg_match('~/file/upload/\d{6}/\d{2}/[^?\s]+\.(?:jpg|jpeg|png|gif|webp)(?:\.(?:thumb|middle)\.(?:jpg|jpeg|png|gif|webp))?~i', $url, $m)) return null;
    return $m[0][0] === '/' ? $m[0] : '/' . $m[0];
}
function download_rel($public, $origin, $rel): void
{
    if (!$rel || str_contains($rel, '092115831')) return;
    $dest = $public . $rel;
    if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
    if (is_file($dest) && filesize($dest) > 600) return;
    $bin = http_get($origin . $rel);
    if ($bin && strlen($bin) > 400 && !str_starts_with(ltrim($bin), '<')) file_put_contents($dest, $bin);
}

function guess_floor(string $text): string
{
    $t = mb_strtolower($text);
    $rules = [
        '机械设备' => ['反应釜', '储罐', '冷凝器', '捏合', '设备', '蒸发器', '硫化机', '搅拌机', '挤出机', '研磨机'],
        '硅树脂' => ['mq树脂', 'mq硅', '硅树脂', '乙烯基mq', '甲基mq'],
        '胶粘剂' => ['密封胶', '灌封', '玻璃胶', '胶粘', '中空玻璃', '结构胶', '耐候胶'],
        '硅烷' => ['硅烷偶联', '硅烷交联', '酮肟', '甲氧基硅烷', '乙酰氧基', '偶联剂', '交联剂', 't-20', 't-30', 't-90'],
        '有机硅助剂/填料' => ['白炭黑', '气相法', '二氧化硅', '色浆', '色母', '封头', '催化剂', '脱模剂', '六甲基二硅', '助剂', '铂金'],
        '硅橡胶' => ['硅橡胶', '混炼胶', '液体硅', '模具硅', '107胶', '生胶', 'lsr', '饰品模具', '人体仿真', 'sa-'],
        '硅油' => ['硅油', '含氢', '羟基硅', '乙烯基硅油', '苯基硅油', '二甲基硅油'],
        '有机硅单体/中间体' => ['dmc', 'd4', 'd5', '环硅氧烷', '氯硅烷', '中间体', '水解物', 'm1', 'm2', 'm3'],
    ];
    foreach ($rules as $name => $kws) {
        foreach ($kws as $kw) {
            if (mb_stripos($t, $kw) !== false) return $name;
        }
    }
    return '';
}

function root_id(PDO $pdo, string $name, array $modules): ?int
{
    foreach ($modules as $mod) {
        $st = $pdo->prepare("SELECT id FROM categories WHERE module=? AND name=? AND parent_id IS NULL AND is_active=1 LIMIT 1");
        $st->execute([$mod, $name]);
        $id = $st->fetchColumn();
        if ($id) return (int) $id;
    }
    if ($name === '硅烷' || $name === '硅树脂') {
        $id = root_id($pdo, '硅烷/硅树脂', ['company']);
        if ($id) return $id;
    }
    return null;
}

function remap_table(PDO $pdo, string $table, string $textSql, array $modules, string $label): int
{
    $rows = $pdo->query($textSql)->fetchAll(PDO::FETCH_ASSOC);
    $upd = $pdo->prepare("UPDATE `$table` SET category_id=? WHERE id=?");
    $n = 0;
    foreach ($rows as $r) {
        $name = guess_floor(($r['title'] ?? '') . ' ' . ($r['name'] ?? '') . ' ' . ($r['business'] ?? '') . ' ' . ($r['intro'] ?? '') . ' ' . ($r['summary'] ?? ''));
        if ($name === '') continue;
        $cid = root_id($pdo, $name, $modules);
        if (!$cid || (int) $r['category_id'] === $cid) continue;
        $upd->execute([$cid, $r['id']]);
        $n++;
        echo "$label {$r['id']} -> $name\n";
    }
    return $n;
}

$nP = remap_table($pdo, 'products',
    "SELECT id, title, category_id, summary FROM products WHERE status='approved'",
    ['sell', 'mall'], 'product');
$nS = remap_table($pdo, 'supplies',
    "SELECT id, title, category_id, summary FROM supplies WHERE status='approved'",
    ['sell'], 'supply');
$nC = remap_table($pdo, 'companies',
    "SELECT id, name, business, intro, category_id FROM companies WHERE status='approved'",
    ['company'], 'company');
echo "remapped products=$nP supplies=$nS companies=$nC\n";

function fill_empty_floors(PDO $pdo, string $table, string $nameCol, array $modules, int $min = 4): void
{
    $mod = $modules[0];
    $roots = $pdo->prepare("SELECT id, name FROM categories WHERE module=? AND parent_id IS NULL AND is_active=1 ORDER BY sort");
    $roots->execute([$mod]);
    $floors = $roots->fetchAll(PDO::FETCH_ASSOC);
    $cntSt = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE status='approved' AND category_id=?");
    $poolSt = $pdo->query("SELECT id, category_id FROM `$table` WHERE status='approved' ORDER BY id DESC");
    $all = $poolSt->fetchAll(PDO::FETCH_ASSOC);
    $upd = $pdo->prepare("UPDATE `$table` SET category_id=? WHERE id=?");
    foreach ($floors as $f) {
        $cntSt->execute([$f['id']]);
        $have = (int) $cntSt->fetchColumn();
        if ($have >= $min) continue;
        $need = $min - $have;
        $moved = 0;
        foreach ($all as $i => $row) {
            if ($moved >= $need) break;
            if ((int) $row['category_id'] === (int) $f['id']) continue;
            // don't drain a floor below $min
            $cntSt->execute([$row['category_id']]);
            $srcHave = (int) $cntSt->fetchColumn();
            if ($srcHave <= $min) continue;
            $upd->execute([$f['id'], $row['id']]);
            $all[$i]['category_id'] = $f['id'];
            $moved++;
            echo "fill {$table} {$row['id']} -> {$f['name']}\n";
        }
    }
}

// ---------- scrape sell ----------
function has_col(PDO $pdo, string $table, string $col): bool
{
    $st = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
    $st->execute([$table, $col]);
    return (int) $st->fetchColumn() > 0;
}
if (!has_col($pdo, 'supplies', 'origin_itemid')) {
    $pdo->exec("ALTER TABLE supplies ADD COLUMN origin_itemid INT UNSIGNED NULL");
    try { $pdo->exec('CREATE UNIQUE INDEX uk_supplies_origin ON supplies (origin_itemid)'); } catch (Throwable $e) {}
}

$userId = (int) $pdo->query("SELECT id FROM users ORDER BY id ASC LIMIT 1")->fetchColumn();
$sellIds = [];
$sellPages = ['/sell/', '/sell/list.php'];
$htmlSell = to_utf8(http_get($origin . '/sell/'));
preg_match_all('/list\.php\?catid=(\d+)/', $htmlSell, $sm);
foreach (array_unique($sm[1] ?? []) as $cid) {
    $sellPages[] = '/sell/list.php?catid=' . $cid;
    $sellPages[] = '/sell/list.php?catid=' . $cid . '&page=2';
}
foreach ($sellPages as $path) {
    $html = ($path === '/sell/') ? $htmlSell : to_utf8(http_get($origin . $path));
    preg_match_all('/show\.php\?itemid=(\d+)/', $html, $mm);
    foreach ($mm[1] as $id) $sellIds[(int) $id] = true;
    usleep(80000);
}
$sellIds = array_keys($sellIds);
echo 'sell items ' . count($sellIds) . "\n";

$findS = $pdo->prepare('SELECT id FROM supplies WHERE origin_itemid=?');
$insS = $pdo->prepare("INSERT INTO supplies (user_id, category_id, title, thumb, price, unit, summary, content, status, is_elite, refreshed_at, origin_itemid)
    VALUES (?,?,?,?,?,?,?,?,'approved',0,NOW(),?)");
$updS = $pdo->prepare("UPDATE supplies SET title=?, thumb=?, price=?, unit=?, summary=?, content=?, category_id=?, status='approved', refreshed_at=NOW() WHERE origin_itemid=?");
$okS = 0;
foreach ($sellIds as $itemid) {
    $html = to_utf8(http_get($origin . '/sell/show.php?itemid=' . $itemid));
    if ($html === '' || !preg_match('/<h1[^>]*>(.*?)<\/h1>/s', $html, $hm)) { usleep(50000); continue; }
    $title = trim(html_entity_decode(strip_tags($hm[1]), ENT_QUOTES, 'UTF-8'));
    if ($title === '' || mb_strlen($title) < 2) continue;
    $price = null;
    if (preg_match('/class="px16"[^>]*>\s*([\d.]+)/', $html, $pm)) $price = (float) $pm[1];
    $thumb = '';
    preg_match_all('~/file/upload/\d{6}/\d{2}/[^"\']+\.(?:jpg|jpeg|png|gif|webp)(?:\.(?:thumb|middle)\.(?:jpg|jpeg|png|gif|webp))?~i', $html, $im);
    foreach ($im[0] as $raw) {
        $rel = destoon_rel($raw);
        if (!$rel || str_contains($rel, '092115831')) continue;
        if (str_contains($rel, '.thumb.')) { $thumb = $rel; break; }
        if ($thumb === '') $thumb = $rel;
    }
    if ($thumb) download_rel($public, $origin, $thumb);
    $floor = guess_floor($title);
    $catId = $floor ? root_id($pdo, $floor, ['sell']) : root_id($pdo, '硅油', ['sell']);
    $summary = mb_substr($title, 0, 120);
    $findS->execute([$itemid]);
    if ($findS->fetchColumn()) {
        $updS->execute([$title, $thumb ?: null, $price, '吨', $summary, '', $catId, $itemid]);
    } else {
        $insS->execute([$userId, $catId, $title, $thumb ?: null, $price, '吨', $summary, '', $itemid]);
    }
    $okS++;
    echo "sell #$itemid $title\n";
    usleep(60000);
}
echo "sell upserted $okS\n";

fill_empty_floors($pdo, 'products', 'title', ['sell'], 4);
fill_empty_floors($pdo, 'supplies', 'title', ['sell'], 4);
fill_empty_floors($pdo, 'companies', 'name', ['company'], 4);

echo "=== floor counts ===\n";
foreach ([['products','sell'], ['supplies','sell'], ['companies','company']] as [$tb, $mod]) {
    $rs = $pdo->prepare("SELECT c.name, COUNT(t.id) n FROM categories c LEFT JOIN `$tb` t ON t.category_id=c.id AND t.status='approved' WHERE c.module=? AND c.parent_id IS NULL GROUP BY c.id, c.name ORDER BY c.sort");
    $rs->execute([$mod]);
    foreach ($rs as $r) echo "$tb {$r['name']} = {$r['n']}\n";
}
echo "DONE\n";
