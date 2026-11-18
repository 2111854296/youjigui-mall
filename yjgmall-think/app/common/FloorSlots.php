<?php

namespace app\common;

/** 首页材料楼层、产品分类推荐供应的广告位编码。 */
class FloorSlots
{
    /** @var array<string, array{0:string,1:string,2:string}> name => [floorBase, label, eliteBase] */
    public const MAP = [
        '有机硅单体/中间体' => ['floor_mono', '有机硅单体', 'elite_mono'],
        '硅油' => ['floor_oil', '硅油', 'elite_oil'],
        '硅橡胶' => ['floor_rubber', '硅橡胶', 'elite_rubber'],
        '有机硅助剂/填料' => ['floor_addon', '助剂填料', 'elite_addon'],
        '机械设备' => ['floor_equip', '机械设备', 'elite_equip'],
        '硅烷' => ['floor_silane', '硅烷', 'elite_silane'],
        '胶粘剂' => ['floor_glue', '胶粘剂', 'elite_glue'],
        '硅树脂' => ['floor_resin', '硅树脂', 'elite_resin'],
    ];

    public static function childCode(string $base, int $childId, int $slot): string
    {
        return $base.'_c'.$childId.'_'.$slot;
    }

    public static function eliteCode(string $base, int $slot): string
    {
        return $base.'_'.$slot;
    }

    /** @return array{child:int,slot:int} */
    public static function parseFloor(string $position): array
    {
        if (preg_match('/_c(\d+)_(\d)$/', $position, $m)) {
            return ['child' => (int) $m[1], 'slot' => (int) $m[2]];
        }
        if (preg_match('/_(\d)$/', $position, $m)) {
            return ['child' => 0, 'slot' => (int) $m[1]];
        }

        return ['child' => 0, 'slot' => 0];
    }
}
