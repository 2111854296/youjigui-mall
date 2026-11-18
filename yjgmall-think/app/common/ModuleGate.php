<?php
namespace app\common;

use think\Response;

/**
 * 暂未开放的频道模块开关。
 * 改 config/app.php → modules 里对应项为 true 即可恢复。
 */
class ModuleGate
{
    /** @var array<string,string> 模块键 => 展示名 */
    public const LABELS = [
        'downloads' => '下载中心',
        'photo' => '图库',
        'video' => '视频',
        'group' => '团购',
        'exhibit' => '展会',
        'brand' => '品牌馆',
        'know' => '知道',
        'club' => '商圈',
    ];

    /** 后台资源 key → 模块键 */
    public const ADMIN_RESOURCE_MAP = [
        'downloads' => 'downloads',
        'photos' => 'photo',
        'videos' => 'video',
        'group-deals' => 'group',
        'exhibits' => 'exhibit',
        'brands' => 'brand',
        'know' => 'know',
        'club' => 'club',
    ];

    public static function enabled(string $key): bool
    {
        $mods = config('app.modules');
        if (!is_array($mods)) {
            // 兼容旧 downloads_enabled
            if ($key === 'downloads') {
                return (bool) config('app.downloads_enabled', false);
            }
            return true;
        }
        if (!array_key_exists($key, $mods)) {
            return true;
        }
        return (bool) $mods[$key];
    }

    public static function refuse(string $key): ?Response
    {
        if (self::enabled($key)) {
            return null;
        }
        $label = self::LABELS[$key] ?? '该功能';
        return json_error($label.'暂未开放', 403);
    }

    /** @return list<string> 应隐藏的后台资源 key */
    public static function hiddenAdminResources(): array
    {
        $out = [];
        foreach (self::ADMIN_RESOURCE_MAP as $resource => $mod) {
            if (!self::enabled($mod)) {
                $out[] = $resource;
            }
        }
        return $out;
    }
}
