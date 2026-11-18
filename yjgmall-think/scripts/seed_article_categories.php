<?php
/**
 * 资讯分类对齐前台「信息资讯」栏目。
 * 用法：php scripts/seed_article_categories.php
 */
require __DIR__ . '/../vendor/autoload.php';
$app = new think\App();
$app->initialize();

$db = think\facade\Db::connect();
$now = date('Y-m-d H:i:s');

$used = array_values(array_unique(array_filter(array_map(
    'intval',
    $db->name('articles')->column('category_id')
))));
echo 'used: ' . json_encode($used) . PHP_EOL;

function freeSlug($db, string $slug, array $keepIds, array $used): void
{
    $rows = $db->name('categories')->where('slug', $slug)->select()->toArray();
    foreach ($rows as $row) {
        $id = (int) $row['id'];
        if (in_array($id, $keepIds, true)) {
            continue;
        }
        if (in_array($id, $used, true)) {
            // 被文章占用则临时改 slug，避免唯一冲突
            $db->name('categories')->where('id', $id)->update([
                'slug' => $slug . '-old-' . $id,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            echo "retarget slug on used #{$id}\n";
            continue;
        }
        $db->name('categories')->where('id', $id)->delete();
        echo "delete slug-conflict #{$id} {$row['name']}\n";
    }
}

$keepMap = [
    31 => ['name' => '有机硅资讯网评', 'slug' => 'news-review', 'sort' => 10],
    32 => ['name' => '价格行情资讯', 'slug' => 'news-price', 'sort' => 20],
    33 => ['name' => '研究报告', 'slug' => 'news-report', 'sort' => 50],
    34 => ['name' => '企业动态', 'slug' => 'news-company', 'sort' => 60],
];
$keepIds = array_keys($keepMap);

// 先释放目标 slug
foreach ($keepMap as $row) {
    freeSlug($db, $row['slug'], $keepIds, $used);
}

foreach ($keepMap as $id => $row) {
    $n = $db->name('categories')->where('id', $id)->update([
        'parent_id' => null,
        'module' => 'article',
        'name' => $row['name'],
        'slug' => $row['slug'],
        'sort' => $row['sort'],
        'is_active' => 1,
        'updated_at' => $now,
    ]);
    echo "update keep#{$id} {$row['name']} n={$n}\n";
}

$extra = [
    ['name' => '产能产量', 'slug' => 'news-capacity', 'sort' => 30],
    ['name' => '进出口', 'slug' => 'news-trade', 'sort' => 40],
    ['name' => '对话大咖', 'slug' => 'news-talk', 'sort' => 70],
    ['name' => '企业活动', 'slug' => 'news-event', 'sort' => 80],
    ['name' => '新品推荐', 'slug' => 'news-product', 'sort' => 90],
    ['name' => '技术分享', 'slug' => 'news-tech', 'sort' => 100],
    ['name' => '展会动态', 'slug' => 'news-expo', 'sort' => 110],
];

foreach ($extra as $row) {
    $byName = $db->name('categories')
        ->where('module', 'article')
        ->where('name', $row['name'])
        ->whereNull('parent_id')
        ->find();
    if ($byName) {
        freeSlug($db, $row['slug'], [(int) $byName['id']], $used);
        $db->name('categories')->where('id', $byName['id'])->update([
            'slug' => $row['slug'],
            'sort' => $row['sort'],
            'is_active' => 1,
            'updated_at' => $now,
        ]);
        echo "ok #{$byName['id']} {$row['name']}\n";
        continue;
    }
    freeSlug($db, $row['slug'], $keepIds, $used);
    $id = $db->name('categories')->insertGetId([
        'parent_id' => null,
        'name' => $row['name'],
        'slug' => $row['slug'],
        'module' => 'article',
        'sort' => $row['sort'],
        'is_active' => 1,
        'icon' => null,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    echo "insert #{$id} {$row['name']}\n";
}

// 清理同名重复（非 keep、无文章）
$names = array_merge(array_column($keepMap, 'name'), array_column($extra, 'name'));
foreach ($names as $name) {
    $rows = $db->name('categories')
        ->where('module', 'article')
        ->where('name', $name)
        ->whereNull('parent_id')
        ->order('id', 'asc')
        ->select()
        ->toArray();
    if (count($rows) <= 1) {
        continue;
    }
    $keeper = null;
    foreach ($rows as $r) {
        if (in_array((int) $r['id'], $keepIds, true)) {
            $keeper = (int) $r['id'];
            break;
        }
    }
    if ($keeper === null) {
        $keeper = (int) $rows[0]['id'];
    }
    foreach ($rows as $r) {
        $id = (int) $r['id'];
        if ($id === $keeper) {
            continue;
        }
        if (in_array($id, $used, true)) {
            echo "cannot delete used dup #{$id} {$name}\n";
            continue;
        }
        $db->name('categories')->where('id', $id)->delete();
        echo "delete name-dup #{$id} {$name}\n";
    }
}

$final = $db->name('categories')
    ->where('module', 'article')
    ->whereNull('parent_id')
    ->where('is_active', 1)
    ->order('sort', 'asc')
    ->order('id', 'asc')
    ->select()
    ->toArray();
echo "FINAL:\n" . json_encode(array_map(static fn ($r) => [
    'id' => (int) $r['id'],
    'name' => $r['name'],
    'slug' => $r['slug'],
    'sort' => (int) $r['sort'],
], $final), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
