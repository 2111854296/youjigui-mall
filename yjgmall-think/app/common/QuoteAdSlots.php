<?php

namespace app\common;

use think\facade\Db;

/** 每个行情品种右侧 3 个广告位，编码 quote_{品种id}_{1|2|3}。 */
class QuoteAdSlots
{
    public static function code(int $productId, int $slot): string
    {
        return 'quote_'.$productId.'_'.$slot;
    }

    public static function shortName(string $name): string
    {
        if (preg_match('/[（(]([^）)]+)[）)]/u', $name, $m) && trim($m[1]) !== '') {
            return trim($m[1]);
        }
        $name = trim($name);

        return $name !== '' ? $name : '行情';
    }

    public static function sync(object $product): void
    {
        $id = (int) ($product->id ?? 0);
        if ($id <= 0) {
            return;
        }
        $short = self::shortName((string) ($product->name ?? ''));
        $active = (int) ($product->is_active ?? 1) === 1 ? 1 : 0;
        $now = date('Y-m-d H:i:s');
        for ($slot = 1; $slot <= 3; $slot++) {
            self::upsert(
                self::code($id, $slot),
                '行情详情·'.$short.'·第'.$slot.'位',
                800 + $id * 10 + $slot,
                $active,
                $now
            );
        }
    }

    public static function deactivate(int $productId): void
    {
        if ($productId <= 0) {
            return;
        }
        $codes = [];
        for ($slot = 1; $slot <= 3; $slot++) {
            $codes[] = self::code($productId, $slot);
        }
        Db::name('ad_places')->whereIn('code', $codes)->update([
            'is_active' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private static function upsert(string $code, string $name, int $sort, int $active, string $now): void
    {
        $row = Db::name('ad_places')->where('code', $code)->find();
        $data = [
            'name' => $name,
            'size' => '右侧卡片',
            'type' => 'image',
            'sort' => $sort,
            'is_active' => $active,
            'updated_at' => $now,
        ];
        if ($row) {
            Db::name('ad_places')->where('id', $row['id'])->update($data);

            return;
        }
        Db::name('ad_places')->insert($data + [
            'code' => $code,
            'price_note' => '面议',
            'created_at' => $now,
        ]);
    }
}
