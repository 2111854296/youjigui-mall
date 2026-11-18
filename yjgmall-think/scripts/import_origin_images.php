<?php
/**
 * Pull public images from the client's Destoon site (yjgmall.com)
 * into public/file/upload/... (same relative paths) and write thumbs into DB.
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
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
]);

$public = is_dir('/www/wwwroot/g.aifeili.cn/public')
    ? '/www/wwwroot/g.aifeili.cn/public'
    : realpath(__DIR__ . '/../public');
if (!$public) {
    fwrite(STDERR, "public dir not found\n");
    exit(1);
}

$origin = 'https://www.yjgmall.com';
$pages = [
    '/mall/',
    '/mall/list.php',
    '/mall/list.php?page=2',
    '/mall/list.php?page=3',
    '/sell/',
    '/sell/list.php',
    '/sell/list.php?page=2',
    '/sell/list.php?page=3',
    '/news/',
    '/news/list.php',
    '/news/list.php?catid=73103',
    '/news/list.php?catid=73104',
    '/news/list.php?catid=73105',
    '/company/',
    '/company/list.php',
    '/company/list.php?page=2',
    '/company/list.php?page=3',
    '/',
];

function http_get(string $url): string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 12,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; YjgMallImporter/1.0)',
        CURLOPT_HTTPHEADER => ['Accept: text/html,image/*,*/*'],
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($body === false || $code >= 400) {
        return '';
    }
    return $body;
}

function to_utf8(string $html): string
{
    if (preg_match('/charset\s*=\s*["\']?(gbk|gb2312|gb18030)/i', $html, $m)
        || !mb_check_encoding($html, 'UTF-8')) {
        $from = isset($m[1]) ? strtoupper($m[1]) : 'GBK';
        if ($from === 'GB2312') $from = 'GBK';
        $conv = @mb_convert_encoding($html, 'UTF-8', $from);
        if ($conv !== false) return $conv;
    }
    return $html;
}

function normalize_title(string $s): string
{
    $s = html_entity_decode($s, ENT_QUOTES, 'UTF-8');
    $s = preg_replace('/\s+/u', '', $s);
    $s = str_replace(['｜', '|', '—', '-', '·', '【', '】', '（', '）', '(', ')'], '', $s);
    return $s;
}

function abs_url(string $src, string $origin): string
{
    $src = html_entity_decode(trim($src));
    if ($src === '') return '';
    if (str_starts_with($src, '//')) return 'https:' . $src;
    if (str_starts_with($src, 'http://') || str_starts_with($src, 'https://')) {
        return preg_replace('#^http://#', 'https://', $src);
    }
    if ($src[0] === '/') return $origin . $src;
    return $origin . '/' . ltrim($src, './');
}

function destoon_rel(string $url): ?string
{
    if (!preg_match('~/file/upload/\d{6}/\d{2}/[^?\s]+\.(?:jpg|jpeg|png|gif|webp)(?:\.thumb\.(?:jpg|jpeg|png|gif|webp))?~i', $url, $m)) {
        return null;
    }
    $rel = $m[0];
    if ($rel[0] !== '/') {
        $rel = '/' . $rel;
    }
    return $rel;
}

function skip_src(string $src): bool
{
    if ($src === '') return true;
    if (preg_match('#/skin/|/ad/|phone\.gif|qq\d+\.gif|topad|brand_tx#i', $src)) return true;
    if (!preg_match('#file/upload/#i', $src)) return true;
    return false;
}

$pairs = ['product' => [], 'news' => [], 'company' => [], 'any' => []];

foreach ($pages as $path) {
    $url = $origin . $path;
    echo "fetch $url\n";
    $html = to_utf8(http_get($url));
    if ($html === '') {
        echo "  empty\n";
        continue;
    }
    if (!preg_match_all('/<img\b([^>]+)>/i', $html, $imgs, PREG_SET_ORDER)) {
        continue;
    }
    $count = 0;
    foreach ($imgs as $img) {
        $tag = $img[1];
        if (!preg_match('/src=["\']([^"\']+)["\']/i', $tag, $sm)) continue;
        $src = abs_url($sm[1], $origin);
        if (skip_src($src)) continue;
        $rel = destoon_rel($src);
        if (!$rel) continue;
        $alt = '';
        if (preg_match('/alt=["\']([^"\']*)["\']/i', $tag, $am)) {
            $alt = trim(html_entity_decode($am[1], ENT_QUOTES, 'UTF-8'));
        }
        $w = 0; $h = 0;
        if (preg_match('/width=["\']?(\d+)/i', $tag, $wm)) $w = (int) $wm[1];
        if (preg_match('/height=["\']?(\d+)/i', $tag, $hm)) $h = (int) $hm[1];
        $kind = 'any';
        if (str_contains($path, '/news')) $kind = 'news';
        elseif (str_contains($path, '/company')) $kind = 'company';
        elseif (str_contains($path, '/mall') || str_contains($path, '/sell')) $kind = 'product';
        if ($w === 200 && $h === 220) $kind = 'news';
        if ($w === 175 && $h === 175) $kind = 'product';
        if ($w === 130 && $h === 130) $kind = 'product';
        if ($w === 145 && $h === 66) $kind = 'company';
        $item = ['title' => $alt, 'src' => $src, 'rel' => $rel, 'w' => $w, 'h' => $h];
        $pairs[$kind][$rel] = $item;
        $pairs['any'][$rel] = $item;
        $count++;
    }
    echo "  images $count\n";
    usleep(200000);
}

function download_rel(string $public, string $origin, string $rel): bool
{
    $dest = $public . $rel;
    $dir = dirname($dest);
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return false;
    }
    if (is_file($dest) && filesize($dest) > 800) {
        return true;
    }
    $bin = http_get($origin . $rel);
    if ($bin === '' || strlen($bin) < 400) {
        $bin = http_get('https://www.yjgmall.com' . $rel);
    }
    if ($bin === '' || strlen($bin) < 400) {
        return false;
    }
    if (str_starts_with(ltrim($bin), '<')) {
        return false;
    }
    return file_put_contents($dest, $bin) !== false;
}

$ok = 0; $fail = 0;
foreach ($pairs['any'] as $rel => $item) {
    if (download_rel($public, $origin, $rel)) {
        $ok++;
        echo "ok $rel\n";
    } else {
        $fail++;
        echo "fail $rel\n";
        unset($pairs['any'][$rel], $pairs['product'][$rel], $pairs['news'][$rel], $pairs['company'][$rel]);
    }
}
echo "downloaded ok=$ok fail=$fail\n";

function pick_path(array $row, array $pool, array &$used): ?string
{
    $title = normalize_title((string) ($row['title'] ?? $row['name'] ?? ''));
    $best = null; $bestLen = 0;
    foreach ($pool as $rel => $item) {
        if (isset($used[$rel])) continue;
        $t = normalize_title((string) $item['title']);
        if ($t === '' || $title === '') continue;
        if ($t === $title || str_contains($title, $t) || str_contains($t, $title)) {
            $len = min(mb_strlen($t), mb_strlen($title));
            if ($len > $bestLen) {
                $bestLen = $len;
                $best = $rel;
            }
        }
    }
    if ($best) {
        $used[$best] = true;
        return $best;
    }
    foreach ($pool as $rel => $item) {
        if (!isset($used[$rel])) {
            $used[$rel] = true;
            return $rel;
        }
    }
    return null;
}

function fill_table(PDO $pdo, string $table, string $idCol, string $titleCol, string $imgCol, array $pool): int
{
    $used = [];
    $n = 0;
    $rows = $pdo->query("SELECT `$idCol` AS id, `$titleCol` AS title, `$imgCol` AS img FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
    $upd = $pdo->prepare("UPDATE `$table` SET `$imgCol`=? WHERE `$idCol`=?");
    foreach ($rows as $row) {
        $cur = (string) $row['img'];
        if ($cur && str_starts_with($cur, '/file/upload/') && !preg_match('/placehold|092115831/i', $cur)) {
            continue;
        }
        $rel = pick_path($row, $pool, $used);
        if (!$rel) continue;
        $upd->execute([$rel, $row['id']]);
        $n++;
    }
    return $n;
}

function is_banner_or_logo(array $item): bool
{
    $rel = $item['rel'] ?? '';
    if (str_contains($rel, '092115831.png')) return true;
    $w = (int) ($item['w'] ?? 0);
    $h = (int) ($item['h'] ?? 0);
    if ($w >= 500 || $h >= 300) return true;
    return false;
}

$productPool = array_filter($pairs['product'] ?: $pairs['any'], fn ($i) => !is_banner_or_logo($i));
$newsPool = array_filter($pairs['news'] ?: $pairs['any'], fn ($i) => !is_banner_or_logo($i));
$companyPool = array_filter($pairs['company'] ?: $pairs['any'], fn ($i) => !is_banner_or_logo($i));
if (!$newsPool) $newsPool = $productPool;
if (!$productPool) $productPool = array_filter($pairs['any'], fn ($i) => !is_banner_or_logo($i));
if (!$companyPool) $companyPool = $productPool;

$nP = fill_table($pdo, 'products', 'id', 'title', 'thumb', $productPool);
$nS = 0;
try { $nS = fill_table($pdo, 'supplies', 'id', 'title', 'thumb', $productPool); } catch (Throwable $e) {}
$nA = fill_table($pdo, 'articles', 'id', 'title', 'thumb', $newsPool);
$nC = 0;
try { $nC = fill_table($pdo, 'companies', 'id', 'name', 'logo', $companyPool); } catch (Throwable $e) {}

echo "updated products=$nP supplies=$nS articles=$nA companies=$nC\n";
echo "DONE\n";
