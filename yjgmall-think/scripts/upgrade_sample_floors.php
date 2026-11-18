<?php
/**
 * 样品专区每个分类楼层 5 个商品位，和首页 floor_*、优质产品无关。
 * php scripts/upgrade_sample_floors.php
 */
require __DIR__ . '/../vendor/autoload.php';

$app = new think\App();
$app->initialize();

use app\common\FloorSlots;
use think\facade\Db;

$now = date('Y-m-d H:i:s');
$upsert = function (string $code, string $name, int $sort) use ($now): void {
    $row = Db::name('ad_places')->where('code', $code)->find();
    $data = [
        'name' => $name,
        'size' => '商品卡',
        'type' => 'image',
        'sort' => $sort,
        'is_active' => 1,
        'updated_at' => $now,
    ];
    if ($row) {
        Db::name('ad_places')->where('id', $row['id'])->update($data);
        echo "update {$code}\n";
        return;
    }
    Db::name('ad_places')->insert($data + [
        'code' => $code,
        'price_note' => '面议',
        'created_at' => $now,
    ]);
    echo "insert {$code}\n";
};

$sort = 6;
foreach (FloorSlots::MAP as $spec) {
    [$floorBase, $label] = $spec;
    $base = 'sample_'.$floorBase;
    for ($slot = 1; $slot <= 5; $slot++) {
        $upsert($base.'_'.$slot, '样品专区·'.$label.'·第'.$slot.'位', $sort++);
    }
}
echo "SAMPLE_FLOORS_OK\n";
