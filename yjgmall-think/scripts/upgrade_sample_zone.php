<?php
/** php scripts/upgrade_sample_zone.php */
require __DIR__ . '/../vendor/autoload.php';
$app = new think\App();
$app->initialize();
use think\facade\Db;

$cols = Db::query("SHOW COLUMNS FROM products LIKE 'is_sample'");
if (!$cols) {
    Db::execute('ALTER TABLE products ADD is_sample TINYINT NOT NULL DEFAULT 0');
    echo "added is_sample\n";
} else {
    echo "is_sample exists\n";
}
echo "SAMPLE_ZONE_OK\n";
