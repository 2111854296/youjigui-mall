<?php
require dirname(__DIR__) . '/vendor/autoload.php';
$app = new think\App();
$app->initialize();

$rows = think\facade\Db::query(
    "SELECT id,name,slug,module,parent_id FROM categories
     WHERE module LIKE '%news%' OR slug LIKE 'news-%'
        OR name LIKE '%资讯%' OR name LIKE '%产能%' OR name LIKE '%行情%'
        OR name LIKE '%网评%' OR name LIKE '%进出口%' OR name LIKE '%报告%'
     ORDER BY parent_id, sort, id"
);
echo "CATS:\n";
foreach ($rows as $r) {
    echo "{$r['id']}\t{$r['parent_id']}\t{$r['module']}\t{$r['slug']}\t{$r['name']}\n";
}
echo "TYPES:\n";
foreach (think\facade\Db::query('SELECT type, COUNT(*) c FROM articles GROUP BY type') as $r) {
    echo "{$r['type']} {$r['c']}\n";
}
$n = think\facade\Db::query('SELECT COUNT(*) c FROM articles WHERE category_id IS NULL OR category_id=0');
echo 'no_cat=' . $n[0]['c'] . "\n";
$b = think\facade\Db::query('SELECT COUNT(*) c FROM articles WHERE quote_product_id IS NOT NULL');
echo 'bound_qp=' . $b[0]['c'] . "\n";
echo "SAMPLE quote arts:\n";
foreach (think\facade\Db::query('SELECT id,title,type,category_id,quote_product_id FROM articles WHERE type=\'quote\' ORDER BY id DESC LIMIT 12') as $r) {
    echo "{$r['id']}\tcat={$r['category_id']}\tqp={$r['quote_product_id']}\t{$r['title']}\n";
}
