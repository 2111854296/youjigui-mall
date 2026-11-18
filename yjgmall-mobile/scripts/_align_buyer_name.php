<?php
$root = '/www/wwwroot/g.aifeili.cn';
$lines = @file($root.'/.env') ?: [];
$env = [];
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, " \t\"'");
}
$pdo = new PDO(
    'mysql:host='.($env['DB_HOST'] ?? '127.0.0.1').';dbname='.($env['DB_NAME'] ?? 'yjgmall').';charset=utf8mb4',
    $env['DB_USER'] ?? 'root',
    $env['DB_PASS'] ?? ($env['DB_PASSWORD'] ?? ''),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$pdo->prepare("UPDATE users SET name='采购小李' WHERE username='test_buyer_inquiry'")->execute();
$row = $pdo->query("SELECT id,username,name FROM users WHERE username='test_buyer_inquiry'")->fetch(PDO::FETCH_ASSOC);
echo json_encode($row, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), PHP_EOL;
