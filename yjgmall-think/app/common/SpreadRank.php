<?php

namespace app\common;

use app\model\Spread;

/** 已审核且在有效期内的关键词推广：搜索词命中后，按出价把该会员的信息排到前面。 */
class SpreadRank
{
    public static function apply($query, string $module, string $q, string $userColumn = 'user_id'): void
    {
        $q = trim($q);
        if ($q === '') {
            return;
        }
        $now = date('Y-m-d H:i:s');
        $rows = Spread::where('module', $module)
            ->where('status', 'active')
            ->where(function ($w) use ($now) {
                $w->whereNull('starts_at')->whereOr('starts_at', '<=', $now);
            })
            ->where(function ($w) use ($now) {
                $w->whereNull('ends_at')->whereOr('ends_at', '>=', $now);
            })
            ->select();

        $best = [];
        foreach ($rows as $row) {
            $kw = trim((string) ($row->keyword ?: $row->title));
            if ($kw === '' || !self::hit($q, $kw)) {
                continue;
            }
            $uid = (int) $row->user_id;
            $bid = (float) $row->bid;
            if ($uid < 1) {
                continue;
            }
            if (!isset($best[$uid]) || $bid > $best[$uid]) {
                $best[$uid] = $bid;
            }
        }
        if (!$best) {
            return;
        }
        arsort($best);
        $case = 'CASE '.$userColumn;
        foreach ($best as $uid => $bid) {
            $case .= ' WHEN '.(int) $uid.' THEN '.(int) round($bid * 100);
        }
        $case .= ' ELSE 0 END DESC';
        $query->orderRaw($case);
    }

    private static function hit(string $query, string $keyword): bool
    {
        $q = mb_strtolower($query);
        $k = mb_strtolower($keyword);

        return mb_stripos($q, $k) !== false || mb_stripos($k, $q) !== false;
    }
}
