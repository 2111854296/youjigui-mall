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
foreach (['users', 'companies', 'inquiries'] as $t) {
    echo "=== $t ===\n";
    foreach ($pdo->query("SHOW COLUMNS FROM `$t`") as $r) {
        echo $r['Field']."\t".$r['Type']."\t".$r['Null']."\t".$r['Default']."\n";
    }
    echo "\n";
}
echo "=== sample password ===\n";
$r = $pdo->query("SELECT id,username,password,member_type,status FROM users WHERE member_type IN ('company','enterprise') OR group_code='enterprise' LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
print_r($r);
