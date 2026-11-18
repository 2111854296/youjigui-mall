<?php

namespace app\common;

/** 会员填写的姓名、证件、手机和银行账号。 */
class InputCheck
{
    public static function realName(string $name): bool
    {
        $name = trim($name);

        return (bool) preg_match('/^[\x{4e00}-\x{9fa5}]{2,20}(·[\x{4e00}-\x{9fa5}]{1,20})?$/u', $name);
    }

    public static function idCard(string $raw): bool
    {
        $s = strtoupper(trim($raw));
        if (!preg_match('/^[1-9]\d{16}[\dX]$/', $s)) {
            return false;
        }
        $weight = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
        $check = '10X98765432';
        $sum = 0;
        for ($i = 0; $i < 17; $i++) {
            $sum += (int) $s[$i] * $weight[$i];
        }

        return $check[$sum % 11] === $s[17];
    }

    public static function mobile(string $mobile): bool
    {
        return (bool) preg_match('/^1\d{10}$/', trim($mobile));
    }

    public static function bankAccount(string $account): bool
    {
        return (bool) preg_match('/^\d{8,30}$/', trim($account));
    }

    public static function email(string $email): bool
    {
        return filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false;
    }
}
