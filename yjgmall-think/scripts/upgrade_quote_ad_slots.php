<?php
/**
 * 每个行情品种 3 个详情页广告位。
 * php scripts/upgrade_quote_ad_slots.php
 */
require __DIR__ . '/../vendor/autoload.php';

$app = new think\App();
$app->initialize();

use app\common\QuoteAdSlots;
use app\model\QuoteProduct;

$rows = QuoteProduct::order('id', 'asc')->select();
foreach ($rows as $row) {
    QuoteAdSlots::sync($row);
    echo (int) $row->id, "\t", QuoteAdSlots::shortName((string) $row->name), "\n";
}
echo "QUOTE_ADS_OK\n";
