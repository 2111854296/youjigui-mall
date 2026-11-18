<?php

namespace app\common;

/** 商品数量区间价，最多三档。最后一档截止数量为空表示该数量及以上。 */
class PriceTiers
{
    /** @return array{ok:bool,message:string,tiers:list<array{min_qty:int,max_qty:?int,price:string}>,price:?float} */
    public static function normalize($raw): array
    {
        if ($raw === null || $raw === '' || $raw === []) {
            return ['ok' => true, 'message' => '', 'tiers' => [], 'price' => null];
        }
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($raw)) {
            return ['ok' => false, 'message' => '区间价格式不正确', 'tiers' => [], 'price' => null];
        }

        $rows = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $minRaw = $row['min_qty'] ?? $row['min'] ?? '';
            $maxRaw = $row['max_qty'] ?? $row['max'] ?? '';
            $priceRaw = $row['price'] ?? '';
            if ($minRaw === '' && $maxRaw === '' && ($priceRaw === '' || $priceRaw === null)) {
                continue;
            }
            $rows[] = [
                'min_qty' => (int) $minRaw,
                'max_qty' => ($maxRaw === '' || $maxRaw === null) ? null : (int) $maxRaw,
                'price' => round((float) $priceRaw, 2),
            ];
        }
        if (!$rows) {
            return ['ok' => true, 'message' => '', 'tiers' => [], 'price' => null];
        }
        if (count($rows) > 3) {
            return ['ok' => false, 'message' => '价格档位最多三个', 'tiers' => [], 'price' => null];
        }
        usort($rows, static fn ($a, $b) => $a['min_qty'] <=> $b['min_qty']);
        $prevMax = null;
        foreach ($rows as $i => $row) {
            if ($row['min_qty'] < 1) {
                return ['ok' => false, 'message' => '起始数量至少为 1', 'tiers' => [], 'price' => null];
            }
            if ($row['price'] <= 0) {
                return ['ok' => false, 'message' => '区间价必须大于 0', 'tiers' => [], 'price' => null];
            }
            $isLast = $i === count($rows) - 1;
            if (!$isLast && $row['max_qty'] === null) {
                return ['ok' => false, 'message' => '只有最后一档可以不填截止数量', 'tiers' => [], 'price' => null];
            }
            if ($row['max_qty'] !== null && $row['max_qty'] < $row['min_qty']) {
                return ['ok' => false, 'message' => '截止数量不能小于起始数量', 'tiers' => [], 'price' => null];
            }
            if ($prevMax !== null && $row['min_qty'] !== $prevMax + 1) {
                return ['ok' => false, 'message' => '数量区间要首尾相接，不能重叠或断开', 'tiers' => [], 'price' => null];
            }
            $prevMax = $row['max_qty'];
        }

        $tiers = [];
        foreach ($rows as $row) {
            $tiers[] = [
                'min_qty' => $row['min_qty'],
                'max_qty' => $row['max_qty'],
                'price' => number_format($row['price'], 2, '.', ''),
            ];
        }

        return ['ok' => true, 'message' => '', 'tiers' => $tiers, 'price' => (float) $tiers[0]['price']];
    }

    /** @return list<array{min_qty:int,max_qty:?int,price:string}> */
    public static function decode($raw): array
    {
        $parsed = self::normalize($raw);

        return $parsed['ok'] ? $parsed['tiers'] : [];
    }

    public static function priceFor($product, int $qty): float
    {
        $qty = max(1, $qty);
        $tiers = self::decode($product->price_tiers ?? null);
        if (!$tiers) {
            return round((float) ($product->price ?? 0), 2);
        }
        foreach ($tiers as $tier) {
            $min = (int) $tier['min_qty'];
            $max = $tier['max_qty'];
            if ($qty >= $min && ($max === null || $qty <= (int) $max)) {
                return round((float) $tier['price'], 2);
            }
        }
        $last = $tiers[count($tiers) - 1];
        if ($qty >= (int) $last['min_qty']) {
            return round((float) $last['price'], 2);
        }

        return round((float) $tiers[0]['price'], 2);
    }
}
