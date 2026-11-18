<?php
require dirname(__DIR__) . '/vendor/autoload.php';
$app = new think\App();
$app->initialize();

echo "PRODUCT CATS (module product/mall/sell):\n";
$rows = think\facade\Db::query(
    "SELECT id,parent_id,module,slug,name FROM categories
     WHERE module IN ('product','mall','sell','goods') OR slug LIKE 'cat-%'
     ORDER BY module,parent_id,sort,id LIMIT 80"
);
foreach ($rows as $r) {
    echo "{$r['id']}\t{$r['parent_id']}\t{$r['module']}\t{$r['slug']}\t{$r['name']}\n";
}
echo "\nQUOTE PRODUCTS:\n";
foreach (think\facade\Db::query('SELECT id,name,is_active FROM quote_products ORDER BY sort,id') as $r) {
    echo "{$r['id']}\t{$r['is_active']}\t{$r['name']}\n";
}
echo "\nARTICLE CAT TREE:\n";
$rows = think\facade\Db::query(
    "SELECT id,parent_id,slug,name FROM categories WHERE module='article' ORDER BY parent_id,sort,id"
);
foreach ($rows as $r) {
    echo "{$r['id']}\t{$r['parent_id']}\t{$r['slug']}\t{$r['name']}\n";
}
