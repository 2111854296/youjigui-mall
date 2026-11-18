<?php
/**
 * Temporary captcha diagnostic — delete after use
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/../vendor/autoload.php';
$app = new think\App();
$app->initialize();

echo "gd=" . (extension_loaded('gd') ? '1' : '0') . "\n";
echo "cache_default=" . config('cache.default') . "\n";

try {
    \think\facade\Cache::set('captcha_probe', 'ok', 60);
    echo "cache_set=" . (\think\facade\Cache::get('captcha_probe') === 'ok' ? '1' : '0') . "\n";
} catch (Throwable $e) {
    echo "cache_err=" . $e->getMessage() . "\n";
}

try {
    $r = app\common\SlideCaptcha::generate();
    echo "gen_ok=1\n";
    echo "key=" . substr($r['captcha_key'], 0, 12) . "\n";
    echo "bg_len=" . strlen($r['bg']) . "\n";
    echo "slice_len=" . strlen($r['slice']) . "\n";
} catch (Throwable $e) {
    echo "gen_err=" . $e->getMessage() . "\n";
    echo $e->getFile() . ':' . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}

// DB hot counts
try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', env('DB_HOST'), env('DB_PORT', '3306'), env('DB_NAME')),
        env('DB_USER'),
        env('DB_PASS'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    foreach (['supplies', 'purchases'] as $t) {
        $c = (int) $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        $h = (int) $pdo->query("SELECT COUNT(*) FROM `$t` WHERE is_hot=1")->fetchColumn();
        echo "$t total=$c hot=$h\n";
    }
} catch (Throwable $e) {
    echo "db_err=" . $e->getMessage() . "\n";
}
