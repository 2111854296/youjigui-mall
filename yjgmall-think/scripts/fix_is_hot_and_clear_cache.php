<?php
/**
 * Ensure is_hot columns + dump recent purchases + clear home cache keys.
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
$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $env['DB_HOST'] ?? '127.0.0.1',
        $env['DB_PORT'] ?? '3306',
        $env['DB_NAME'] ?? 'yjgmall_g'),
    $env['DB_USER'] ?? $env['DB_USERNAME'] ?? 'root',
    $env['DB_PASS'] ?? $env['DB_PASSWORD'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

function hasColumn(PDO $pdo, string $table, string $col): bool {
    $st = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
    $st->execute([$table, $col]);
    return (int) $st->fetchColumn() > 0;
}

foreach (['supplies', 'purchases'] as $t) {
    if (hasColumn($pdo, $t, 'is_hot')) {
        echo "ok $t.is_hot\n";
    } else {
        $pdo->exec("ALTER TABLE `$t` ADD COLUMN `is_hot` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '热门标记'");
        echo "add $t.is_hot\n";
    }
}

echo "--- purchases top ---\n";
foreach ($pdo->query('SELECT id, title, is_hot, status FROM purchases ORDER BY refreshed_at DESC LIMIT 8') as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
}

// Clear file cache if present
$cacheDir = dirname(__DIR__) . '/runtime/cache';
if (is_dir($cacheDir)) {
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cacheDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    $n = 0;
    foreach ($it as $f) {
        $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        $n++;
    }
    echo "cleared runtime/cache entries~$n\n";
}

echo "DONE\n";
