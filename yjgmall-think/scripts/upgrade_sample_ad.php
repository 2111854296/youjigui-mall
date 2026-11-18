<?php
/** php scripts/upgrade_sample_ad.php */
require __DIR__ . '/../vendor/autoload.php';
$app = new think\App();
$app->initialize();
use think\facade\Db;

$code = 'sample_top';
$row = Db::name('ad_places')->where('code', $code)->find();
$now = date('Y-m-d H:i:s');
if ($row) {
    Db::name('ad_places')->where('id', $row['id'])->update([
        'name' => '样品专区轮播',
        'size' => '965x366',
        'type' => 'image',
        'sort' => 5,
        'is_active' => 1,
        'updated_at' => $now,
    ]);
    echo "update {$code}\n";
} else {
    Db::name('ad_places')->insert([
        'code' => $code,
        'name' => '样品专区轮播',
        'size' => '965x366',
        'type' => 'image',
        'price_note' => '面议',
        'sort' => 5,
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    echo "insert {$code}\n";
}
echo "SAMPLE_AD_OK\n";
