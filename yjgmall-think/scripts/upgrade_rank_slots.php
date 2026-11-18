<?php
/**
 * 供应列表前 3 页首条推广位。
 * php scripts/upgrade_rank_slots.php
 */
require __DIR__ . '/../vendor/autoload.php';

$app = new think\App();
$app->initialize();

use think\facade\Db;

$places = [
    ['rank_sell', '供应第1页首条', 30],
    ['rank_sell_2', '供应第2页首条', 31],
    ['rank_sell_3', '供应第3页首条', 32],
];

foreach ($places as [$code, $name, $sort]) {
    $row = Db::name('ad_places')->where('code', $code)->find();
    if ($row) {
        Db::name('ad_places')->where('id', $row['id'])->update([
            'name' => $name,
            'sort' => $sort,
            'is_active' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        echo "update {$code}\n";
    } else {
        Db::name('ad_places')->insert([
            'code' => $code,
            'name' => $name,
            'size' => '--',
            'type' => 'text',
            'price_note' => '面议',
            'sort' => $sort,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        echo "insert {$code}\n";
    }
}

echo "DONE\n";
