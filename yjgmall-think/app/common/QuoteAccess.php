<?php
namespace app\common;

use think\facade\Cache;

/**
 * 行情接口短时访问票据（HMAC），密钥只在服务端。
 */
class QuoteAccess
{
    public const TTL = 90;
    public const HEADER_TICKET = 'X-Quote-Ticket';
    public const HEADER_TS = 'X-Quote-Ts';
    public const HEADER_SIGN = 'X-Quote-Sign';
    public const HEADER_CAPTCHA = 'X-Captcha-Token';

    public static function secret(): string
    {
        $s = (string) env('QUOTE_SIGN_SECRET', '');
        if ($s !== '') {
            return $s;
        }
        // 无 env 时稳定默认（部署可不改 .env；提高脚本门槛即可）
        return 'yjgmall-quote-hmac-v1-g-aifeili';
    }

    /** @return array{ticket:string,ts:int,sign:string,expire_in:int} */
    public static function issue(?string $ip = null): array
    {
        $ticket = bin2hex(random_bytes(16));
        $ts = time();
        $sign = self::sign($ticket, $ts);
        Cache::set('quote_ticket:'.$ticket, [
            'ts' => $ts,
            'ip' => (string) ($ip ?: ''),
        ], self::TTL + 30);

        return [
            'ticket' => $ticket,
            'ts' => $ts,
            'sign' => $sign,
            'expire_in' => self::TTL,
        ];
    }

    public static function sign(string $ticket, int $ts): string
    {
        return hash_hmac('sha256', $ticket.'|'.$ts, self::secret());
    }

    public static function verifyHeaders(?string $ticket, ?string $tsRaw, ?string $sign): bool
    {
        $ticket = trim((string) $ticket);
        $sign = trim((string) $sign);
        $ts = (int) $tsRaw;
        if ($ticket === '' || $sign === '' || $ts <= 0) {
            return false;
        }
        if (abs(time() - $ts) > self::TTL) {
            return false;
        }
        $expect = self::sign($ticket, $ts);
        if (!hash_equals($expect, $sign)) {
            return false;
        }
        $row = Cache::get('quote_ticket:'.$ticket);
        if (!is_array($row)) {
            return false;
        }
        // 票据可在 TTL 内复用（翻页/切品种），不一次性销毁
        return true;
    }
}
