<?php
/**
 * 造一条可测的「已审企业 + 待审询盘」
 */
$root = '/www/wwwroot/g.aifeili.cn';
chdir($root);

$lines = @file($root.'/.env') ?: [];
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
$pdo = new PDO("mysql:host=$h;dbname=$n;charset=utf8mb4", $u, $p, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

function colExists(PDO $pdo, string $table, string $col): bool
{
    $st = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $st->execute([$col]);
    return (bool) $st->fetch();
}

$now = date('Y-m-d H:i:s');
$username = 'test_ent_inquiry';
$passwordHash = password_hash('Test123456', PASSWORD_BCRYPT);

// 1) 企业会员账号
$st = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
$st->execute([$username]);
$userId = (int) ($st->fetchColumn() ?: 0);

$email = 'test_ent_inquiry@yjgmall.test';
if ($userId <= 0) {
    $fields = ['username', 'password', 'name', 'email', 'company_name', 'member_type', 'status', 'group_code', 'is_certified', 'mobile', 'created_at', 'updated_at'];
    $mobile = '1398000'.substr((string) time(), -4);
    $vals = [$username, $passwordHash, '测试企业联系人', $email, '有机硅测试材料有限公司', 'company', 'active', 'enterprise', 1, $mobile, $now, $now];
    $ph = implode(',', array_fill(0, count($fields), '?'));
    $pdo->prepare('INSERT INTO users ('.implode(',', $fields).") VALUES ($ph)")->execute($vals);
    $userId = (int) $pdo->lastInsertId();
} else {
    $mobile = '1398000'.substr((string) time(), -4);
    $pdo->prepare("UPDATE users SET password=?, name='测试企业联系人', email=?, company_name='有机硅测试材料有限公司', member_type='company', status='active', group_code='enterprise', is_certified=1, mobile=?, updated_at=? WHERE id=?")
        ->execute([$passwordHash, $email, $mobile, $now, $userId]);
}

// 2) 企业资料（已通过）
$st = $pdo->prepare('SELECT id FROM companies WHERE user_id = ? ORDER BY id DESC LIMIT 1');
$st->execute([$userId]);
$companyId = (int) ($st->fetchColumn() ?: 0);

$slug = 'test-ent-inquiry-'.substr((string) time(), -6);
if ($companyId <= 0) {
    $fields = ['user_id', 'name', 'slug', 'status', 'contact', 'phone', 'is_certified', 'intro', 'created_at', 'updated_at'];
    $vals = [$userId, '有机硅测试材料有限公司', $slug, 'approved', '测试企业联系人', $mobile, 1, '用于询盘转发联调的测试企业', $now, $now];
    $ph = implode(',', array_fill(0, count($fields), '?'));
    $pdo->prepare('INSERT INTO companies ('.implode(',', $fields).") VALUES ($ph)")->execute($vals);
    $companyId = (int) $pdo->lastInsertId();
} else {
    $pdo->prepare("UPDATE companies SET name='有机硅测试材料有限公司', status='approved', phone=?, is_certified=1, updated_at=? WHERE id=?")
        ->execute([$mobile, $now, $companyId]);
}

// 3) 待审询盘（绑定该企业）
$title = '联调询盘：DMC报价测试';
$content = "你好，需要 DMC 现货 10 吨，请报含税到东莞价。\n（系统联调测试留言，可转发）";
$fields = ['user_id', 'to_user_id', 'target_type', 'target_id', 'title', 'content', 'contact_name', 'contact_phone', 'contact_email', 'status', 'created_at', 'updated_at'];
$vals = [null, $userId, 'company', $companyId, $title, $content, '采购小李', '13800002222', 'buyer-test@yjgmall.test', 'new', $now, $now];
if (colExists($pdo, 'inquiries', 'is_read')) {
    $fields[] = 'is_read';
    $vals[] = 0;
}
$ph = implode(',', array_fill(0, count($fields), '?'));
$pdo->prepare('INSERT INTO inquiries ('.implode(',', $fields).") VALUES ($ph)")->execute($vals);
$inquiryId = (int) $pdo->lastInsertId();

echo json_encode([
    'ok' => true,
    'user_id' => $userId,
    'username' => $username,
    'password' => 'Test123456',
    'company_id' => $companyId,
    'company_name' => '有机硅测试材料有限公司',
    'inquiry_id' => $inquiryId,
    'inquiry_title' => $title,
    'admin_path' => '/admin/r/inquiries',
    'tip' => '后台「信息审核 → 询盘留言」找到该条，点「转发企业」；再用企业账号登录会员中心看询盘管理。',
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), PHP_EOL;
