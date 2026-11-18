<?php
/** 补询盘方买家 + 回填「收到询盘」信件的 from_user_id */
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

$now = date('Y-m-d H:i:s');
$buyerUser = 'test_buyer_inquiry';
$st = $pdo->prepare('SELECT id FROM users WHERE username=? LIMIT 1');
$st->execute([$buyerUser]);
$buyerId = (int) ($st->fetchColumn() ?: 0);
if ($buyerId <= 0) {
    $mobile = '1389000'.substr((string) time(), -4);
    $hash = password_hash('Test123456', PASSWORD_BCRYPT);
    $pdo->prepare("INSERT INTO users (username,password,name,email,mobile,member_type,status,group_code,created_at,updated_at) VALUES (?,?,?,?,?,'personal','active','personal',?,?)")
        ->execute([$buyerUser, $hash, '测试采购小李', 'test_buyer_inquiry@yjgmall.test', $mobile, $now, $now]);
    $buyerId = (int) $pdo->lastInsertId();
}

// 联调询盘绑上买家
$pdo->prepare("UPDATE inquiries SET user_id=? WHERE title LIKE '联调询盘%' AND (user_id IS NULL OR user_id=0)")
    ->execute([$buyerId]);

// 回填：标题「收到询盘：xxx」且 from 为空 → 用对应询盘的 user_id
$msgs = $pdo->query("SELECT id, user_id, title FROM messages WHERE (from_user_id IS NULL OR from_user_id=0) AND title LIKE '收到询盘：%'")->fetchAll(PDO::FETCH_ASSOC);
$fixed = 0;
foreach ($msgs as $m) {
    $title = (string) $m['title'];
    $inqTitle = trim(mb_substr($title, mb_strlen('收到询盘：')));
    $st = $pdo->prepare('SELECT user_id FROM inquiries WHERE title=? AND to_user_id=? ORDER BY id DESC LIMIT 1');
    $st->execute([$inqTitle, (int) $m['user_id']]);
    $from = (int) ($st->fetchColumn() ?: 0);
    if ($from <= 0) {
        $st = $pdo->prepare('SELECT user_id FROM inquiries WHERE to_user_id=? AND user_id IS NOT NULL ORDER BY id DESC LIMIT 1');
        $st->execute([(int) $m['user_id']]);
        $from = (int) ($st->fetchColumn() ?: 0);
    }
    if ($from > 0) {
        $pdo->prepare('UPDATE messages SET from_user_id=? WHERE id=?')->execute([$from, (int) $m['id']]);
        $fixed++;
    }
}

echo json_encode([
    'buyer_id' => $buyerId,
    'buyer_username' => $buyerUser,
    'buyer_password' => 'Test123456',
    'messages_fixed' => $fixed,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), PHP_EOL;
