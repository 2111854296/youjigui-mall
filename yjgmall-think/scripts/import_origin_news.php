<?php
/**
 * 把老站 yjgmall.com 资讯按前台板块搬进后台，状态为待审，不上架。
 * 可重复执行：已有 origin_itemid 的文章会跳过。
 * php scripts/import_origin_news.php
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
$pdo = new PDO(
    sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $env['DB_HOST'] ?? '127.0.0.1',
        $env['DB_PORT'] ?? '3306',
        $env['DB_NAME'] ?? ''
    ),
    $env['DB_USER'] ?? $env['DB_USERNAME'] ?? 'root',
    $env['DB_PASS'] ?? $env['DB_PASSWORD'] ?? '',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
    ]
);
ini_set('memory_limit', '512M');
set_time_limit(0);

$public = is_dir('/www/wwwroot/g.aifeili.cn/public')
    ? '/www/wwwroot/g.aifeili.cn/public'
    : realpath(__DIR__ . '/../public');
$origin = 'https://www.yjgmall.com';

$slugOf = [
    73103 => 'news-review',
    73104 => 'news-review',
    73105 => 'news-review',
    73106 => 'news-review',
    73107 => 'news-company',
    73108 => 'news-company',
    73109 => 'news-tech',
    73110 => 'news-talk',
];

function http_get(string $url): string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; YjgMallNews/1.0)',
        CURLOPT_ENCODING => '',
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return (is_string($body) && $code < 400) ? $body : '';
}

function http_multi(array $urls): array
{
    if ($urls === []) {
        return [];
    }
    $mh = curl_multi_init();
    $handles = [];
    foreach ($urls as $key => $url) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; YjgMallNews/1.0)',
            CURLOPT_ENCODING => '',
        ]);
        curl_multi_add_handle($mh, $ch);
        $handles[$key] = $ch;
    }
    do {
        $status = curl_multi_exec($mh, $running);
        if ($running) {
            curl_multi_select($mh, 1.0);
        }
    } while ($running && $status === CURLM_OK);
    $out = [];
    foreach ($handles as $key => $ch) {
        $body = curl_multi_getcontent($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $out[$key] = (is_string($body) && $code < 400) ? $body : '';
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);
    return $out;
}

function destoon_rel(string $url): ?string
{
    if (!preg_match('~/file/upload/\d{6}/\d{2}/[^?\s"\']+\.(?:jpg|jpeg|png|gif|webp)~i', $url, $m)) {
        return null;
    }
    $rel = $m[0];
    return $rel[0] === '/' ? $rel : '/' . $rel;
}

function download_rel(string $public, string $origin, string $rel): bool
{
    if ($rel === '' || str_contains($rel, '092115831.png')) {
        return false;
    }
    $dest = $public . $rel;
    if (is_file($dest) && filesize($dest) > 400) {
        return true;
    }
    $dir = dirname($dest);
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return false;
    }
    $bin = http_get($origin . $rel);
    if ($bin === '' || strlen($bin) < 400 || str_starts_with(ltrim($bin), '<')) {
        return false;
    }
    return file_put_contents($dest, $bin) !== false;
}

function cut(string $html, string $start, string $end): string
{
    $i = strpos($html, $start);
    if ($i === false) {
        return '';
    }
    $i += strlen($start);
    $j = strpos($html, $end, $i);
    if ($j === false) {
        return '';
    }
    return substr($html, $i, $j - $i);
}

function article_body(string $html): string
{
    $start = '<div id="article">';
    $i = strpos($html, $start);
    if ($i === false) {
        $start = '<div class="content">';
        $i = strpos($html, $start);
    }
    if ($i === false) {
        return '';
    }
    $i += strlen($start);
    $ends = [];
    foreach (['<div class="newkeytags">', '<!-- 正文 E -->', '<div class="info-detail-ud">', '<div id="bdshare"'] as $mark) {
        $j = strpos($html, $mark, $i);
        if ($j !== false) {
            $ends[] = $j;
        }
    }
    if ($ends === []) {
        return '';
    }
    $body = substr($html, $i, min($ends) - $i);
    $body = preg_replace('/<\/div>\s*$/', '', $body) ?? $body;
    return trim($body);
}

function plain(string $html, int $len): string
{
    $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
    if (mb_strlen($text) > $len) {
        return mb_substr($text, 0, $len);
    }
    return $text;
}

$col = $pdo->query("SHOW COLUMNS FROM articles LIKE 'origin_itemid'")->fetch();
if (!$col) {
    $pdo->exec('ALTER TABLE articles ADD COLUMN origin_itemid INT UNSIGNED NULL DEFAULT NULL');
    try {
        $pdo->exec('CREATE UNIQUE INDEX uk_articles_origin ON articles (origin_itemid)');
    } catch (Throwable $e) {
    }
    echo "added origin_itemid\n";
}

$catIds = [];
$stCat = $pdo->prepare("SELECT id FROM categories WHERE module='article' AND slug=? ORDER BY id ASC LIMIT 1");
foreach (array_unique(array_values($slugOf)) as $slug) {
    $stCat->execute([$slug]);
    $id = (int) $stCat->fetchColumn();
    if ($id < 1) {
        fwrite(STDERR, "missing category {$slug}\n");
        exit(1);
    }
    $catIds[$slug] = $id;
    echo "category {$slug} #{$id}\n";
}

$have = [];
foreach ($pdo->query('SELECT origin_itemid FROM articles WHERE origin_itemid IS NOT NULL') as $row) {
    $have[(int) $row['origin_itemid']] = true;
}
echo 'already ' . count($have) . PHP_EOL;

$jobs = [];
foreach ($slugOf as $oldCat => $slug) {
    $pages = 0;
    $found = 0;
    $seenPage = [];
    for ($page = 1; $page <= 200; $page++) {
        $url = $origin . '/news/list.php?catid=' . $oldCat . ($page > 1 ? '&page=' . $page : '');
        $html = http_get($url);
        $ul = cut($html, '<div class="unv-list">', '</ul>');
        if ($ul === '' || !preg_match_all('/itemid=(\d+)[\s\S]{0,1200}?(\d{4}-\d{2}-\d{2})\s*更新/', $ul, $ms, PREG_SET_ORDER)) {
            break;
        }
        $pageKey = implode(',', array_map(fn ($m) => $m[1], $ms));
        if (isset($seenPage[$pageKey])) {
            break;
        }
        $seenPage[$pageKey] = true;
        $pages++;
        foreach ($ms as $m) {
            $itemId = (int) $m[1];
            if (isset($jobs[$itemId]) || isset($have[$itemId])) {
                continue;
            }
            $jobs[$itemId] = ['slug' => $slug, 'date' => $m[2]];
            $found++;
        }
        if (count($ms) < 8) {
            break;
        }
    }
    echo "listed cat {$oldCat} {$slug} pages={$pages} new={$found}\n";
}

uasort($jobs, function ($a, $b) {
    return strcmp($a['date'], $b['date']);
});
echo 'to_fetch ' . count($jobs) . PHP_EOL;

$insert = $pdo->prepare(
    'INSERT INTO articles (category_id, title, thumb, author, source, source_url, summary, content, type, is_top, level, status, published_at, views, view_boost, sort, origin_itemid, created_at, updated_at)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
);

$ok = 0;
$fail = 0;
$bySlug = array_fill_keys(array_values($slugOf), 0);
$ids = array_keys($jobs);
$batchSize = 8;
for ($i = 0, $n = count($ids); $i < $n; $i += $batchSize) {
    $slice = array_slice($ids, $i, $batchSize);
    $urls = [];
    foreach ($slice as $itemId) {
        $urls[$itemId] = $origin . '/news/show.php?itemid=' . $itemId;
    }
    $pages = http_multi($urls);
    $now = date('Y-m-d H:i:s');
    foreach ($slice as $itemId) {
        $html = $pages[$itemId] ?? '';
        $job = $jobs[$itemId];
        $title = '';
        if (preg_match('/<div class="titlenews">\s*<h1>(.*?)<\/h1>/s', $html, $m)) {
            $title = plain($m[1], 180);
        }
        if ($title === '') {
            $fail++;
            echo "fail item {$itemId} empty\n";
            continue;
        }
        $published = $job['date'] . ' 00:00:00';
        if (preg_match('/发布日期：\s*<\/span>\s*<strong>\s*(\d{4}-\d{2}-\d{2})/u', $html, $m)) {
            $published = $m[1] . ' 00:00:00';
        }
        $source = '有机硅商城';
        if (preg_match('/来源：\s*<\/span>\s*<strong>\s*([^<]{1,80})/u', $html, $m)) {
            $source = plain($m[1], 80) ?: $source;
        }
        $views = 0;
        if (preg_match('/id="hits">\s*(\d+)/', $html, $m)) {
            $views = min(2000000000, (int) $m[1]);
        }
        $body = article_body($html);
        if ($body === '') {
            $fail++;
            echo "fail item {$itemId} no body\n";
            continue;
        }
        $thumb = null;
        $body = preg_replace_callback('/(<img\b[^>]*\bsrc=["\'])([^"\']+)/i', function ($m) use ($public, $origin, &$thumb) {
            $src = $m[2];
            if (str_starts_with($src, '//')) {
                $src = 'https:' . $src;
            } elseif (str_starts_with($src, '/')) {
                $src = $origin . $src;
            }
            $rel = destoon_rel($src);
            if (!$rel) {
                return $m[1] . $src;
            }
            download_rel($public, $origin, $rel);
            if ($thumb === null && strlen($rel) <= 191) {
                $thumb = $rel;
            }
            return $m[1] . $rel;
        }, $body) ?? $body;
        $summary = plain($body, 160);
        try {
            $insert->execute([
                $catIds[$job['slug']],
                $title,
                $thumb,
                $source,
                $source,
                $origin . '/news/show.php?itemid=' . $itemId,
                $summary,
                $body,
                'news',
                0,
                0,
                'pending',
                $published,
                $views,
                0,
                0,
                $itemId,
                $now,
                $now,
            ]);
            $ok++;
            $bySlug[$job['slug']]++;
        } catch (Throwable $e) {
            $fail++;
            echo "fail item {$itemId} " . $e->getMessage() . "\n";
        }
    }
    if (($i / $batchSize) % 25 === 0) {
        echo "progress {$ok} ok, {$fail} fail, index {$i}/{$n}\n";
    }
}

echo 'DONE ok=' . $ok . ' fail=' . $fail . PHP_EOL;
foreach ($bySlug as $slug => $count) {
    echo "slug {$slug} {$count}\n";
}
