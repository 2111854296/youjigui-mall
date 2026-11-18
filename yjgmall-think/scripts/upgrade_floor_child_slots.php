<?php
/**
 * 首页材料楼层按子分类各 5 个广告位；产品分类推荐供应按大类各 5 张。
 * php scripts/upgrade_floor_child_slots.php
 */
require __DIR__ . '/../vendor/autoload.php';

$app = new think\App();
$app->initialize();

use app\common\FloorSlots;
use think\facade\Db;

function short_slot_name(string $name): string
{
    if (preg_match('/[（(]([^）)]{1,8})[）)]/u', $name, $m)) {
        return $m[1];
    }
    $name = trim($name);

    return mb_strlen($name) > 8 ? mb_substr($name, 0, 8) : $name;
}

try {
    Db::execute('ALTER TABLE ad_places MODIFY code VARCHAR(64) NOT NULL');
    Db::execute('ALTER TABLE ad_places MODIFY name VARCHAR(120) NOT NULL');
} catch (Throwable $e) {
    echo 'alter skip '.$e->getMessage()."\n";
}

$now = date('Y-m-d H:i:s');
$upsert = function (string $code, string $name, string $size, string $type, int $sort) use ($now): void {
    $row = Db::name('ad_places')->where('code', $code)->find();
    $data = [
        'name' => $name,
        'size' => $size,
        'type' => $type,
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

$roots = Db::name('categories')
    ->where('module', 'sell')
    ->whereNull('parent_id')
    ->where('is_active', 1)
    ->order('sort', 'asc')
    ->select();

$floorSort = 200;
$eliteSort = 80;
foreach ($roots as $root) {
    $spec = FloorSlots::MAP[(string) $root['name']] ?? null;
    if (!$spec) {
        continue;
    }
    [$floorBase, $label, $eliteBase] = $spec;
    for ($slot = 1; $slot <= 5; $slot++) {
        $upsert(
            FloorSlots::eliteCode($eliteBase, $slot),
            '产品分类·'.$label.'·第'.$slot.'张',
            '广告图',
            'image',
            $eliteSort++
        );
    }
    $children = Db::name('categories')
        ->where('parent_id', (int) $root['id'])
        ->where('is_active', 1)
        ->order('sort', 'asc')
        ->select();
    $seen = [];
    foreach ($children as $child) {
        $short = short_slot_name((string) $child['name']);
        if ($short === '') {
            continue;
        }
        if (isset($seen[$short])) {
            $short .= (string) $child['id'];
        }
        $seen[$short] = true;
        for ($slot = 1; $slot <= 5; $slot++) {
            $upsert(
                FloorSlots::childCode($floorBase, (int) $child['id'], $slot),
                '首页·'.$label.'·'.$short.'·第'.$slot.'位',
                '商品卡',
                'text',
                $floorSort++
            );
        }
    }
}

$old = ['sell_elite'];
foreach (['mono', 'oil', 'rubber', 'addon', 'equip', 'silane', 'glue', 'resin'] as $k) {
    $old[] = 'floor_'.$k;
    for ($i = 1; $i <= 5; $i++) {
        $old[] = 'floor_'.$k.'_'.$i;
    }
}
for ($i = 1; $i <= 4; $i++) {
    $old[] = 'sell_elite_'.$i;
}
$n = Db::name('ad_places')->whereIn('code', $old)->update(['is_active' => 0, 'updated_at' => $now]);
echo "deactivate {$n}\n";
echo "FLOOR_SLOTS_OK\n";
