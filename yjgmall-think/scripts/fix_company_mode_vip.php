<?php
$envFile = '/www/wwwroot/g.aifeili.cn/.env';
$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
    [$k, $v] = array_map('trim', explode('=', $line, 2));
    $env[$k] = trim($v, "\"'");
}
$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $env['DB_HOST'] ?? '127.0.0.1', $env['DB_PORT'] ?? '3306', $env['DB_NAME'] ?? 'yjgmall_g'),
    $env['DB_USER'] ?? $env['DB_USERNAME'] ?? 'root',
    $env['DB_PASS'] ?? $env['DB_PASSWORD'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "=== columns ===\n";
foreach ($pdo->query("SHOW COLUMNS FROM companies WHERE Field IN ('scale','business_mode','tags','vip_level')") as $r) {
    echo $r['Field'] . ' | ' . $r['Type'] . "\n";
}

$upd = $pdo->prepare('UPDATE companies SET business_mode=? WHERE id=?');
$n = 0;
foreach ($pdo->query("SELECT id, business, business_mode FROM companies WHERE business IS NOT NULL AND business!=''") as $r) {
    if (trim((string) $r['business_mode']) !== '') continue;
    $raw = (string) $r['business'];
    $mode = null;
    if (preg_match('/贸易|经销|代理|进出口/u', $raw)) $mode = '贸易商';
    elseif (preg_match('/服务|物流|检测|展览|搭建|会议/u', $raw)) $mode = '服务商';
    elseif (preg_match('/生产|制造|研发/u', $raw)) $mode = '制造商';
    if ($mode) { $upd->execute([$mode, $r['id']]); $n++; }
}
echo "filled_mode=$n\n";

echo "=== sample ===\n";
foreach ($pdo->query("SELECT id,name,scale,business_mode,tags,vip_level,is_featured FROM companies WHERE name LIKE '%双键%' OR name LIKE '%博寒%' OR name LIKE '%硅达%' LIMIT 5") as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
}
echo "DONE\n";
