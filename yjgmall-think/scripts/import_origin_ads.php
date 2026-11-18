<?php
/**
 * Import Destoon channel/home slide ads from yjgmall.com into ads + banners.
 * Safe to re-run: upserts by (position, image).
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
    'home_banner' => ['path' => '/', 'w' => 720, 'h' => 329],
    'mall_top' => ['path' => '/mall/', 'w' => 965, 'h' => 366],
    'company_top' => ['path' => '/company/', 'w' => 695, 'h' => 300],
    'news_top' => ['path' => '/news/', 'w' => 455, 'h' => 240],
];

function http_get(string $url, bool $headersOnly = false): string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => !$headersOnly,
        CURLOPT_HEADER => $headersOnly,
        CURLOPT_NOBODY => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 12,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; YjgMallImporter/1.0)',
        CURLOPT_HTTPHEADER => ['Accept: text/html,image/*,*/*'],
    ]);
    if ($headersOnly) {
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    }
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirect = (string) curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);
    if ($headersOnly) {
        if ($redirect) {
            return $redirect;
        }
        if (is_string($body) && preg_match('/^Location:\s*(.+)$/mi', $body, $m)) {
            return trim($m[1]);
        }
        return '';
    }
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
        if ($from === 'GB2312') {
            $from = 'GBK';
        }
        $conv = @mb_convert_encoding($html, 'UTF-8', $from);
        if ($conv !== false) {
            return $conv;
        }
    }
    return $html;
}

function abs_url(string $src, string $origin): string
{
    $src = html_entity_decode(trim($src));
    if ($src === '') {
        return '';
    }
    if (str_starts_with($src, '//')) {
        return 'https:' . $src;
    }
    if (str_starts_with($src, 'http://') || str_starts_with($src, 'https://')) {
        return preg_replace('#^http://#', 'https://', $src);
    }
    if ($src[0] === '/') {
        return $origin . $src;
    }
    return $origin . '/' . ltrim($src, './');
}

function destoon_rel(string $url): ?string
{
    if (!preg_match('~/file/upload/\d{6}/\d{2}/[^?\s]+\.(?:jpg|jpeg|png|gif|webp)~i', $url, $m)) {
        return null;
    }
    $rel = $m[0];
    if ($rel[0] !== '/') {
        $rel = '/' . $rel;
    }
    return preg_replace('/\.thumb\.[^.]+$/i', '', $rel);
}

function has_col(PDO $pdo, string $table, string $col): bool
{
    $st = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $st->execute([$col]);
    return (bool) $st->fetch();
}

function has_table(PDO $pdo, string $table): bool
{
    $st = $pdo->prepare('SHOW TABLES LIKE ?');
    $st->execute([$table]);
    return (bool) $st->fetch();
}

function extract_pairs(string $html, string $origin): array
{
    $out = [];
    if (!preg_match_all('/<a\b([^>]*)>(.*?)<\/a>/si', $html, $as, PREG_SET_ORDER)) {
        return $out;
    }
    foreach ($as as $a) {
        $attrs = $a[1];
        $inner = $a[2];
        if (!preg_match('/<img\b([^>]+)>/i', $inner, $im)) {
            continue;
        }
        $ia = $im[1];
        if (!preg_match('/src=["\']([^"\']+)["\']/i', $ia, $sm)) {
            continue;
        }
        $src = abs_url($sm[1], $origin);
        $href = '';
        if (preg_match('/href=["\']([^"\']+)["\']/i', $attrs, $hm)) {
            $href = html_entity_decode(trim($hm[1]));
        }
        $w = 0;
        $h = 0;
        if (preg_match('/width=["\']?(\d+)/i', $ia, $wm)) {
            $w = (int) $wm[1];
        }
        if (preg_match('/height=["\']?(\d+)/i', $ia, $hm2)) {
            $h = (int) $hm2[1];
        }
        $alt = '';
        if (preg_match('/(?:alt|title)=["\']([^"\']*)["\']/i', $ia . ' ' . $attrs, $tm)) {
            $alt = trim(html_entity_decode($tm[1], ENT_QUOTES, 'UTF-8'));
        }
        $out[] = [
            'src' => $src,
            'href' => $href,
            'w' => $w,
            'h' => $h,
            'alt' => $alt,
        ];
    }
    return $out;
}

function slide_chunks(string $html): array
{
    $chunks = [];
    if (preg_match_all('/id="(slide_a\d+)"/i', $html, $ms, PREG_OFFSET_CAPTURE)) {
        foreach ($ms[1] as $hit) {
            $start = max(0, (int) $hit[1] - 40);
            $chunks[] = substr($html, $start, 12000);
        }
    }
    if (!$chunks && preg_match_all('/id="(slide_\d+)"/i', $html, $ms, PREG_OFFSET_CAPTURE)) {
        foreach ($ms[1] as $hit) {
            $start = max(0, (int) $hit[1] - 40);
            $chunks[] = substr($html, $start, 8000);
        }
    }
    return $chunks;
}

function looks_like_slide(array $p, array $hint, bool $inSlideBlock): bool
{
    $src = $p['src'];
    if ($src === '' || preg_match('#/skin/|/ad/phone|qq\d+\.gif|topad#i', $src)) {
        return false;
    }
    if (str_contains($src, '092115831.png')) {
        return false;
    }
    $w = (int) $p['w'];
    $h = (int) $p['h'];
    if ($hint['w'] && $hint['h']) {
        return $w === (int) $hint['w'] && $h === (int) $hint['h'];
    }
    if ($inSlideBlock && destoon_rel($src) && ($w >= 400 || $h >= 200)) {
        return true;
    }
    return false;
}

function follow_url(string $url): string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 12,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; YjgMallImporter/1.0)',
    ]);
    $resp = curl_exec($ch);
    $final = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    if (is_string($resp) && preg_match('/^location:\s*(.+)$/mi', $resp, $m)) {
        $loc = trim($m[1]);
        if ($loc !== '') {
            return abs_url($loc, 'https://www.yjgmall.com');
        }
    }
    return $final ?: $url;
}

function follow_redirect_aid(string $url): string
{
    if (!preg_match('#redirect\.php\?aid=\d+#i', $url)) {
        return $url;
    }
    return follow_url($url);
}

function map_link(PDO $pdo, string $url): string
{
    $url = html_entity_decode(trim($url));
    if ($url === '' || $url === '#' || $url === '###' || str_starts_with($url, 'javascript:')) {
        return '';
    }
    $url = follow_redirect_aid($url);
    if (preg_match('/[?&]homepage=([^&]+)/i', $url, $m)) {
        $hp = urldecode($m[1]);
        $st = $pdo->prepare('SELECT slug FROM companies WHERE origin_homepage=? OR slug=? LIMIT 1');
        $st->execute([$hp, $hp]);
        $slug = $st->fetchColumn();
        return '/companies/' . ($slug ?: $hp);
    }
    if (preg_match('#/mall/show\.php\?itemid=(\d+)#i', $url, $m)) {
        $st = $pdo->prepare('SELECT id FROM products WHERE origin_itemid=? LIMIT 1');
        $st->execute([(int) $m[1]]);
        $id = $st->fetchColumn();
        return $id ? '/mall/' . $id : '/mall';
    }
    if (preg_match('#/sell/show\.php\?itemid=(\d+)#i', $url, $m)) {
        if (has_col($pdo, 'supplies', 'origin_itemid')) {
            $st = $pdo->prepare('SELECT id FROM supplies WHERE origin_itemid=? LIMIT 1');
            $st->execute([(int) $m[1]]);
            $id = $st->fetchColumn();
            if ($id) {
                return '/sell/' . $id;
            }
        }
        return '/sell';
    }
    if (preg_match('#/news/show\.php\?itemid=(\d+)#i', $url, $m)) {
        if (has_col($pdo, 'articles', 'origin_itemid')) {
            $st = $pdo->prepare('SELECT id FROM articles WHERE origin_itemid=? LIMIT 1');
            $st->execute([(int) $m[1]]);
            $id = $st->fetchColumn();
            if ($id) {
                return '/news/' . $id;
            }
        }
        return '/news';
    }
    $host = parse_url($url, PHP_URL_HOST);
    $path = (string) parse_url($url, PHP_URL_PATH);
    if ($host && preg_match('/yjgmall\.com$/i', $host)) {
        if ($path === '' || $path === '/' || $path === '/index.php') {
            return '/';
        }
        if (str_starts_with($path, '/mall')) {
            return '/mall';
        }
        if (str_starts_with($path, '/company')) {
            return '/companies';
        }
        if (str_starts_with($path, '/news')) {
            return '/news';
        }
        if (str_starts_with($path, '/sell')) {
            return '/sell';
        }
        if (str_starts_with($path, '/buy')) {
            return '/buy';
        }
        return '/';
    }
    if (preg_match('#^https?://#i', $url)) {
        return $url;
    }
    if (str_starts_with($url, '/')) {
        return $url;
    }
    return '';
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
    if ($bin === '' || strlen($bin) < 400 || str_starts_with(ltrim($bin), '<')) {
        return false;
    }
    return file_put_contents($dest, $bin) !== false;
}

function ensure_ads_table(PDO $pdo): void
{
    if (has_table($pdo, 'ads')) {
        return;
    }
    $pdo->exec("CREATE TABLE ads (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      title VARCHAR(255) NOT NULL,
      position VARCHAR(40) NOT NULL,
      type VARCHAR(20) NOT NULL DEFAULT 'image',
      image VARCHAR(255) NULL,
      link VARCHAR(255) NULL,
      content TEXT NULL,
      sort INT UNSIGNED NOT NULL DEFAULT 0,
      is_active TINYINT(1) NOT NULL DEFAULT 1,
      starts_at DATETIME NULL,
      ends_at DATETIME NULL,
      price DECIMAL(12,2) NULL,
      created_at DATETIME NULL,
      updated_at DATETIME NULL,
      KEY idx_position (position)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ensure_ad_places(PDO $pdo): void
{
    if (!has_table($pdo, 'ad_places')) {
        $pdo->exec("CREATE TABLE ad_places (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          code VARCHAR(40) NOT NULL UNIQUE,
          name VARCHAR(80) NOT NULL,
          size VARCHAR(40) NULL,
          type VARCHAR(20) NOT NULL DEFAULT 'image',
          price_note VARCHAR(80) NULL,
          sort INT UNSIGNED NOT NULL DEFAULT 0,
          is_active TINYINT(1) NOT NULL DEFAULT 1,
          created_at DATETIME NULL,
          updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    $places = [
        ['home_banner', '首页轮播', '720x329', 1],
        ['mall_top', '商城轮播', '965x366', 4],
        ['company_top', '企业库轮播', '960x280', 5],
        ['news_top', '资讯轮播', '960x280', 6],
    ];
    $sel = $pdo->prepare('SELECT id FROM ad_places WHERE code=? LIMIT 1');
    $ins = $pdo->prepare('INSERT INTO ad_places (code,name,size,type,price_note,sort,is_active,created_at,updated_at)
        VALUES (?,?,?,?,?,?,1,NOW(),NOW())');
    $upd = $pdo->prepare('UPDATE ad_places SET name=?, size=?, sort=?, is_active=1, updated_at=NOW() WHERE id=?');
    foreach ($places as $p) {
        $sel->execute([$p[0]]);
        $id = $sel->fetchColumn();
        if ($id) {
            $upd->execute([$p[1], $p[2], $p[3], $id]);
        } else {
            $ins->execute([$p[0], $p[1], $p[2], 'image', '面议', $p[3]]);
        }
    }
}

ensure_ads_table($pdo);
ensure_ad_places($pdo);
try { $pdo->exec('ALTER TABLE ads MODIFY link VARCHAR(1024) NULL'); } catch (Throwable $e) {}
if (has_table($pdo, 'banners')) {
    try { $pdo->exec('ALTER TABLE banners MODIFY link VARCHAR(1024) NULL'); } catch (Throwable $e) {}
}

$imported = [];
foreach ($pages as $position => $hint) {
    $url = $origin . $hint['path'];
    echo "fetch $url\n";
    $html = to_utf8(http_get($url));
    if ($html === '') {
        echo "  empty\n";
        continue;
    }
    $items = [];
    $chunks = slide_chunks($html);
    $sources = $chunks ? $chunks : [$html];
    foreach ($sources as $chunk) {
        $inBlock = $chunks !== [];
        foreach (extract_pairs($chunk, $origin) as $p) {
            if (!looks_like_slide($p, $hint, $inBlock)) {
                continue;
            }
            $rel = destoon_rel($p['src']);
            if (!$rel) {
                continue;
            }
            if (isset($items[$rel])) {
                continue;
            }
            $items[$rel] = $p + ['rel' => $rel];
        }
    }
    // news/company: if no wide ads, take first 3 large content slides (still clickable)
    if (!$items && in_array($position, ['news_top', 'company_top'], true)) {
        foreach (extract_pairs($html, $origin) as $p) {
            $rel = destoon_rel($p['src']);
            if (!$rel || str_contains($rel, '092115831')) {
                continue;
            }
            $w = (int) $p['w'];
            $h = (int) $p['h'];
            if ($w < 280 && $h < 180) {
                continue;
            }
            if (isset($items[$rel])) {
                continue;
            }
            $items[$rel] = $p + ['rel' => $rel];
            if (count($items) >= 3) {
                break;
            }
        }
    }
    echo '  candidates ' . count($items) . "\n";
    $sort = 1;
    foreach ($items as $p) {
        if (!download_rel($public, $origin, $p['rel'])) {
            echo "  fail {$p['rel']}\n";
            continue;
        }
        $link = mb_substr(map_link($pdo, (string) $p['href']), 0, 1000);
        $title = trim((string) ($p['alt'] ?? ''));
        if ($title === '' || preg_match('/^轮播\s*\d+$/u', $title)) {
            if (preg_match('#^/companies/([^/?]+)#', $link, $m)) {
                $st = $pdo->prepare('SELECT name FROM companies WHERE slug=? LIMIT 1');
                $st->execute([$m[1]]);
                $title = (string) ($st->fetchColumn() ?: $m[1]);
            } else {
                $title = '广告';
            }
        }
        $imported[] = [
            'position' => $position,
            'title' => mb_substr($title, 0, 80),
            'image' => $p['rel'],
            'link' => $link,
            'sort' => $sort,
            'origin_href' => $p['href'],
        ];
        $max = $position === 'home_banner' ? 12 : 6;
        echo "  ok {$p['rel']} -> {$link}\n";
        $sort++;
        if ($sort > $max) {
            break;
        }
    }
}

if (!$imported) {
    fwrite(STDERR, "no slides imported\n");
    exit(1);
}

$pdo->exec("UPDATE ads SET is_active=0, updated_at=NOW()
    WHERE position IN ('home_banner','mall_top','company_top','news_top')
      AND (image LIKE '%placehold%' OR image LIKE '%.svg' OR image LIKE '/img/%' OR image IS NULL OR image='')");

$find = $pdo->prepare('SELECT id FROM ads WHERE position=? AND image=? LIMIT 1');
$ins = $pdo->prepare("INSERT INTO ads (title,position,type,image,link,content,sort,is_active,created_at,updated_at)
    VALUES (?,?, 'image', ?, ?, ?, ?, 1, NOW(), NOW())");
$upd = $pdo->prepare('UPDATE ads SET title=?, link=?, content=?, sort=?, is_active=1, type=\'image\', updated_at=NOW() WHERE id=?');

$keepIds = [];
foreach ($imported as $row) {
    $content = 'origin:' . $row['origin_href'];
    $find->execute([$row['position'], $row['image']]);
    $id = $find->fetchColumn();
    if ($id) {
        $upd->execute([$row['title'], $row['link'], $content, $row['sort'], $id]);
        $keepIds[] = (int) $id;
    } else {
        $ins->execute([$row['title'], $row['position'], $row['image'], $row['link'], $content, $row['sort']]);
        $keepIds[] = (int) $pdo->lastInsertId();
    }
}

if ($keepIds) {
    $posList = "'home_banner','mall_top','company_top','news_top'";
    $idList = implode(',', array_map('intval', $keepIds));
    $pdo->exec("UPDATE ads SET is_active=0, updated_at=NOW()
        WHERE position IN ($posList) AND id NOT IN ($idList)
          AND (content LIKE 'origin:%' OR image LIKE '/file/upload/%')");
}

if (has_table($pdo, 'banners')) {
    $homeAds = array_values(array_filter($imported, fn ($r) => $r['position'] === 'home_banner'));
    if ($homeAds) {
        $pdo->exec("UPDATE banners SET is_active=0, updated_at=NOW() WHERE position IN ('home','home_banner')");
        $bf = $pdo->prepare('SELECT id FROM banners WHERE position IN (\'home\',\'home_banner\') AND image=? LIMIT 1');
        $bi = $pdo->prepare("INSERT INTO banners (title,image,link,position,sort,is_active,created_at,updated_at)
            VALUES (?,?,?,'home',?,1,NOW(),NOW())");
        $bu = $pdo->prepare("UPDATE banners SET title=?, link=?, position='home', sort=?, is_active=1, updated_at=NOW() WHERE id=?");
        foreach ($homeAds as $row) {
            $bf->execute([$row['image']]);
            $id = $bf->fetchColumn();
            if ($id) {
                $bu->execute([$row['title'], $row['link'], $row['sort'], $id]);
            } else {
                $bi->execute([$row['title'], $row['image'], $row['link'], $row['sort']]);
            }
        }
    }
}

$st = $pdo->query("SELECT position, COUNT(*) c FROM ads WHERE is_active=1 GROUP BY position");
echo "active ads:\n";
foreach ($st as $r) {
    echo "  {$r['position']} = {$r['c']}\n";
}
echo "DONE " . count($imported) . "\n";
