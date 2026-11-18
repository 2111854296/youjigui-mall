<?php
/**
 * 把还不是 6 位的用户主键改成不重复的随机号，并同步所有引用列。
 * 可重复执行：已经是 100000–999999 的账号跳过。
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
$db = $env['DB_DATABASE'] ?? $env['DB_NAME'] ?? '';

function qi(string $name): string
{
    return '`' . str_replace('`', '``', $name) . '`';
}

if (in_array('--list', $argv ?? [], true)) {
    $st = $pdo->prepare(
        "SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = ?
           AND (
             COLUMN_NAME = 'user_id'
             OR COLUMN_NAME LIKE '%\\_user_id'
             OR COLUMN_NAME IN ('peer_id','tokenable_id','uid','member_id','friend_id','owner_id')
           )
         ORDER BY TABLE_NAME, COLUMN_NAME"
    );
    $st->execute([$db]);
    foreach ($st as $row) {
        echo $row['TABLE_NAME'] . '.' . $row['COLUMN_NAME'] . ' ' . $row['DATA_TYPE'] . "\n";
    }
    echo "---- FK ----\n";
    $fk = $pdo->prepare(
        "SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
         FROM information_schema.KEY_COLUMN_USAGE
         WHERE TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME IS NOT NULL
         ORDER BY REFERENCED_TABLE_NAME, TABLE_NAME"
    );
    $fk->execute([$db]);
    foreach ($fk as $row) {
        echo $row['TABLE_NAME'] . '.' . $row['COLUMN_NAME']
            . ' -> ' . $row['REFERENCED_TABLE_NAME'] . '.' . $row['REFERENCED_COLUMN_NAME'] . "\n";
    }
    $idcol = $pdo->query("SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'id'")->fetchColumn();
    $cnt = $pdo->query("SELECT COUNT(*) c, SUM(id < 100000 OR id > 999999) need, MIN(id) mn, MAX(id) mx FROM users")->fetch(PDO::FETCH_ASSOC);
    echo "users.id " . $idcol . " users=" . $cnt['c'] . " need=" . $cnt['need'] . " min=" . $cnt['mn'] . " max=" . $cnt['mx'] . "\n";
    exit(0);
}

$cols = $pdo->prepare(
    "SELECT TABLE_NAME, COLUMN_NAME
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = ?
       AND TABLE_NAME <> 'users'
       AND (
         COLUMN_NAME = 'user_id'
         OR COLUMN_NAME LIKE '%\\_user_id'
         OR COLUMN_NAME = 'peer_id'
       )"
);
$cols->execute([$db]);
$targets = $cols->fetchAll(PDO::FETCH_ASSOC);
$targets[] = ['TABLE_NAME' => 'friendships', 'COLUMN_NAME' => 'friend_id'];
$targets[] = ['TABLE_NAME' => 'users', 'COLUMN_NAME' => 'invited_by_user_id'];
$seen = [];
$targets = array_values(array_filter($targets, function ($t) use (&$seen) {
    $key = $t['TABLE_NAME'] . '.' . $t['COLUMN_NAME'];
    if ($key === 'users.id' || isset($seen[$key])) {
        return false;
    }
    $seen[$key] = true;
    return true;
}));

$users = $pdo->query("SELECT id FROM users WHERE id < 100000 OR id > 999999 ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
if (!$users) {
    echo "USER_ID_MIGRATE_NONE\n";
    exit(0);
}

$used = [];
foreach ($pdo->query("SELECT id FROM users") as $row) {
    $used[(int) $row['id']] = true;
}
$map = [];
foreach ($users as $old) {
    $old = (int) $old;
    for ($i = 0; $i < 50; $i++) {
        $nid = random_int(100000, 999999);
        if (!isset($used[$nid])) {
            $used[$nid] = true;
            $map[$old] = $nid;
            break;
        }
    }
    if (!isset($map[$old])) {
        fwrite(STDERR, "alloc failed for {$old}\n");
        exit(1);
    }
}

$pdo->beginTransaction();
try {
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    // 先挪到 200 万以上的临时号，避免新旧号码互相覆盖，也避开无符号主键不能为负
    $park = $pdo->prepare('UPDATE users SET id = ? WHERE id = ?');
    foreach ($map as $old => $nid) {
        $park->execute([2000000 + $old, $old]);
    }
    $setUser = $pdo->prepare('UPDATE users SET id = ? WHERE id = ?');
    foreach ($map as $old => $nid) {
        $setUser->execute([$nid, 2000000 + $old]);
        foreach ($targets as $t) {
            $sql = 'UPDATE ' . qi($t['TABLE_NAME']) . ' SET ' . qi($t['COLUMN_NAME'])
                . ' = ? WHERE ' . qi($t['COLUMN_NAME']) . ' = ?';
            $pdo->prepare($sql)->execute([$nid, $old]);
        }
        $tok = $pdo->prepare(
            "UPDATE personal_access_tokens SET tokenable_id = ? WHERE tokenable_id = ? AND tokenable_type LIKE '%User'"
        );
        $tok->execute([$nid, $old]);
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

$orphans = 0;
foreach ($targets as $t) {
    $sql = 'SELECT COUNT(*) FROM ' . qi($t['TABLE_NAME']) . ' c LEFT JOIN users u ON u.id = c.' . qi($t['COLUMN_NAME'])
        . ' WHERE c.' . qi($t['COLUMN_NAME']) . ' IS NOT NULL AND c.' . qi($t['COLUMN_NAME']) . ' <> 0 AND u.id IS NULL';
    $n = (int) $pdo->query($sql)->fetchColumn();
    if ($n > 0) {
        echo 'orphan ' . $t['TABLE_NAME'] . '.' . $t['COLUMN_NAME'] . '=' . $n . "\n";
        $orphans += $n;
    }
}
$left = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE id < 100000 OR id > 999999")->fetchColumn();
if ($left !== 0) {
    fwrite(STDERR, "still short ids {$left}\n");
    exit(1);
}
$max = (int) $pdo->query('SELECT IFNULL(MAX(id),0) FROM users')->fetchColumn();
if ($max < 1000000) {
    $pdo->exec('ALTER TABLE users AUTO_INCREMENT = 1000000');
}
$sample = $pdo->query("SELECT id FROM users WHERE username = '2111854296'")->fetchColumn();
echo 'moved=' . count($map) . ' left=' . $left . ' orphans=' . $orphans . ' shown=' . (int) $sample . "\n";
echo "USER_ID_MIGRATE_OK\n";
