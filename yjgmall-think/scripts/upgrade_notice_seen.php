<?php
$envFile = dirname(__DIR__) . '/.env';
if (!is_file($envFile)) {
    $envFile = '/www/wwwroot/g.aifeili.cn/.env';
}
$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
        continue;
    }
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, " \t\"'");
}
$pdo = new PDO(
    sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $env['DB_HOST'] ?? '127.0.0.1',
        $env['DB_PORT'] ?? '3306',
        $env['DB_DATABASE'] ?? $env['DB_NAME'] ?? ''
    ),
    $env['DB_USERNAME'] ?? $env['DB_USER'] ?? 'root',
    $env['DB_PASSWORD'] ?? $env['DB_PASS'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
function colExists(PDO $pdo, string $table, string $col): bool
{
    $st = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
    $st->execute([$col]);
    return (bool) $st->fetch();
}
$adds = [
    'fav_seen_id' => "ALTER TABLE `users` ADD COLUMN `fav_seen_id` INT NOT NULL DEFAULT 0",
    'announce_seen_id' => "ALTER TABLE `users` ADD COLUMN `announce_seen_id` INT NOT NULL DEFAULT 0",
];
foreach ($adds as $col => $sql) {
    if (colExists($pdo, 'users', $col)) {
        echo "OK users.{$col}\n";
        continue;
    }
    $pdo->exec($sql);
    echo "ADD users.{$col}\n";
}
echo "NOTICE_SEEN_OK\n";
