<?php
$envFile = '/www/wwwroot/g.aifeili.cn/.env';
$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, " \t\"'");
}
$pdo = new PDO(
    'mysql:host=' . ($env['DB_HOST'] ?? '127.0.0.1') . ';port=' . ($env['DB_PORT'] ?? '3306') . ';dbname=' . ($env['DB_NAME'] ?? $env['DB_DATABASE']) . ';charset=utf8mb4',
    $env['DB_USER'] ?? $env['DB_USERNAME'] ?? 'root',
    $env['DB_PASS'] ?? $env['DB_PASSWORD'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$pdo->exec("UPDATE specials SET status='published'");
$rows = $pdo->query('SELECT id, name FROM specials')->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    $sid = (int) $r['id'];
    $c = (int) $pdo->query("SELECT COUNT(*) FROM special_items WHERE special_id={$sid}")->fetchColumn();
    echo "special {$sid} {$r['name']} items={$c}\n";
    if ($c === 0) {
        $st = $pdo->prepare('INSERT INTO special_items(special_id,item_type,item_id,title,summary,link,sort,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,NOW(),NOW()),(?,?,?,?,?,?,?,?,NOW(),NOW())');
        $st->execute([
            $sid, 'article', 1, '专题入门：有机硅行情解读', '示例条目', '/news/1', 1, 'published',
            $sid, 'product', 1, '相关商城商品', '示例商品条目', '/mall/1', 2, 'published',
        ]);
        echo "  seeded\n";
    }
}
echo "OK\n";
