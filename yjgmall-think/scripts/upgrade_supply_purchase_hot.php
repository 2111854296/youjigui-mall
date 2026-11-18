<?php
/**
 * Add is_hot to supplies / purchases
 * php scripts/upgrade_supply_purchase_hot.php
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

$alters = [
    'supplies' => "ALTER TABLE `supplies` ADD COLUMN `is_hot` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '热门标记（前台显示热）'",
    'purchases' => "ALTER TABLE `purchases` ADD COLUMN `is_hot` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '热门标记（前台显示热）'",
];

foreach ($alters as $table => $sql) {
    if (hasColumn($table, 'is_hot')) {
        echo "skip $table.is_hot\n";
        continue;
    }
    echo "add $table.is_hot ... ";
    Db::execute($sql);
    echo "ok\n";
}

echo "DONE\n";
