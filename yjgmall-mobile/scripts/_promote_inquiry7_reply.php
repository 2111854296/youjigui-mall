<?php
/** 把企业已直发的信件内容收回为询盘「回复待审」，方便后台看到 */
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

$st = $pdo->query("SELECT id,content FROM messages WHERE from_user_id=1000000 AND title LIKE '回复：收到询盘：联调询盘%' ORDER BY id DESC LIMIT 1");
$msg = $st->fetch(PDO::FETCH_ASSOC);
$reply = trim((string) ($msg['content'] ?? '3333'));
$now = date('Y-m-d H:i:s');
$pdo->prepare("UPDATE inquiries SET reply=?, status='reply_pending', replied_at=NULL, updated_at=? WHERE id=7")
    ->execute([$reply, $now]);
// 撤回已直达买家的那封（避免未审先到）
if ($msg) {
    $pdo->prepare('DELETE FROM messages WHERE id=?')->execute([(int) $msg['id']]);
}
$row = $pdo->query('SELECT id,status,reply FROM inquiries WHERE id=7')->fetch(PDO::FETCH_ASSOC);
echo json_encode(['ok' => true, 'inquiry' => $row, 'deleted_message_id' => $msg['id'] ?? null], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), PHP_EOL;
