<?php
/**
 * Replace generic "轮播 N" titles with company names when link points to /companies/{slug}.
 */
$envFile = is_file(__DIR__ . '/../.env') ? __DIR__ . '/../.env' : '/www/wwwroot/g.aifeili.cn/.env';
$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
        continue;
    }
    [$k, $v] = array_map('trim', explode('=', $line, 2));
    $env[$k] = trim($v, "\"'");
}
$pdo = new PDO(sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $env['DB_HOST'] ?? '127.0.0.1',
    $env['DB_PORT'] ?? '3306',
    $env['DB_NAME'] ?? 'yjgmall_g'
), $env['DB_USER'] ?? 'root', $env['DB_PASS'] ?? $env['DB_PASSWORD'] ?? '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$rows = $pdo->query("SELECT id, title, link FROM ads WHERE is_active=1")->fetchAll(PDO::FETCH_ASSOC);
$upd = $pdo->prepare('UPDATE ads SET title=?, updated_at=NOW() WHERE id=?');
$find = $pdo->prepare('SELECT name FROM companies WHERE slug=? LIMIT 1');
$n = 0;
foreach ($rows as $r) {
    $title = trim((string) $r['title']);
    if (!preg_match('/^轮播\s*\d+$/u', $title)) {
        continue;
    }
    if (!preg_match('#^/companies/([^/?]+)#', (string) $r['link'], $m)) {
        continue;
    }
    $find->execute([$m[1]]);
    $name = $find->fetchColumn();
    if (!$name) {
        continue;
    }
    $upd->execute([$name, (int) $r['id']]);
    $n++;
    echo "ad {$r['id']} -> {$name}\n";
}
echo "updated={$n}\n";
