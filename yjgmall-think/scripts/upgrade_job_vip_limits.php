<?php
/**
 * Align job entitlements on existing vip_grades (free/silver/gold/diamond).
 * Safe to re-run. Does NOT create new membership tiers.
 */
$envFile = dirname(__DIR__) . '/.env';
if (!is_file($envFile)) {
    fwrite(STDERR, "missing .env\n");
    exit(1);
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

$now = date('Y-m-d H:i:s');
$jobLimits = [
    'free' => [
        'resume_contact_quota_month' => 1,
        'job_sticky_per_month' => 0,
        'jobs_online_limit' => 5,
        'top_price' => 1000,
        'cert_bonus_contacts' => 5,
        'cert_bonus_days' => 30,
    ],
    'silver' => [
        'resume_contact_quota_month' => 15,
        'job_sticky_per_month' => 0,
        'jobs_online_limit' => 10,
        'top_price' => 500,
    ],
    'gold' => [
        'resume_contact_quota_month' => 30,
        'job_sticky_per_month' => 0,
        'jobs_online_limit' => 30,
        'top_price' => 300,
    ],
    'diamond' => [
        'resume_contact_quota_month' => 60,
        'job_sticky_per_month' => 1,
        'jobs_online_limit' => 0,
        'top_price' => 0,
    ],
];

$gst = $pdo->query('SELECT id, code, limits FROM vip_grades');
$gupd = $pdo->prepare('UPDATE vip_grades SET limits=?, updated_at=? WHERE id=?');
$updated = 0;
while ($row = $gst->fetch(PDO::FETCH_ASSOC)) {
    $code = (string) $row['code'];
    if (!isset($jobLimits[$code])) {
        continue;
    }
    $lim = json_decode((string) $row['limits'], true) ?: [];
    if (!is_array($lim)) {
        $lim = [];
    }
    $lim = array_merge($lim, $jobLimits[$code]);
    $gupd->execute([json_encode($lim, JSON_UNESCAPED_UNICODE), $now, $row['id']]);
    echo "PATCH limits {$code} resume_contact={$lim['resume_contact_quota_month']} sticky={$lim['job_sticky_per_month']} online={$lim['jobs_online_limit']} top={$lim['top_price']}\n";
    $updated++;
}

if ($updated < 4) {
    fwrite(STDERR, "WARN: expected 4 grades, updated {$updated}\n");
}
echo "JOB_VIP_LIMITS_OK\n";
