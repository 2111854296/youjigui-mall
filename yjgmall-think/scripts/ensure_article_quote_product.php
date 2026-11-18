<?php
/**
 * Ensure articles.quote_product_id exists.
 */
$envFile = is_file(__DIR__ . '/../.env') ? __DIR__ . '/../.env' : '/www/wwwroot/g.aifeili.cn/.env';
if (!is_file($envFile)) {
    fwrite(STDERR, "no .env\n");
    exit(1);
}

require dirname(__DIR__) . '/vendor/autoload.php';
$app = new think\App();
$app->initialize();

try {
    $cols = think\facade\Db::query("SHOW COLUMNS FROM `articles` LIKE 'quote_product_id'");
    if (!$cols) {
        think\facade\Db::execute(
            'ALTER TABLE `articles` ADD COLUMN `quote_product_id` INT UNSIGNED NULL DEFAULT NULL AFTER `type`, ADD INDEX `idx_articles_quote_product` (`quote_product_id`)'
        );
        echo "ALTER_OK\n";
    } else {
        echo "COL_EXISTS\n";
    }
    $n = think\facade\Db::query('SELECT COUNT(*) AS c FROM quote_products');
    echo 'QP_COUNT=' . (int) ($n[0]['c'] ?? 0) . "\n";
    $bound = think\facade\Db::query('SELECT COUNT(*) AS c FROM articles WHERE quote_product_id IS NOT NULL AND quote_product_id > 0');
    echo 'BOUND=' . (int) ($bound[0]['c'] ?? 0) . "\n";
} catch (Throwable $e) {
    echo 'ERR:' . $e->getMessage() . "\n";
    exit(1);
}
