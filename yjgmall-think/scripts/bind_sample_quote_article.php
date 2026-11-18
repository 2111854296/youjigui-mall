<?php
/**
 * Bind sample quote article to DMC quote_product for smoke test.
 */
require dirname(__DIR__) . '/vendor/autoload.php';
$app = new think\App();
$app->initialize();

try {
    $qp = think\facade\Db::query("SELECT id,name FROM quote_products WHERE name LIKE '%DMC%' OR name='二甲基环硅氧烷混合物（DMC）' LIMIT 5");
    echo "QP:\n";
    foreach ($qp as $r) {
        echo "  {$r['id']} {$r['name']}\n";
    }
    $dmcId = 0;
    foreach ($qp as $r) {
        if (str_contains((string) $r['name'], 'DMC')) {
            $dmcId = (int) $r['id'];
            break;
        }
    }
    if (!$dmcId && $qp) {
        $dmcId = (int) $qp[0]['id'];
    }
    if (!$dmcId) {
        echo "NO_DMC\n";
        exit(1);
    }
    $n = think\facade\Db::execute(
        'UPDATE articles SET quote_product_id=? WHERE id=? AND type=?',
        [$dmcId, 4, 'quote']
    );
    echo "UPDATE_art4=$n dmc=$dmcId\n";
    $row = think\facade\Db::query('SELECT id,title,type,quote_product_id FROM articles WHERE id=4');
    print_r($row);
} catch (Throwable $e) {
    echo 'ERR:' . $e->getMessage() . "\n";
    exit(1);
}
