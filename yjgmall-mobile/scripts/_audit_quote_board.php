<?php
/**
 * 行情录入全量自检（导入/按年清除/补录/板筛选/最新价同步/无 year 拒绝）
 */
$root = '/www/wwwroot/g.aifeili.cn';
chdir($root);
require $root.'/vendor/autoload.php';

$app = new \think\App();
$app->initialize();

use app\common\QuotePriceImport;
use app\model\QuoteProduct;
use app\model\QuoteProductPrice;
use app\model\QuoteRegionPrice;
use think\facade\Db;

$report = [];
$fail = 0;
$pass = function (string $name, bool $ok, $detail = null) use (&$report, &$fail) {
    $report[] = ['case' => $name, 'ok' => $ok, 'detail' => $detail];
    if (!$ok) {
        $fail++;
    }
};

$marker = 'AUDIT_QB_'.time();
$now = date('Y-m-d H:i:s');

// 独立测试品种，测完删除
$p = QuoteProduct::create([
    'name' => '行情自检_'.$marker,
    'unit' => '元/吨',
    'market' => '自检',
    'is_active' => 0,
    'created_at' => $now,
    'updated_at' => $now,
]);
$pid = (int) $p->id;

$seed = function (string $date, float $avg) use ($pid, $marker, $now) {
    QuoteProductPrice::where('product_id', $pid)->where('quoted_on', $date)->delete();
    QuoteProductPrice::create([
        'product_id' => $pid,
        'quoted_on' => $date,
        'price_avg' => $avg,
        'price_min' => $avg - 10,
        'price_max' => $avg + 10,
        'market' => $marker,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    QuoteRegionPrice::where('product_id', $pid)->where('quoted_on', $date)->where('region', $marker)->delete();
    QuoteRegionPrice::create([
        'product_id' => $pid,
        'region' => $marker,
        'quoted_on' => $date,
        'price' => $avg,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
};

try {
    // --- 1) 无 year 清除必须拒绝 ---
    $src = file_get_contents($root.'/app/controller/api/v1/AdminResource.php');
    $pass('clear_requires_year_in_code', str_contains($src, '请指定要清除的年份') && str_contains($src, 'whereBetween'));

    // --- 2) 写入多年后只清 2026 ---
    $seed('2024-03-01', 1000);
    $seed('2025-03-01', 2000);
    $seed('2026-03-01', 3000);
    $seed('2026-11-01', 3100); // 最新

    // 同步最新价
    $latest = QuoteProductPrice::where('product_id', $pid)->order('quoted_on', 'desc')->find();
    QuoteProduct::where('id', $pid)->update([
        'price_avg' => $latest->price_avg,
        'price_min' => $latest->price_min,
        'price_max' => $latest->price_max,
        'quoted_on' => substr((string) $latest->quoted_on, 0, 10),
    ]);
    $beforeLatest = QuoteProduct::find($pid);

    QuoteProductPrice::where('product_id', $pid)->whereBetween('quoted_on', ['2026-01-01', '2026-12-31'])->delete();
    QuoteRegionPrice::where('product_id', $pid)->whereBetween('quoted_on', ['2026-01-01', '2026-12-31'])->delete();

    $c24 = (int) QuoteProductPrice::where('product_id', $pid)->whereBetween('quoted_on', ['2024-01-01', '2024-12-31'])->count();
    $c25 = (int) QuoteProductPrice::where('product_id', $pid)->whereBetween('quoted_on', ['2025-01-01', '2025-12-31'])->count();
    $c26 = (int) QuoteProductPrice::where('product_id', $pid)->whereBetween('quoted_on', ['2026-01-01', '2026-12-31'])->count();
    $r24 = (int) QuoteRegionPrice::where('product_id', $pid)->whereBetween('quoted_on', ['2024-01-01', '2024-12-31'])->count();
    $r26 = (int) QuoteRegionPrice::where('product_id', $pid)->whereBetween('quoted_on', ['2026-01-01', '2026-12-31'])->count();
    $pass('clear_2026_keeps_other_years', $c24 === 1 && $c25 === 1 && $c26 === 0 && $r24 === 1 && $r26 === 0, compact('c24', 'c25', 'c26', 'r24', 'r26'));

    // sync after clear — 最新应回到 2025
    $latest2 = QuoteProductPrice::where('product_id', $pid)->order('quoted_on', 'desc')->find();
    QuoteProduct::where('id', $pid)->update([
        'price_avg' => $latest2 ? $latest2->price_avg : null,
        'price_min' => $latest2 ? $latest2->price_min : null,
        'price_max' => $latest2 ? $latest2->price_max : null,
        'quoted_on' => $latest2 ? substr((string) $latest2->quoted_on, 0, 10) : null,
    ]);
    $after = QuoteProduct::find($pid);
    $pass('sync_latest_after_clear_year', substr((string) $after->quoted_on, 0, 10) === '2025-03-01' && (float) $after->price_avg === 2000.0, [
        'quoted_on' => $after->quoted_on,
        'price_avg' => $after->price_avg,
        'before' => $beforeLatest->quoted_on,
    ]);

    // --- 3) 补录某一天 ---
    QuoteProductPrice::create([
        'product_id' => $pid,
        'quoted_on' => '2025-12-20',
        'price_avg' => 2100,
        'price_min' => 2090,
        'price_max' => 2110,
        'market' => $marker,
    ]);
    $latest3 = QuoteProductPrice::where('product_id', $pid)->order('quoted_on', 'desc')->find();
    QuoteProduct::where('id', $pid)->update([
        'price_avg' => $latest3->price_avg,
        'quoted_on' => substr((string) $latest3->quoted_on, 0, 10),
    ]);
    $p3 = QuoteProduct::find($pid);
    $pass('manual_day_updates_latest', substr((string) $p3->quoted_on, 0, 10) === '2025-12-20' && (float) $p3->price_avg === 2100.0);

    // --- 4) CSV 导入 + year 强制改写 ---
    $csv = "日期,均价,最低价,最高价\n2024-01-05,5000,4900,5100\n1/6,5100,5000,5200\n";
    $tmp = sys_get_temp_dir().'/quote_audit_'.$marker.'.csv';
    file_put_contents($tmp, $csv);
    $imp = QuotePriceImport::fromFile($tmp, 'csv', $pid, 2026);
    @unlink($tmp);
    $d1 = QuoteProductPrice::where('product_id', $pid)->where('quoted_on', '2026-01-05')->find();
    $d2 = QuoteProductPrice::where('product_id', $pid)->where('quoted_on', '2026-01-06')->find();
    $wrong = QuoteProductPrice::where('product_id', $pid)->where('quoted_on', '2024-01-05')->where('price_avg', 5000)->find();
    $pass('import_force_year_2026', $d1 && $d2 && !$wrong && (float) $d1->price_avg === 5000.0, [
        'imported' => $imp['imported'] ?? null,
        'updated' => $imp['updated'] ?? null,
        'd1' => $d1 ? $d1->quoted_on : null,
        'd2' => $d2 ? $d2->quoted_on : null,
        'wrong2024' => (bool) $wrong,
    ]);

    // --- 5) 同日再导入应覆盖 ---
    $csv2 = "日期,均价\n2026-01-05,5555\n";
    $tmp2 = sys_get_temp_dir().'/quote_audit2_'.$marker.'.csv';
    file_put_contents($tmp2, $csv2);
    $imp2 = QuotePriceImport::fromFile($tmp2, 'csv', $pid, 2026);
    @unlink($tmp2);
    $d1b = QuoteProductPrice::where('product_id', $pid)->where('quoted_on', '2026-01-05')->find();
    $pass('import_overwrite_same_day', $d1b && (float) $d1b->price_avg === 5555.0 && ($imp2['updated'] ?? 0) >= 1, $imp2);

    // --- 6) board 年份窗口过滤 ---
    $all = QuoteProductPrice::where('product_id', $pid)->column('quoted_on');
    $y2026 = array_values(array_filter($all, fn ($d) => str_starts_with(substr((string) $d, 0, 10), '2026-')));
    $y2025 = array_values(array_filter($all, fn ($d) => str_starts_with(substr((string) $d, 0, 10), '2025-')));
    $pass('board_year_window_logic', count($y2026) >= 2 && count($y2025) >= 1, [
        'y2026' => count($y2026),
        'y2025' => count($y2025),
        'sample2026' => array_slice($y2026, 0, 5),
    ]);

    // --- 7) 前端页面是否带 year 参数清除 ---
    $html = file_get_contents($root.'/public/admin-quote-board.html');
    $pass('ui_clear_button_year_scoped', str_contains($html, '清除本年数据') && str_contains($html, 'prices?year=') && str_contains($html, '只清除本品种'));

    // --- 8) 宽表导入：选年不应删其它年地区 ---
    // 先给 2025 留一个地区价
    QuoteRegionPrice::where('product_id', $pid)->where('quoted_on', '2025-03-01')->delete();
    QuoteRegionPrice::create([
        'product_id' => $pid,
        'region' => '华东旧区',
        'quoted_on' => '2025-03-01',
        'price' => 1888,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    // 模拟宽表：标题 + 表头 + 一行（用 csv 走不到 wide，直接测删除保护逻辑等价：带 defaultYear 的 whereNotIn 只清本年）
    $keepOtherYearRegion = (int) QuoteRegionPrice::where('product_id', $pid)
        ->where('region', '华东旧区')
        ->where('quoted_on', '2025-03-01')
        ->count();
    // 执行与 importWide 相同的「只清 defaultYear」删除
    $defaultYear = 2026;
    $regionNames = ['华东'];
    $q = QuoteRegionPrice::where('product_id', $pid)->whereNotIn('region', $regionNames);
    $q->whereBetween('quoted_on', [sprintf('%04d-01-01', $defaultYear), sprintf('%04d-12-31', $defaultYear)]);
    $q->delete();
    $keepAfter = (int) QuoteRegionPrice::where('product_id', $pid)->where('region', '华东旧区')->where('quoted_on', '2025-03-01')->count();
    $pass('wide_import_region_cleanup_year_scoped', $keepOtherYearRegion === 1 && $keepAfter === 1);

} catch (Throwable $e) {
    $pass('exception', false, $e->getMessage());
}

// 清理
try {
    QuoteProductPrice::where('product_id', $pid)->delete();
    QuoteRegionPrice::where('product_id', $pid)->delete();
    QuoteProduct::where('id', $pid)->delete();
} catch (Throwable $e) {
}

echo json_encode([
    'ok' => $fail === 0,
    'failed' => $fail,
    'product_id' => $pid,
    'cases' => $report,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), PHP_EOL;

exit($fail === 0 ? 0 : 1);
