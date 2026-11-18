<?php
/**
 * 按标题关键词给行情资讯自动绑 quote_product_id（仅空值）。
 */
require dirname(__DIR__) . '/vendor/autoload.php';
$app = new think\App();
$app->initialize();

$products = think\facade\Db::query('SELECT id, name FROM quote_products ORDER BY sort, id');
// [match keywords in title] => prefer product name contains
$rules = [
    [['DMC', '二甲基环硅氧烷'], 'DMC'],
    [['D4', '八甲基环四硅氧烷'], 'D4'],
    [['生胶', '甲基乙烯基硅橡胶'], '生胶'],
    [['107胶', '107室温'], '107'],
    [['外资硅油', '进口硅油', '外资品牌'], '外资'],
    [['二甲基硅油'], '二甲基硅油'],
    [['混炼胶'], '混炼胶'],
    [['气相白炭黑', '气相法'], '气相'],
    [['沉淀白炭黑', '沉淀二氧化硅'], '沉淀'],
    [['421#金属硅', '421', '金属硅'], '421'],
    [['工业硅期货', '工业硅'], '工业硅'],
    [['一氯甲烷', '氯甲烷'], '一氯甲烷'],
    [['有机硅助剂'], '有机硅助剂'],
    [['裂解料'], '裂解料'],
    [['废硅胶', '毛边'], '废硅胶'],
];

$arts = think\facade\Db::query(
    "SELECT id, title FROM articles WHERE type='quote' AND (quote_product_id IS NULL OR quote_product_id=0)"
);
$n = 0;
foreach ($arts as $a) {
    $title = (string) $a['title'];
    $pid = 0;
    $pname = '';
    foreach ($rules as [$kws, $needle]) {
        $hit = false;
        foreach ($kws as $k) {
            if ($k !== '' && str_contains($title, $k)) {
                $hit = true;
                break;
            }
        }
        if (!$hit) {
            continue;
        }
        foreach ($products as $p) {
            if (str_contains((string) $p['name'], $needle)) {
                $pid = (int) $p['id'];
                $pname = (string) $p['name'];
                break 2;
            }
        }
    }
    if ($pid) {
        think\facade\Db::execute('UPDATE articles SET quote_product_id=? WHERE id=?', [$pid, (int) $a['id']]);
        echo "BIND {$a['id']} -> {$pid} {$pname} | {$title}\n";
        $n++;
    }
}
echo "DONE bound={$n}\n";
