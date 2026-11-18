<?php
/** php scripts/upgrade_price_tiers.php */
require __DIR__ . '/../vendor/autoload.php';
$app = new think\App();
$app->initialize();
use think\facade\Db;

$cols = Db::query("SHOW COLUMNS FROM products LIKE 'price_tiers'");
if (!$cols) {
    Db::execute('ALTER TABLE products ADD price_tiers TEXT NULL');
    echo "added price_tiers\n";
} else {
    echo "price_tiers exists\n";
}
echo "PRICE_TIERS_OK\n";
