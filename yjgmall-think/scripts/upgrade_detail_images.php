<?php
/** php scripts/upgrade_detail_images.php */
require __DIR__ . '/../vendor/autoload.php';
$app = new think\App();
$app->initialize();
use think\facade\Db;

$cols = Db::query("SHOW COLUMNS FROM products LIKE 'detail_images'");
if (!$cols) {
    Db::execute('ALTER TABLE products ADD detail_images TEXT NULL');
    echo "added detail_images\n";
} else {
    echo "detail_images exists\n";
}
echo "DETAIL_IMAGES_OK\n";
