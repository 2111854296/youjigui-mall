<?php
$pdo = new PDO(
    'mysql:host=127.0.0.1;dbname=yjgmall_g;charset=utf8mb4',
    'yjgmall_g',
    'TkzDCT7GkRf5TPer',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$user = $pdo->query("SELECT id FROM users WHERE username='admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    fwrite(STDERR, "no admin\n");
    exit(1);
}
$uid = (int) $user['id'];
$plain = bin2hex(random_bytes(20));
$hash = hash('sha256', $plain);
$now = date('Y-m-d H:i:s');
$stmt = $pdo->prepare('INSERT INTO personal_access_tokens (tokenable_type, tokenable_id, name, token, abilities, created_at, updated_at) VALUES (?,?,?,?,?,?,?)');
$stmt->execute(['App\\Models\\User', $uid, 'browser-probe', $hash, '["*"]', $now, $now]);
$id = (int) $pdo->lastInsertId();
echo $id . '|' . $plain . "\n";
