<?php
/**
 * Scrape mall products + company shops from the client's Destoon site
 * into our products/companies tables, keeping /file/upload paths.
 * Safe to re-run (upsert by origin_itemid / origin_homepage).
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
    $env['DB_NAME'] ?? 'yjgmall_g'
);
$pdo = new PDO($dsn, $env['DB_USER'] ?? $env['DB_USERNAME'] ?? 'root', $env['DB_PASS'] ?? $env['DB_PASSWORD'] ?? '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
]);
$public = is_dir('/www/wwwroot/g.aifeili.cn/public')
    ? '/www/wwwroot/g.aifeili.cn/public'
    : realpath(__DIR__ . '/../public');
$origin = 'https://www.yjgmall.com';

function http_get(string $url): string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 28,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; YjgMallCatalog/1.0)',
        CURLOPT_ENCODING => '',
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($body && $code < 400) ? $body : '';
}
function to_utf8(string $html): string
{
    if (preg_match('/charset\s*=\s*["\']?(gbk|gb2312|gb18030)/i', substr($html, 0, 1200))) {
        $conv = @mb_convert_encoding($html, 'UTF-8', 'GBK');
        return $conv !== false ? $conv : $html;
    }
    return $html;
}
function destoon_rel(string $url): ?string
{
    if (!preg_match('~/file/upload/\d{6}/\d{2}/[^?\s]+\.(?:jpg|jpeg|png|gif|webp)(?:\.(?:thumb|middle)\.(?:jpg|jpeg|png|gif|webp))?~i', $url, $m)) {
        return null;
    }
    $rel = $m[0];
    return $rel[0] === '/' ? $rel : '/' . $rel;
}
function download_rel(string $public, string $origin, string $rel): bool
{
    if (!$rel || str_contains($rel, '092115831.png')) return false;
    $dest = $public . $rel;
    $dir = dirname($dest);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (is_file($dest) && filesize($dest) > 600) return true;
    $bin = http_get($origin . $rel);
    if ($bin === '' || strlen($bin) < 400 || str_starts_with(ltrim($bin), '<')) return false;
    return file_put_contents($dest, $bin) !== false;
}
function has_col(PDO $pdo, string $table, string $col): bool
{
    $st = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
    $st->execute([$table, $col]);
    return (int) $st->fetchColumn() > 0;
}
function ensure_col(PDO $pdo, string $table, string $col, string $ddl): void
{
    if (has_col($pdo, $table, $col)) return;
    $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$col` $ddl");
    echo "add $table.$col\n";
}
ensure_col($pdo, 'products', 'origin_itemid', 'INT UNSIGNED NULL');
ensure_col($pdo, 'companies', 'origin_homepage', 'VARCHAR(64) NULL');
try { $pdo->exec('CREATE UNIQUE INDEX uk_products_origin ON products (origin_itemid)'); } catch (Throwable $e) {}
try { $pdo->exec('CREATE UNIQUE INDEX uk_companies_origin ON companies (origin_homepage)'); } catch (Throwable $e) {}

function cat_map(PDO $pdo, string $name, array $modules): ?int
{
    $name = trim($name);
    if ($name === '') return null;
    foreach ($modules as $mod) {
        $st = $pdo->prepare("SELECT id FROM categories WHERE module=? AND name=? AND parent_id IS NULL AND is_active=1 LIMIT 1");
        $st->execute([$mod, $name]);
        $id = $st->fetchColumn();
        if ($id) return (int) $id;
    }
    if (in_array('company', $modules, true) && in_array($name, ['硅烷', '硅树脂'], true)) {
        return cat_map($pdo, '硅烷/硅树脂', ['company']);
    }
    if (in_array('sell', $modules, true) && $name === '硅烷/硅树脂') {
        return cat_map($pdo, '硅烷', ['sell', 'mall']);
    }
    foreach ($modules as $mod) {
        $st = $pdo->prepare("SELECT id FROM categories WHERE module=? AND name LIKE ? AND parent_id IS NULL AND is_active=1 LIMIT 1");
        $st->execute([$mod, '%'.$name.'%']);
        $id = $st->fetchColumn();
        if ($id) return (int) $id;
    }
    $st = $pdo->prepare("SELECT id FROM categories WHERE module=? AND parent_id IS NULL AND is_active=1 ORDER BY sort ASC LIMIT 1");
    $st->execute([$modules[0]]);
    $id = $st->fetchColumn();
    return $id ? (int) $id : null;
}

$userId = (int) $pdo->query("SELECT id FROM users ORDER BY id ASC LIMIT 1")->fetchColumn();
if (!$userId) {
    fwrite(STDERR, "no users\n");
    exit(1);
}

// ---------- mall itemids ----------
$itemids = [];
$mallSeeds = ['/mall/', '/mall/list.php'];
$htmlHome = to_utf8(http_get($origin . '/mall/'));
preg_match_all('/list\.php\?catid=(\d+)/', $htmlHome, $cm);
$catids = array_values(array_unique($cm[1] ?? []));
echo 'mall cats ' . count($catids) . "\n";
foreach ($catids as $cid) {
    $mallSeeds[] = '/mall/list.php?catid=' . $cid;
    $mallSeeds[] = '/mall/list.php?catid=' . $cid . '&page=2';
    $mallSeeds[] = '/mall/list.php?catid=' . $cid . '&page=3';
}
foreach ($mallSeeds as $path) {
    $html = ($path === '/mall/') ? $htmlHome : to_utf8(http_get($origin . $path));
    if ($html === '') continue;
    preg_match_all('/show\.php\?itemid=(\d+)/', $html, $mm);
    foreach ($mm[1] as $id) $itemids[(int) $id] = true;
    usleep(120000);
}
$itemids = array_keys($itemids);
sort($itemids);
echo 'mall items ' . count($itemids) . "\n";

$insP = $pdo->prepare("INSERT INTO products (user_id, category_id, title, thumb, price, stock, unit, summary, content, status, sales, origin_itemid)
    VALUES (?,?,?,?,?,?,?,?,?,'approved',?,?)");
$updP = $pdo->prepare("UPDATE products SET category_id=?, title=?, thumb=?, price=?, unit=?, summary=?, content=?, status='approved', sales=? WHERE origin_itemid=?");
$findP = $pdo->prepare('SELECT id FROM products WHERE origin_itemid=?');

$okP = 0;
foreach ($itemids as $itemid) {
    $html = to_utf8(http_get($origin . '/mall/show.php?itemid=' . $itemid));
    if ($html === '' || !preg_match('/<h1[^>]*>(.*?)<\/h1>/s', $html, $hm)) {
        echo "skip mall $itemid\n";
        continue;
    }
    $title = trim(html_entity_decode(strip_tags($hm[1]), ENT_QUOTES, 'UTF-8'));
    if ($title === '') continue;
    $price = 0;
    if (preg_match('/class="px16"[^>]*>\s*([\d.]+)/', $html, $pm) || preg_match('/[￥¥]\s*<span[^>]*>\s*([\d.]+)/u', $html, $pm)) {
        $price = (float) $pm[1];
    }
    $catName = '';
    if (preg_match_all('/list\.php\?catid=\d+[^>]*>([^<]+)/', $html, $cats)) {
        foreach ($cats[1] as $cn) {
            $cn = trim(html_entity_decode(strip_tags($cn), ENT_QUOTES, 'UTF-8'));
            if (in_array($cn, ['有机硅单体/中间体','硅油','硅橡胶','有机硅助剂/填料','机械设备','硅烷','胶粘剂','硅树脂','硅烷/硅树脂'], true)) {
                $catName = $cn;
                break;
            }
        }
        if ($catName === '' && !empty($cats[1][0])) {
            $catName = trim(html_entity_decode(strip_tags($cats[1][0]), ENT_QUOTES, 'UTF-8'));
        }
    }
    $catId = cat_map($pdo, $catName, ['sell', 'mall']) ?: cat_map($pdo, '硅油', ['sell', 'mall']);
    $thumb = '';
    preg_match_all('~/file/upload/\d{6}/\d{2}/[^"\']+\.(?:jpg|jpeg|png|gif|webp)(?:\.(?:thumb|middle)\.(?:jpg|jpeg|png|gif|webp))?~i', $html, $im);
    foreach ($im[0] as $raw) {
        $rel = destoon_rel($raw);
        if (!$rel || str_contains($rel, '092115831')) continue;
        if (str_contains($rel, '.thumb.')) { $thumb = $rel[0] === '/' ? $rel : '/'.$rel; break; }
        if ($thumb === '') $thumb = $rel[0] === '/' ? $rel : '/'.$rel;
    }
    if ($thumb && $thumb[0] !== '/') $thumb = '/'.$thumb;
    if ($thumb) download_rel($public, $origin, $thumb);
    $content = '';
    if (preg_match('/id="content"[^>]*>(.*?)<\/div>/s', $html, $ct) || preg_match('/class="content"[^>]*>(.*?)<\/div>/s', $html, $ct)) {
        $content = trim($ct[1]);
    }
    $summary = mb_substr(trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($content ?: $title), ENT_QUOTES, 'UTF-8'))), 0, 180);
    $unit = (preg_match('/元\s*\/\s*(吨|公斤|千克|套|台|桶|瓶)/u', $html, $um)) ? $um[1] : '吨';
    $sales = 0;
    if (preg_match('/已售\s*(\d+)/u', $html, $sm) || preg_match('/人气[^0-9]{0,6}(\d+)/u', $html, $sm)) $sales = (int) $sm[1];
    $findP->execute([$itemid]);
    if ($findP->fetchColumn()) {
        $updP->execute([$catId, $title, $thumb ?: null, $price, $unit, $summary, $content, $sales, $itemid]);
    } else {
        $insP->execute([$userId, $catId, $title, $thumb ?: null, $price, 999, $unit, $summary, $content, $sales, $itemid]);
    }
    $okP++;
    echo "mall #$itemid $title ￥$price\n";
    usleep(80000);
}

// ---------- companies ----------
$shops = []; // homepage => [name, logo, catName]
$coHome = to_utf8(http_get($origin . '/company/'));
preg_match_all('/list\.php\?catid=(\d+)/', $coHome, $ccm);
$coCats = array_values(array_unique($ccm[1] ?? []));
$coPages = ['/company/'];
foreach ($coCats as $cid) {
    $coPages[] = '/company/list.php?catid=' . $cid;
    $coPages[] = '/company/list.php?catid=' . $cid . '&page=2';
}
echo 'company cats ' . count($coCats) . "\n";

function ingest_company_links(string $html, ?string $forcedCat, array &$shops): void
{
    if (!preg_match_all('/<a[^>]+href=["\'][^"\']*homepage=([A-Za-z0-9_]+)["\'][^>]*>(.*?)<\/a>/su', $html, $mm, PREG_SET_ORDER)) {
        return;
    }
    foreach ($mm as $m) {
        $hp = $m[1];
        $name = trim(html_entity_decode(strip_tags($m[2]), ENT_QUOTES, 'UTF-8'));
        if ($name === '' || mb_strlen($name) < 4) continue;
        if (!isset($shops[$hp])) $shops[$hp] = ['homepage' => $hp, 'name' => $name, 'logo' => '', 'catName' => $forcedCat ?: ''];
        elseif ($forcedCat && empty($shops[$hp]['catName'])) $shops[$hp]['catName'] = $forcedCat;
        elseif (mb_strlen($name) > mb_strlen($shops[$hp]['name'])) $shops[$hp]['name'] = $name;
    }
    if (preg_match_all('/<img[^>]+alt=["\']([^"\']+)["\'][^>]+src=["\']([^"\']+file\/upload[^"\']+)["\']/i', $html, $im, PREG_SET_ORDER)
        || preg_match_all('/<img[^>]+src=["\']([^"\']+file\/upload[^"\']+)["\'][^>]+alt=["\']([^"\']+)["\']/i', $html, $im2, PREG_SET_ORDER)) {
        $rows = $im ?: [];
        foreach (($im2 ?? []) as $r) { $rows[] = [1 => $r[2], 2 => $r[1]]; }
        foreach ($im as $r) {
            $alt = trim(html_entity_decode($r[1], ENT_QUOTES, 'UTF-8'));
            $rel = destoon_rel($r[2]);
            if (!$rel) continue;
            foreach ($shops as $hp => $s) {
                if ($s['name'] === $alt || ($alt && str_contains($s['name'], $alt))) {
                    $shops[$hp]['logo'] = $rel[0] === '/' ? $rel : '/'.$rel;
                }
            }
        }
    }
}

foreach ($coPages as $path) {
    $html = ($path === '/company/') ? $coHome : to_utf8(http_get($origin . $path));
    $forced = '';
    if (preg_match('/catid=(\d+)/', $path, $x)) {
        // try heading
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/s', $html, $h1)) {
            $forced = trim(strip_tags($h1[1]));
        }
        if (preg_match('/全部行业分类[^<]*<\/[^>]+>\s*<[^>]+>([^<]+)/u', $html, $hm)) {
            $forced = trim($hm[1]);
        }
    }
    ingest_company_links($html, $forced, $shops);
    echo "page $path shops=" . count($shops) . "\n";
    usleep(120000);
}

$insC = null;
$cols = 'user_id,name,slug,logo,intro,content,business,address,area,phone,contact,website,category_id,status,is_featured,is_certified,origin_homepage';
$insC = $pdo->prepare("INSERT INTO companies ($cols) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,'approved',0,0,?)");
$updC = $pdo->prepare("UPDATE companies SET name=?, logo=?, intro=?, content=?, business=?, address=?, area=?, phone=?, contact=?, website=?, category_id=?, status='approved' WHERE origin_homepage=?");
$findC = $pdo->prepare('SELECT id FROM companies WHERE origin_homepage=?');
$slugTaken = $pdo->prepare('SELECT id FROM companies WHERE slug=? AND (origin_homepage IS NULL OR origin_homepage<>?)');

$okC = 0;
foreach ($shops as $hp => $s) {
    $introHtml = to_utf8(http_get($origin . '/index.php?homepage=' . rawurlencode($hp) . '&file=introduce'));
    $homeHtml = to_utf8(http_get($origin . '/index.php?homepage=' . rawurlencode($hp)));
    $contactHtml = to_utf8(http_get($origin . '/index.php?homepage=' . rawurlencode($hp) . '&file=contact'));
    $blob = $introHtml . "\n" . $homeHtml . "\n" . $contactHtml;
    $name = $s['name'];
    if (preg_match('/<h1[^>]*>(.*?)<\/h1>/s', $homeHtml, $h1)) {
        $n = trim(html_entity_decode(strip_tags($h1[1]), ENT_QUOTES, 'UTF-8'));
        if (mb_strlen($n) >= 4) $name = $n;
    }
    $intro = '';
    if (preg_match('/name="description"\s+content="([^"]+)"/', $homeHtml, $dm)) {
        $intro = html_entity_decode($dm[1], ENT_QUOTES, 'UTF-8');
    }
    $content = '';
    if (preg_match('/id="content"[^>]*>(.*?)<\/div>/s', $introHtml, $ct) || preg_match('/公司介绍<\/strong>.*?<\/div>\s*<div[^>]*>(.*?)<\/div>/su', $introHtml, $ct)) {
        $content = trim($ct[1]);
    }
    if ($content === '' && $introHtml) {
        $content = trim(preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $introHtml));
        $content = mb_substr(strip_tags($content, '<p><br><div><span>'), 0, 4000);
    }
    if ($intro === '') $intro = mb_substr(trim(preg_replace('/\s+/u', ' ', strip_tags($content))), 0, 180);
    $phone = '';
    if (preg_match('/(?:电话|联系电话|Tel)[：:\s]*([0-9\-()（）\s]{8,20})/u', $blob, $ph)) $phone = trim($ph[1]);
    $address = '';
    if (preg_match('/(?:地址|公司地址)[：:\s]*([^\n<]{6,80})/u', $blob, $ad)) $address = trim(strip_tags($ad[1]));
    $contact = '';
    if (preg_match('/(?:联系人)[：:\s]*([^\n<]{2,20})/u', $blob, $cp)) $contact = trim(strip_tags($cp[1]));
    $area = '';
    foreach (['广东','浙江','江苏','山东','上海','北京','福建','江西','安徽','湖北','四川','河北','湖南','河南','广西','云南','贵州','辽宁','内蒙古'] as $a) {
        if (str_contains($address.$intro.$name, $a)) { $area = $a; break; }
    }
    $business = '';
    if (preg_match('/<h4[^>]*>(.*?)<\/h4>/s', $homeHtml, $h4)) {
        $business = trim(html_entity_decode(strip_tags($h4[1]), ENT_QUOTES, 'UTF-8'));
    }
    if ($business === '') $business = mb_substr($intro, 0, 80);
    $logo = $s['logo'];
    if (!$logo) {
        preg_match_all('~/file/upload/\d{6}/\d{2}/[^"\']+\.(?:jpg|jpeg|png|gif|webp)~i', $homeHtml.$introHtml, $im);
        foreach ($im[0] as $raw) {
            $rel = destoon_rel($raw);
            if ($rel && !str_contains($rel, '092115831') && !str_contains($rel, 'banner')) {
                $logo = $rel[0] === '/' ? $rel : '/'.$rel;
                break;
            }
        }
    }
    if ($logo) download_rel($public, $origin, $logo);
    $catName = $s['catName'];
    if ($catName === '' && preg_match('/keywords"\s+content="[^"]*,([^,"]+)"/', $homeHtml, $kw)) {
        $catName = trim($kw[1]);
    }
    $catId = cat_map($pdo, $catName, ['company']) ?: cat_map($pdo, '硅油', ['company']);
    $slug = preg_replace('/[^a-z0-9\-]+/i', '-', $hp);
    $slug = strtolower(trim($slug, '-')) ?: ('co-'.$hp);
    $n = 0;
    $try = $slug;
    while (true) {
        $slugTaken->execute([$try, $hp]);
        if (!$slugTaken->fetchColumn()) { $slug = $try; break; }
        $try = $slug . '-' . (++$n);
        if ($n > 8) break;
    }
    $findC->execute([$hp]);
    if ($findC->fetchColumn()) {
        $updC->execute([$name, $logo ?: null, $intro, $content, $business, $address, $area, $phone, $contact, null, $catId, $hp]);
    } else {
        $insC->execute([$userId, $name, $slug, $logo ?: null, $intro, $content, $business, $address, $area, $phone, $contact, null, $catId, $hp]);
    }
    $okC++;
    echo "shop $hp $name\n";
    usleep(80000);
}

if ($okP >= 10) {
    $pdo->exec("UPDATE products SET status='offline' WHERE origin_itemid IS NULL AND status='approved'");
    echo "offlined demo products\n";
}
if ($okC >= 10) {
    $pdo->exec("UPDATE companies SET status='offline' WHERE origin_homepage IS NULL AND status='approved'");
    echo "offlined demo companies\n";
}

$pc = $pdo->query("SELECT COUNT(*) FROM products WHERE status='approved'")->fetchColumn();
$cc = $pdo->query("SELECT COUNT(*) FROM companies WHERE status='approved'")->fetchColumn();
echo "DONE mall_ok=$okP company_ok=$okC approved_products=$pc approved_companies=$cc\n";
