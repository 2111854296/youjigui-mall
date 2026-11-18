<?php
/**
 * 供应排序权重：默认为 0，数字越大越靠前。
 * php scripts/upgrade_supply_weight.php
 */
require __DIR__ . '/../vendor/autoload.php';

$app = new think\App();
$app->initialize();

use think\facade\Db;

function hasColumn(string $table, string $col): bool
{
    $rows = Db::query(
        'SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
        [$table, $col]
    );
    return (int) ($rows[0]['c'] ?? 0) > 0;
}

if (hasColumn('supplies', 'weight')) {
    echo "skip supplies.weight\n";
} else {
    echo "add supplies.weight ... ";
    Db::execute("ALTER TABLE `supplies` ADD COLUMN `weight` INT NOT NULL DEFAULT 0 COMMENT '排序权重，同会员等级时越大越靠前'");
    echo "ok\n";
}

echo "DONE\n";
