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

echo "=== inquiries (recent) ===\n";
foreach ($pdo->query("SELECT id,user_id,to_user_id,title,status,LEFT(reply,80) reply,replied_at,updated_at FROM inquiries ORDER BY id DESC LIMIT 10") as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE)."\n";
}

echo "\n=== messages involving test ent/buyer ===\n";
foreach ($pdo->query("SELECT id,user_id,from_user_id,title,LEFT(content,60) content,created_at FROM messages WHERE user_id IN (1000000,1000001) OR from_user_id IN (1000000,1000001) ORDER BY id DESC LIMIT 20") as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE)."\n";
}

echo "\n=== deployed Inquiry.php reply snippet ===\n";
$f = file_get_contents($root.'/app/controller/api/v1/Inquiry.php');
if (str_contains($f, 'reply_pending')) echo "HAS reply_pending\n"; else echo "MISSING reply_pending\n";
if (str_contains($f, 'Message::create')) echo "STILL has Message::create in Inquiry.php\n"; else echo "no Message::create in Inquiry.php\n";
