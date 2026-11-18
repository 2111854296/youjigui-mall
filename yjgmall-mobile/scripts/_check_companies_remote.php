<?php
$lines = @file('/www/wwwroot/g.aifeili.cn/.env') ?: [];
$env = [];
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, " \t\"'");
}
$h = $env['DB_HOST'] ?? '127.0.0.1';
$n = $env['DB_NAME'] ?? 'yjgmall';
$u = $env['DB_USER'] ?? 'root';
$p = $env['DB_PASS'] ?? ($env['DB_PASSWORD'] ?? '');
$pdo = new PDO("mysql:host=$h;dbname=$n;charset=utf8mb4", $u, $p);
$queries = [
    'SELECT status, COUNT(*) c FROM companies GROUP BY status',
    'SELECT COUNT(*) c FROM users WHERE member_type="company" OR group_code IN ("enterprise","company")',
    'SELECT id,name,status FROM companies ORDER BY id DESC LIMIT 8',
    'SELECT id,title,to_user_id,target_type,target_id,status FROM inquiries ORDER BY id DESC LIMIT 8',
];
foreach ($queries as $sql) {
    echo "SQL: $sql\n";
    foreach ($pdo->query($sql) as $row) {
        echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
    }
    echo "\n";
}
