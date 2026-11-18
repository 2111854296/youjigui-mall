<?php

namespace app\common;

use app\controller\api\v1\Site;
use app\model\VerificationCode;

/**
 * 手机验证码。sms_mode=fixed 时会员输入站点设置里的固定数字即通过；
 * sms_mode=gateway 时按站点设置的阿里云密钥发送随机验证码。
 */
class SmsCode
{
    public const CHANNEL = 'mobile_verify';

    public static function gatewayOn(): bool
    {
        return (string) (Site::allSettings()['sms_mode'] ?? 'fixed') === 'gateway';
    }

    /** @return array{ok:bool,message:string} */
    public static function send(string $mobile, int $userId): array
    {
        if (!preg_match('/^1\d{10}$/', $mobile)) {
            return ['ok' => false, 'message' => '请填写11位手机号'];
        }
        if ($wait = self::tooSoon(self::CHANNEL, $mobile)) {
            return $wait;
        }

        if (self::gatewayOn()) {
            $code = (string) random_int(100000, 999999);
            self::store(self::CHANNEL, $mobile, $userId, $code, 600);
            $sent = SmsGateway::send($mobile, $code);
            if (empty($sent['ok'])) {
                self::discard(self::CHANNEL, $mobile);

                return $sent;
            }

            return ['ok' => true, 'message' => '验证码已发送'];
        }

        $settings = Site::allSettings();
        $code = preg_replace('/\D/', '', (string) ($settings['sms_fixed_code'] ?? ''));
        if (strlen($code) < 4) {
            $code = '888888';
        }
        self::store(self::CHANNEL, $mobile, $userId, $code, 600);

        return ['ok' => true, 'message' => '请输入短信验证码'];
    }

    /** 找回密码：正式短信发到会员手机，验证码仍按提交的账号核销。 */
    public static function sendReset(string $account, string $mobile, int $userId, string $channel = 'reset'): array
    {
        if (!in_array($channel, ['reset', 'reset_pay'], true)) {
            $channel = 'reset';
        }
        if ($wait = self::tooSoon($channel, $account)) {
            return $wait;
        }
        $code = (string) random_int(100000, 999999);
        self::store($channel, $account, $userId, $code, 900);
        $sent = SmsGateway::send($mobile, $code);
        if (empty($sent['ok'])) {
            self::discard($channel, $account);

            return $sent;
        }

        return ['ok' => true, 'message' => '验证码已发送至手机'];
    }

    /** @return array{ok:bool,message:string}|null */
    private static function tooSoon(string $channel, string $account): ?array
    {
        $recent = VerificationCode::where('channel', $channel)
            ->where('account', $account)
            ->where('created_at', '>', date('Y-m-d H:i:s', time() - 60))
            ->find();
        if ($recent) {
            return ['ok' => false, 'message' => '请60秒后再获取验证码'];
        }

        return null;
    }

    private static function store(string $channel, string $account, int $userId, string $code, int $ttl): void
    {
        VerificationCode::where('channel', $channel)->where('account', $account)->whereNull('used_at')->delete();
        VerificationCode::create([
            'channel' => $channel,
            'account' => $account,
            'user_id' => $userId,
            'code_hash' => password_hash($code, PASSWORD_BCRYPT),
            'expires_at' => date('Y-m-d H:i:s', time() + $ttl),
        ]);
    }

    private static function discard(string $channel, string $account): void
    {
        VerificationCode::where('channel', $channel)->where('account', $account)->whereNull('used_at')->delete();
    }

    public static function check(string $mobile, string $input): bool
    {
        $input = trim($input);
        if ($input === '' || $mobile === '') {
            return false;
        }
        $row = VerificationCode::where('channel', self::CHANNEL)
            ->where('account', $mobile)
            ->whereNull('used_at')
            ->order('id', 'desc')
            ->find();
        if (!$row || strtotime((string) $row->expires_at) < time()) {
            return false;
        }
        if (!password_verify($input, (string) $row->code_hash)) {
            return false;
        }
        $row->used_at = date('Y-m-d H:i:s');
        $row->save();

        return true;
    }
}
