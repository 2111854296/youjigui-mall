<?php
namespace app\controller\api\v1;

use app\BaseController;
use app\common\VipEntitlement;
use app\model\QuoteProduct;
use app\model\QuoteProductPrice;
use app\model\QuoteRegionPrice;
use think\Response;

class Quote extends BaseController
{
    /** 列表每页上限 / 默认 */
    private const LIST_PER_DEFAULT = 20;
    private const LIST_PER_MAX = 30;
    /** 最大页深，防止脚本翻页扫光 */
    private const LIST_PAGE_MAX = 40;
    public function index(): Response
    {
        // 禁止一次拉全品种全历史 / 批量参数
        if ($this->hasBatchDumpParams()) {
            return json_error('不支持批量拉取行情数据', 422);
        }

        $user = $this->request->user ?? null;
        $page = max(1, (int) $this->request->get('page', 1));
        if ($page > self::LIST_PAGE_MAX) {
            return json_error('页码超出限制', 422);
        }
        $per = (int) $this->request->get('per_page', self::LIST_PER_DEFAULT);
        $per = min(self::LIST_PER_MAX, max(1, $per));

        $needLogin = !$user;
        $maxDays = $needLogin ? 0 : VipEntitlement::quoteHistoryDays($user);
        $sparkDays = $needLogin ? 0 : $maxDays;

        $paginator = QuoteProduct::where('is_active', 1)
            ->order('sort', 'asc')
            ->order('quoted_on', 'desc')
            ->paginate(['list_rows' => $per, 'page' => $page]);

        return json(paginate_laravel($paginator, function ($p) use ($sparkDays, $maxDays, $needLogin) {
            $series = $this->seriesFor($p, $sparkDays);
            $diff = $this->diffFromSeries($series);
            // 现价：登录即可看；会员天数只限制走势深度，不因「几天没更新」把现价抹掉。
            $visible = !$needLogin;
            return [
                'id' => (int) $p->id,
                'name' => $p->name,
                'unit' => $p->unit,
                'price_min' => $visible ? ($p->price_min !== null ? (string) $p->price_min : null) : null,
                'price_max' => $visible ? ($p->price_max !== null ? (string) $p->price_max : null) : null,
                'price_avg' => $visible ? ($p->price_avg !== null ? (string) $p->price_avg : null) : null,
                // 列表始终返回品种均价，展示标签与价格口径一致
                'market' => '均价',
                'quoted_on' => $p->quoted_on,
                'day_diff' => $visible ? $diff['day_diff'] : null,
                'day_pct' => $visible ? $diff['day_pct'] : null,
                'month_diff' => $visible ? $diff['month_diff'] : null,
                'month_pct' => $visible ? $diff['month_pct'] : null,
                'series' => $series,
                'history_days_limit' => $maxDays,
            ];
        }));
    }

    public function show($id): Response
    {
        if ($this->hasBatchDumpParams()) {
            return json_error('不支持批量拉取行情数据', 422);
        }

        $p = QuoteProduct::where('is_active', 1)->find($id);
        if (!$p) {
            return json_error('Not found', 404);
        }

        $user = $this->request->user ?? null;
        $needLogin = !$user;
        $maxDays = $needLogin ? 0 : VipEntitlement::quoteHistoryDays($user);
        $isVip = $user ? VipEntitlement::isPaidActive($user) : false;
        $reqDays = (int) $this->request->get('days', 0);

        // 长周期：超过免费档（7 天）必须登录且具备付费 VIP 权益
        if ($reqDays > 7) {
            if ($needLogin) {
                return json_error('请登录后查看更长周期行情', 401);
            }
            if (!$isVip && $reqDays > $maxDays) {
                return json_error('当前会员档可看的行情天数不够，请升级后再查看', 403);
            }
        }

        $sort = strtolower(trim((string) $this->request->get('sort', 'asc')));
        if ($sort !== 'desc') {
            $sort = 'asc';
        }
        if ($needLogin) {
            $days = 0;
            $series = [];
        } else {
            if ($reqDays > 0 && $reqDays > $maxDays) {
                // 超出档位：拒绝而不是默默截断，避免爬虫试探
                return json_error('超出当前会员可查看的行情天数', 403, [
                    'history_days_limit' => [$maxDays],
                ]);
            }
            $days = $reqDays > 0 ? $reqDays : min(7, $maxDays);
            $days = max(1, min($days, $maxDays));
            $series = $this->seriesFor($p, $days, $sort);
        }
        $regionName = trim((string) $this->request->get('region', ''));
        $regions = $this->regionBriefs((int) $p->id, $maxDays);
        $activeRegion = '';
        foreach ($regions as $brief) {
            if ($brief['name'] === $regionName) {
                $activeRegion = $regionName;
                break;
            }
        }
        if ($activeRegion !== '' && !$needLogin) {
            $series = $this->regionSeries($p, $activeRegion, $days, $sort);
        }
        $diff = $this->diffFromSeries($series);
        $regionPrice = null;
        $regionDate = null;
        if ($activeRegion !== '') {
            foreach ($regions as $brief) {
                if ($brief['name'] === $activeRegion) {
                    $regionPrice = $brief['price'];
                    $regionDate = $brief['quoted_on'];
                    break;
                }
            }
        }

        $others = QuoteProduct::where('is_active', 1)
            ->where('id', '<>', (int) $p->id)
            ->order('sort', 'asc')
            ->limit(8)
            ->select()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => $row->name,
                'unit' => $row->unit,
                'price_avg' => (!$needLogin && $row->price_avg !== null) ? (string) $row->price_avg : null,
                'quoted_on' => $row->quoted_on,
            ])
            ->all();

        $grade = VipEntitlement::resolveGrade($user);
        $quotedOn = substr((string) ($regionDate ?: $p->quoted_on), 0, 10);
        $showPrice = !$needLogin;

        return json_data([
            'id' => (int) $p->id,
            'name' => $p->name,
            'unit' => $p->unit,
            'price_min' => $showPrice && $p->price_min !== null ? (string) $p->price_min : null,
            'price_max' => $showPrice && $p->price_max !== null ? (string) $p->price_max : null,
            'price_avg' => $showPrice ? ($regionPrice !== null ? (string) $regionPrice : ($p->price_avg !== null ? (string) $p->price_avg : null)) : null,
            'product_price_avg' => $showPrice && $p->price_avg !== null ? (string) $p->price_avg : null,
            // 与当前展示价口径一致：选了地区就显示地区名，否则是均价（勿用商品上写死的市场字段）
            'market' => $activeRegion !== '' ? $activeRegion : '均价',
            'quoted_on' => $regionDate ?: $p->quoted_on,
            'regions' => $regions,
            'active_region' => $activeRegion,
            'day_diff' => $diff['day_diff'],
            'day_pct' => $diff['day_pct'],
            'month_diff' => $diff['month_diff'],
            'month_pct' => $diff['month_pct'],
            'series' => $series,
            'series_days' => $days,
            'series_sort' => $sort,
            'history_days_limit' => $maxDays,
            'need_login' => $needLogin,
            'is_vip' => $isVip,
            'vip_grade_code' => (string) ($grade['code'] ?? 'free'),
            'others' => array_values($others),
            'ad_slots' => $this->quoteAdSlots($p),
        ]);
    }

    /** @return list<array{slot:int,code:string,label:string,ad:?array}> */
    private function quoteAdSlots($p): array
    {
        $codes = [];
        for ($slot = 1; $slot <= 3; $slot++) {
            $codes[] = \app\common\QuoteAdSlots::code((int) $p->id, $slot);
        }
        $now = date('Y-m-d H:i:s');
        $byPos = [];
        $ads = \app\model\Ad::where('is_active', 1)->whereIn('position', $codes)->order('sort', 'asc')->order('id', 'asc')->select();
        foreach ($ads as $ad) {
            if ($ad->starts_at && $ad->starts_at > $now) {
                continue;
            }
            if ($ad->ends_at && $ad->ends_at < $now) {
                continue;
            }
            $pos = (string) $ad->position;
            if (isset($byPos[$pos])) {
                continue;
            }
            $slide = \app\model\Ad::toSlideArray($ad);
            if (!\app\model\Ad::usableImage($slide['image'] ?? '') && trim((string) ($slide['title'] ?? '')) === '') {
                continue;
            }
            $byPos[$pos] = $slide;
        }
        $label = \app\common\QuoteAdSlots::shortName((string) $p->name);
        $out = [];
        foreach ($codes as $i => $code) {
            $out[] = [
                'slot' => $i + 1,
                'code' => $code,
                'label' => $label,
                'ad' => $byPos[$code] ?? null,
            ];
        }

        return $out;
    }

    /** 拦截批量/全量导出类参数 */
    private function hasBatchDumpParams(): bool
    {
        $get = $this->request->get();
        if (!is_array($get)) {
            return false;
        }
        if (!empty($get['ids']) || !empty($get['id_list']) || !empty($get['product_ids'])) {
            return true;
        }
        $all = $get['all'] ?? $get['export'] ?? $get['dump'] ?? null;
        if ($all === '1' || $all === 1 || $all === true || $all === 'true' || $all === 'yes') {
            return true;
        }
        // 故意把 per_page 拉爆
        if (isset($get['per_page']) && (int) $get['per_page'] > self::LIST_PER_MAX) {
            return true;
        }
        return false;
    }

    /**
     * 只返回会员档日历窗口内的报价。窗口锚定「最新有价日」，避免当天未报价时少一天。
     * @param 'asc'|'desc' $sort 历史表排序：asc 旧→新，desc 新→旧（由 SQL order 返回，非前端翻转）
     */
    private function seriesFor(QuoteProduct $p, int $days = 30, string $sort = 'asc'): array
    {
        if ($days < 1) {
            return [];
        }
        $dir = $sort === 'desc' ? 'desc' : 'asc';
        $end = $this->latestQuotedOn((int) $p->id) ?: date('Y-m-d');
        $start = $this->windowStartBefore($days, $end);
        $rows = QuoteProductPrice::where('product_id', (int) $p->id)
            ->whereNotNull('price_avg')
            ->where('quoted_on', '>=', $start)
            ->where('quoted_on', '<=', $end)
            ->order('quoted_on', $dir)
            ->select();

        $series = [];
        foreach ($rows as $row) {
            $avg = (float) $row->price_avg;
            $date = substr((string) $row->quoted_on, 0, 10);
            $series[] = [
                'date' => $date,
                'label' => substr($date, 5),
                'price_avg' => round($avg, 2),
                'price_min' => $row->price_min !== null ? round((float) $row->price_min, 2) : null,
                'price_max' => $row->price_max !== null ? round((float) $row->price_max, 2) : null,
            ];
        }

        return $series;
    }

    private function latestQuotedOn(int $productId): string
    {
        $v = QuoteProductPrice::where('product_id', $productId)
            ->whereNotNull('price_avg')
            ->order('quoted_on', 'desc')
            ->value('quoted_on');

        return $v ? substr((string) $v, 0, 10) : '';
    }

    /** 含结束日，共 $days 个自然日。 */
    private function windowStartBefore(int $days, string $end): string
    {
        $days = max(1, $days);
        $end = preg_match('/^\d{4}-\d{2}-\d{2}$/', $end) ? $end : date('Y-m-d');

        return date('Y-m-d', strtotime($end . ' -' . ($days - 1) . ' days'));
    }

    /** 会员可见窗口：含当天，共 $days 个自然日（按今天计）。 */
    private function windowStart(int $days): string
    {
        return $this->windowStartBefore($days, date('Y-m-d'));
    }

    private function dateWithin(?string $date, int $days): bool
    {
        if ($days < 1) {
            return false;
        }
        $day = substr((string) $date, 0, 10);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $day)) {
            return false;
        }

        return $day >= $this->windowStart($days);
    }

    /** @param list<array{price_avg:float}> $series */
    private function diffFromSeries(array $series): array
    {
        $n = count($series);
        $latest = $n ? (float) $series[$n - 1]['price_avg'] : 0.0;
        $prev = $n >= 2 ? (float) $series[$n - 2]['price_avg'] : $latest;
        $monthAgo = $n ? (float) $series[0]['price_avg'] : $latest;
        $dayDiff = round($latest - $prev, 2);
        $monthDiff = round($latest - $monthAgo, 2);
        $dayPct = $prev != 0.0 ? round($dayDiff / $prev * 100, 2) : 0.0;
        $monthPct = $monthAgo != 0.0 ? round($monthDiff / $monthAgo * 100, 2) : 0.0;
        return [
            'day_diff' => $dayDiff,
            'day_pct' => $dayPct,
            'month_diff' => $monthDiff,
            'month_pct' => $monthPct,
        ];
    }

    /** @return list<array{name:string,price:float,prev:float,quoted_on:string}> */
    private function regionBriefs(int $productId, int $days): array
    {
        if ($days < 1 || ! $this->regionTableReady()) {
            return [];
        }
        $rows = QuoteRegionPrice::where('product_id', $productId)->order('quoted_on', 'asc')->select();
        $by = [];
        foreach ($rows as $row) {
            $by[(string) $row->region][] = $row;
        }
        $out = [];
        foreach ($by as $name => $list) {
            $last = $list[count($list) - 1];
            $prev = count($list) >= 2 ? $list[count($list) - 2] : $last;
            $on = substr((string) $last->quoted_on, 0, 10);
            $out[] = [
                'name' => $name,
                'price' => round((float) $last->price, 2),
                'prev' => round((float) $prev->price, 2),
                'quoted_on' => $on,
            ];
        }

        return $out;
    }

    /** @return list<array{date:string,label:string,price_avg:float,price_min:?float,price_max:?float}> */
    private function regionSeries(QuoteProduct $p, string $region, int $days, string $sort = 'asc'): array
    {
        if ($days < 1) {
            return [];
        }
        $dir = $sort === 'desc' ? 'desc' : 'asc';
        $endRow = QuoteRegionPrice::where('product_id', (int) $p->id)
            ->where('region', $region)
            ->order('quoted_on', 'desc')
            ->value('quoted_on');
        $end = $endRow ? substr((string) $endRow, 0, 10) : date('Y-m-d');
        $start = $this->windowStartBefore($days, $end);
        $rows = QuoteRegionPrice::where('product_id', (int) $p->id)
            ->where('region', $region)
            ->where('quoted_on', '>=', $start)
            ->where('quoted_on', '<=', $end)
            ->order('quoted_on', $dir)
            ->select();
        $series = [];
        foreach ($rows as $row) {
            $date = substr((string) $row->quoted_on, 0, 10);
            $series[] = [
                'date' => $date,
                'label' => substr($date, 5),
                'price_avg' => round((float) $row->price, 2),
                'price_min' => null,
                'price_max' => null,
            ];
        }

        return $series;
    }

    private function regionTableReady(): bool
    {
        try {
            QuoteRegionPrice::limit(1)->select();

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
