<?php

namespace app\common;

use app\model\SiteSetting;
use think\facade\Cache;

/** 后台删除用二级密码：站点级设置，校验通过后 24 小时内免再输。 */
class AdminSecondPassword
{
    public const SETTING_KEY = 'admin_second_password_hash';
    public const CACHE_PREFIX = 'admin_second_ok_';
    public const TTL = 86400;

    public static function hash(): string
    {
        $row = SiteSetting::where('key', self::SETTING_KEY)->find();

        return $row ? trim((string) $row->value) : '';
    }

    public static function isSet(): bool
    {
        return self::hash() !== '';
    }

    public static function set(string $plain): void
    {
        $plain = trim($plain);
        if (strlen($plain) < 6) {
            throw new \InvalidArgumentException('二级密码至少 6 位');
        }
        $hash = password_hash($plain, PASSWORD_BCRYPT);
        $row = SiteSetting::where('key', self::SETTING_KEY)->find();
        if ($row) {
            $row->value = $hash;
            $row->save();
        } else {
            SiteSetting::create(['key' => self::SETTING_KEY, 'value' => $hash]);
        }
    }

    public static function check(string $plain): bool
    {
        $hash = self::hash();
        if ($hash === '') {
            return false;
        }

        return password_verify(trim($plain), $hash);
    }

    public static function cacheKey(int $adminId): string
    {
        return self::CACHE_PREFIX.$adminId;
    }

    public static function isVerified(int $adminId): bool
    {
        if ($adminId < 1) {
            return false;
        }

        return (bool) Cache::get(self::cacheKey($adminId));
    }

    public static function markVerified(int $adminId): void
    {
        if ($adminId < 1) {
            return;
        }
        Cache::set(self::cacheKey($adminId), time() + self::TTL, self::TTL);
    }

    public static function clearVerified(int $adminId): void
    {
        if ($adminId < 1) {
            return;
        }
        Cache::delete(self::cacheKey($adminId));
    }

    public static function verifiedUntil(int $adminId): ?string
    {
        $until = Cache::get(self::cacheKey($adminId));
        if (!$until) {
            return null;
        }
        $ts = is_numeric($until) ? (int) $until : time() + self::TTL;

        return date('Y-m-d H:i:s', $ts);
    }

    /**
     * 仅用于删除类操作（列表删除、行情日价删除、回收站彻底删除）。
     * 上架 / 下架 / 还原不调用此方法。
     */
    public static function assertCanDelete(mixed $admin): void
    {
        if (!$admin || empty($admin->id)) {
            throw new \RuntimeException('请先登录', 401);
        }
        if (!self::isSet()) {
            throw new \RuntimeException('请先在「站点设置」中设置二级密码后再删除', 423);
        }
        if (!self::isVerified((int) $admin->id)) {
            throw new \RuntimeException('需要验证二级密码', 428);
        }
    }
}
