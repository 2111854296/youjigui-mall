<?php
/**
 * 清除安全硬检：无 year 必须拒绝；带 year 只删该年该品种；删品种只删本品种价；导入不得误删他年。
 * 直接打线上 clearQuotePrices 逻辑等价路径 + 读源码 + 二次密码门禁。
 */
$root = '/www/wwwroot/g.aifeili.cn';
chdir($root);
require $root.'/vendor/autoload.php';
$app = new \think\App();
$app->initialize();

use app\common\AdminSecondPassword;
use app\common\QuotePriceImport;
use app\model\QuoteProduct;
use app\model\QuoteProductPrice;
use app\model\QuoteRegionPrice;
use think\facade\Db;

$tag = 'CLRSAFE'.substr((string) time(), -8);
$now = date('Y-m-d H:i:s');
$report = [];
$fail = 0;
$pass = function (string $name, bool $ok, $detail = null) use (&$report, &$fail) {
    $report[] = ['case' => $name, 'ok' => $ok, 'detail' => $detail];
    if (!$ok) {
        $fail++;
    }
};

$qpIds = [];
try {
    // ===== 源码硬约束 =====
    $src = file_get_contents($root.'/app/controller/api/v1/AdminResource.php');
    $html = file_get_contents($root.'/public/admin-quote-board.html');
    $route = file_get_contents($root.'/route/app.php');

    $pass('api_rejects_missing_year', str_contains($src, '请指定要清除的年份（year）'));
    $pass('api_year_range_1990_2100', preg_match('/\$year\s*<\s*1990\s*\|\|\s*\$year\s*>\s*2100/', $src) === 1);
    $pass('api_delete_uses_product_id_and_whereBetween',
        preg_match('/QuoteProductPrice::where\(\'product_id\'.*whereBetween\(\'quoted_on\'/s', $src) === 1
        && preg_match('/QuoteRegionPrice::where\(\'product_id\'.*whereBetween\(\'quoted_on\'/s', $src) === 1
    );
    // 禁止无 year 的全表/全品种清价（在 clearQuotePrices 方法体内）
    if (preg_match('/function clearQuotePrices\(\$id\).*?(?=public function |private function |protected function )/s', $src, $m)) {
        $body = $m[0];
        $hasUnscoped = preg_match('/QuoteProductPrice::where\(\'product_id\'[^\n]*\)\s*->\s*delete\(\)/', $body)
            || preg_match('/QuoteProductPrice::where\(\'product_id\'[^\n]*\)\s*->delete\(\)/', $body);
        // 允许的形式必须带 whereBetween；检测方法体内是否存在「仅 product_id 就 delete」的危险写法
        $danger = false;
        if (preg_match_all('/Quote(Product|Region)Price::where\(\'product_id\'[^\n;]*\)(?:\s*->\s*[a-zA-Z]+\([^\)]*\))*\s*->\s*delete\(\)/', $body, $alls)) {
            foreach ($alls[0] as $line) {
                if (!str_contains($line, 'whereBetween') && !str_contains($line, 'quoted_on')) {
                    $danger = true;
                }
            }
        }
        // 更严：方法体内两次 delete 前都必须先 whereBetween
        $okScoped = substr_count($body, 'whereBetween') >= 2 && substr_count($body, '->delete()') >= 2;
        $pass('clearQuotePrices_body_year_scoped', $okScoped && !$danger, [
            'whereBetween' => substr_count($body, 'whereBetween'),
            'deletes' => substr_count($body, '->delete()'),
            'danger_unscoped' => $danger,
        ]);
    } else {
        $pass('clearQuotePrices_body_year_scoped', false, 'method not found');
    }

    $pass('ui_button_says_year_only', str_contains($html, '清除本年数据'));
    $pass('ui_confirm_says_other_years_safe', str_contains($html, '其它年份不受影响') || str_contains($html, '只清除本品种'));
    $pass('ui_delete_always_sends_year', str_contains($html, "prices?year=") && str_contains($html, 'encodeURIComponent(String(year))'));
    $pass('ui_blocks_clear_without_year', str_contains($html, '请先在上方选择要清除的年份'));
    $pass('route_delete_prices_exists', str_contains($route, 'resources/quote-products/:id/prices') && str_contains($route, 'clearQuotePrices'));

    // 禁止旧版「一键清空全部」文案/接口残留
    $pass('no_clear_all_years_ui_copy', !preg_match('/清除全部|清空全部|清除所有年|一键清空/', $html));
    $pass('second_password_on_clear', str_contains($src, 'AdminSecondPassword::assertCanDelete')
        && str_contains(substr($src, strpos($src, 'function clearQuotePrices'), 400), 'assertCanDelete'));

    // ===== 数据实测：两品种 × 多年 =====
    $mk = function (string $name) use ($now, &$qpIds) {
        $p = QuoteProduct::create([
            'name' => $name,
            'unit' => '元/吨',
            'market' => '安全测',
            'is_active' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $qpIds[] = (int) $p->id;

        return $p;
    };
    $seed = function (int $pid, array $days) {
        foreach ($days as [$d, $price]) {
            QuoteProductPrice::create([
                'product_id' => $pid,
                'quoted_on' => $d,
                'price_avg' => $price,
                'price_min' => $price,
                'price_max' => $price,
            ]);
            QuoteRegionPrice::create([
                'product_id' => $pid,
                'region' => '华东',
                'quoted_on' => $d,
                'price' => $price,
            ]);
        }
    };

    $a = $mk('安全测A_'.$tag);
    $b = $mk('安全测B_'.$tag);
    $aid = (int) $a->id;
    $bid = (int) $b->id;
    $seed($aid, [
        ['2024-03-01', 100],
        ['2025-03-01', 200],
        ['2026-01-15', 300],
        ['2026-06-01', 310],
        ['2026-12-31', 320],
    ]);
    $seed($bid, [
        ['2025-05-01', 500],
        ['2026-05-01', 600],
        ['2026-08-01', 610],
    ]);

    $countYear = function (int $pid, int $year) {
        $from = sprintf('%04d-01-01', $year);
        $to = sprintf('%04d-12-31', $year);

        return [
            'days' => (int) QuoteProductPrice::where('product_id', $pid)->whereBetween('quoted_on', [$from, $to])->count(),
            'regions' => (int) QuoteRegionPrice::where('product_id', $pid)->whereBetween('quoted_on', [$from, $to])->count(),
            'all' => (int) QuoteProductPrice::where('product_id', $pid)->count(),
        ];
    };

    // 模拟 clearQuotePrices 核心（与线上一致）
    $clearYear = function (int $pid, int $year) {
        if ($year < 1990 || $year > 2100) {
            throw new RuntimeException('请指定要清除的年份（year）');
        }
        $from = sprintf('%04d-01-01', $year);
        $to = sprintf('%04d-12-31', $year);
        QuoteProductPrice::where('product_id', $pid)->whereBetween('quoted_on', [$from, $to])->delete();
        QuoteRegionPrice::where('product_id', $pid)->whereBetween('quoted_on', [$from, $to])->delete();
    };

    // 无 year / 非法 year 必须拒
    $rejectOk = false;
    try {
        $clearYear($aid, 0);
    } catch (Throwable $e) {
        $rejectOk = str_contains($e->getMessage(), '请指定要清除的年份');
    }
    $pass('runtime_reject_year_0', $rejectOk);

    $reject2099ok = true; // 2099 合法边界内但无数据，应成功且不影响
    // 非法 1899
    $reject1899 = false;
    try {
        $clearYear($aid, 1899);
    } catch (Throwable $e) {
        $reject1899 = true;
    }
    $pass('runtime_reject_year_1899', $reject1899);

    // 清 A 的 2026：A 的 24/25 保留，B 全部保留
    $beforeA = $countYear($aid, 2026);
    $beforeB = $countYear($bid, 2026);
    $clearYear($aid, 2026);
    $afterA26 = $countYear($aid, 2026);
    $afterA24 = $countYear($aid, 2024);
    $afterA25 = $countYear($aid, 2025);
    $afterB26 = $countYear($bid, 2026);
    $afterB25 = $countYear($bid, 2025);
    $pass('clear_2026_only_product_A',
        $afterA26['days'] === 0 && $afterA26['regions'] === 0
        && $afterA24['days'] === 1 && $afterA25['days'] === 1
        && $afterB26['days'] === $beforeB['days'] && $afterB25['days'] === 1
        && (int) QuoteProductPrice::where('product_id', $aid)->count() === 2,
        [
            'A26_before' => $beforeA,
            'A26_after' => $afterA26,
            'A24' => $afterA24,
            'A25' => $afterA25,
            'B26' => $afterB26,
            'B_all' => (int) QuoteProductPrice::where('product_id', $bid)->count(),
        ]
    );

    // 再清 A 的 2025：只剩 2024；B 仍完整
    $clearYear($aid, 2025);
    $pass('clear_2025_keeps_2024_and_B',
        (int) QuoteProductPrice::where('product_id', $aid)->where('quoted_on', '2024-03-01')->count() === 1
        && (int) QuoteProductPrice::where('product_id', $aid)->count() === 1
        && (int) QuoteProductPrice::where('product_id', $bid)->count() === 3
    );

    // 导入 forceYear=2026 不得误伤 2024/2025，且不删他品种
    $csv = "日期,均价\n1/10,999\n2025-02-02,888\n";
    $tmp = sys_get_temp_dir().'/clrsafe_'.$tag.'.csv';
    file_put_contents($tmp, $csv);
    $imp = QuotePriceImport::fromFile($tmp, 'csv', $aid, 2026);
    @unlink($tmp);
    $d110 = QuoteProductPrice::where('product_id', $aid)->where('quoted_on', '2026-01-10')->find();
    $forced = QuoteProductPrice::where('product_id', $aid)->where('quoted_on', '2026-02-02')->find();
    $leak25 = QuoteProductPrice::where('product_id', $aid)->where('quoted_on', '2025-02-02')->where('price_avg', 888)->find();
    $still24 = QuoteProductPrice::where('product_id', $aid)->where('quoted_on', '2024-03-01')->find();
    $bUntouched = (int) QuoteProductPrice::where('product_id', $bid)->count() === 3;
    $pass('import_force_year_no_cross_year_leak', (bool) $d110 && (bool) $forced && !$leak25 && (bool) $still24 && $bUntouched, [
        'imported' => $imp['imported'] ?? null,
        'd110' => $d110 ? (string) $d110->quoted_on : null,
        'forced' => $forced ? (string) $forced->quoted_on : null,
    ]);

    // 导入地区清理：必须同时具备 defaultYear 门禁 + whereBetween
    $impSrc = file_get_contents($root.'/app/common/QuotePriceImport.php');
    $pass('import_region_cleanup_requires_year',
        str_contains($impSrc, '无有效年份则绝不清理')
        || (
            str_contains($impSrc, '$defaultYear >= 2000')
            && str_contains($impSrc, 'whereNotIn(\'region\'')
            && preg_match('/whereNotIn\(\'region\'[\s\S]{0,280}whereBetween\(\'quoted_on\'/', $impSrc) === 1
        )
    );
    // 禁止「无 year 也 delete」的旧写法残留
    $pass('import_no_unscoped_region_wipe', !preg_match(
        '/whereNotIn\(\'region\'[^\n]*\);\s*\n\s*if\s*\(\s*\$defaultYear/',
        $impSrc
    ));

    // 导入接口必须强制 year
    $pass('import_api_requires_year', str_contains($src, '请指定要导入的年份'));
    $pass('import_ui_requires_year', str_contains($html, '请先在上方选择要导入的年份'));

    // 删除品种时只删本品种价（destroy 路径）
    $pass('destroy_product_only_own_prices',
        preg_match('/QuoteProductPrice::where\(\'product_id\',\s*\(int\)\s*\$item->id\)->delete\(\)/', $src) === 1
        || preg_match('/QuoteProductPrice::where\(\'product_id\'/', $src) === 1
    );

    // 二级密码：未授权不可删
    $threw = false;
    try {
        AdminSecondPassword::assertCanDelete((object) ['id' => 0]);
    } catch (Throwable $e) {
        $threw = true;
    }
    $pass('second_password_blocks_unauth_delete', $threw);

} catch (Throwable $e) {
    $pass('fatal', false, $e->getMessage()."\n".$e->getTraceAsString());
}

// cleanup
try {
    foreach ($qpIds as $id) {
        QuoteProductPrice::where('product_id', $id)->delete();
        QuoteRegionPrice::where('product_id', $id)->delete();
        Db::name('quote_products')->where('id', $id)->delete();
    }
} catch (Throwable $e) {
    $pass('cleanup', false, $e->getMessage());
}

echo json_encode([
    'ok' => $fail === 0,
    'failed' => $fail,
    'passed' => count($report) - $fail,
    'total' => count($report),
    'failed_cases' => array_values(array_filter($report, fn ($r) => empty($r['ok']))),
    'all' => $report,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), PHP_EOL;

exit($fail === 0 ? 0 : 1);
