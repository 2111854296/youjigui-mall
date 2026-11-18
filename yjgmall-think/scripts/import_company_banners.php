<?php
/**
 * Import Destoon company shop ad-banners (img width=100%) into companies.banner.
 * Run on a machine that can reach yjgmall.com (server IP may be WAF-blocked).
 *
 * Usage: php scripts/import_company_banners.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . '/public';
$origin = getenv('ORIGIN') ?: 'https://www.yjgmall.com';

$env = parse_ini_file($root . '/.env');
$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $env['DB_HOST'] ?? '127.0.0.1', $env['DB_PORT'] ?? '3306', $env['DB_NAME']),
    $env['DB_USER'],
    $env['DB_PASS'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

function http_get(string $url): string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
        CURLOPT_REFERER => 'https://www.yjgmall.com/',
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
    return is_string($body) ? $body : '';
}

function download_to(string $url, string $dest): bool
{
    $dir = dirname($dest);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    $ch = curl_init($url);
    $fp = fopen($dest . '.tmp', 'wb');
    curl_setopt_array($ch, [
        CURLOPT_FILE => $fp,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
        CURLOPT_REFERER => 'https://www.yjgmall.com/',
    ]);
    $ok = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fp);
    if (!$ok || $code !== 200 || filesize($dest . '.tmp') < 800) {
        @unlink($dest . '.tmp');
        return false;
    }
    rename($dest . '.tmp', $dest);
    return true;
}

function extract_banner(string $html): ?string
{
    if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*width=["\']?100%["\']?[^>]*>/i', $html, $m)
        || preg_match('/<img[^>]+width=["\']?100%["\']?[^>]*src=["\']([^"\']+)["\'][^>]*>/i', $html, $m)) {
        $src = trim($m[1]);
        if (str_starts_with($src, '//')) $src = 'https:' . $src;
        return $src;
    }
    return null;
}

function to_rel(string $src, string $origin): ?string
{
    $u = preg_replace('#^https?://[^/]+#i', '', $src) ?: $src;
    $u = explode('?', $u)[0];
    if (str_starts_with($u, '/file/upload/')) return $u;
    if (preg_match('#/company/skin/([^/]+)/([^/?]+)$#i', $u, $m)) {
        return '/file/company-skin/' . $m[1] . '/' . $m[2];
    }
    return null;
}

$rows = $pdo->query("SELECT id, slug, IFNULL(origin_homepage, slug) hp FROM companies WHERE status='approved'")->fetchAll(PDO::FETCH_ASSOC);
$upd = $pdo->prepare('UPDATE companies SET banner=? WHERE id=?');
$ok = 0; $miss = 0;

foreach ($rows as $r) {
    $html = http_get($origin . '/index.php?homepage=' . rawurlencode($r['hp']));
    $src = extract_banner($html);
    if (!$src) { $miss++; continue; }
    if (!str_starts_with($src, 'http')) $src = rtrim($origin, '/') . $src;
    $rel = to_rel($src, $origin);
    if (!$rel) { $miss++; continue; }
    $dest = $public . $rel;
    if (!is_file($dest) || filesize($dest) < 800) {
        if (!download_to($src, $dest)) { $miss++; echo "fail {$r['slug']} $src\n"; continue; }
    }
    $upd->execute([$rel, $r['id']]);
    $ok++;
    echo "ok {$r['slug']} $rel\n";
}

echo "DONE ok=$ok miss=$miss\n";
