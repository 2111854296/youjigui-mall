<?php

namespace app\common;

use app\controller\api\v1\Site;
use app\model\User;
use app\model\VipGrade;
use app\model\VipPayOrder;

/**
 * VIP 对公转账订单：创建、倒计时过期、凭证提交、审核开通
 */
class VipPay
{
    public const STATUS_PENDING_PAY = 'pending_pay';
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    public static function expireMinutes(): int
    {
        $settings = Site::allSettings();
        $m = (int) ($settings['vip_pay_expire_minutes'] ?? 30);

        return max(5, min(120, $m ?: 30));
    }

    /** @return array{bank_name:string,bank_account:string,bank_account_name:string,pay_hint:string,expire_minutes:int} */
    public static function bankConfig(): array
    {
        $s = Site::allSettings();

        return [
            'bank_name' => (string) ($s['vip_bank_name'] ?? ''),
            'bank_account' => (string) ($s['vip_bank_account'] ?? ''),
            'bank_account_name' => (string) ($s['vip_bank_account_name'] ?? ''),
            'pay_hint' => (string) ($s['vip_pay_hint'] ?? ''),
            'expire_minutes' => self::expireMinutes(),
        ];
    }

    public static function makeOrderNo(): string
    {
        return 'VP' . date('YmdHis') . substr((string) microtime(true), -3) . random_int(100, 999);
    }

    /**
     * 懒过期：pending_pay 超时 → expired
     */
    public static function refreshStatus(VipPayOrder $order): VipPayOrder
    {
        if ($order->status === self::STATUS_PENDING_PAY) {
            $expire = strtotime((string) $order->expire_at);
            if ($expire && time() >= $expire) {
                $order->status = self::STATUS_EXPIRED;
                $order->save();
            }
        }

        return $order;
    }

    public static function remainSeconds(VipPayOrder $order): int
    {
        if ($order->status !== self::STATUS_PENDING_PAY) {
            return 0;
        }
        $expire = strtotime((string) $order->expire_at);

        return max(0, ($expire ?: 0) - time());
    }

    /** @return array<string, mixed> */
    public static function serialize(VipPayOrder $order, bool $withBank = true): array
    {
        $order = self::refreshStatus($order);
        $data = $order->toArray();
        $data['remain_seconds'] = self::remainSeconds($order);
        $data['status_label'] = self::statusLabel((string) $order->status);
        $data['amount_yuan'] = number_format((float) $order->amount, 2, '.', '');
        $status = (string) $order->status;
        $data['can_upload_voucher'] = match ($status) {
            self::STATUS_PENDING_PAY => self::remainSeconds($order) > 0,
            self::STATUS_PENDING_REVIEW, self::STATUS_REJECTED => true,
            default => false,
        };
        $data['can_cancel'] = in_array($status, [self::STATUS_PENDING_PAY, self::STATUS_PENDING_REVIEW], true);
        if ($withBank) {
            $bank = self::bankConfig();
            $data['pay_hint'] = $bank['pay_hint'];
            // 订单快照缺账户时回填站点最新配置（便于旧单展示）
            if (empty($data['bank_account'])) {
                $data['bank_name'] = $bank['bank_name'];
                $data['bank_account'] = $bank['bank_account'];
                $data['bank_account_name'] = $bank['bank_account_name'];
            }
        } else {
            unset($data['bank_name'], $data['bank_account'], $data['bank_account_name']);
        }

        return $data;
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING_PAY => '待付款',
            self::STATUS_PENDING_REVIEW => '待审核',
            self::STATUS_APPROVED => '已开通',
            self::STATUS_REJECTED => '已驳回',
            self::STATUS_EXPIRED => '已超时',
            self::STATUS_CANCELLED => '已取消',
            default => $status,
        };
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{ok:bool,message?:string,order?:VipPayOrder}
     */
    public static function create(User $user, VipGrade $grade, array $payload): array
    {
        if ((float) $grade->price_year <= 0) {
            return ['ok' => false, 'message' => '请选择付费会员档位'];
        }
        // 已付费会员不可降级购买更低档；同档续费、更高档升级可以。
        if (VipEntitlement::isPaidActive($user)) {
            $rank = static function (string $code): int {
                return match ($code) {
                    'silver' => 1,
                    'gold' => 2,
                    'diamond' => 3,
                    default => 0,
                };
            };
            $cur = $rank(VipEntitlement::tierCode($user));
            $target = $rank((string) $grade->code);
            if ($target > 0 && $target < $cur) {
                return ['ok' => false, 'message' => '不可购买更低档位，请选择当前档续费或更高档升级'];
            }
        }
        $bank = self::bankConfig();
        if (trim($bank['bank_account']) === '' || trim($bank['bank_account_name']) === '') {
            return ['ok' => false, 'message' => '收款账户尚未配置，请联系客服或稍后在后台「站点设置」完善对公账户后再试'];
        }

        // 同档位已有待付/待审单：直接复用，避免重复下单丢失凭证进度
        $existing = VipPayOrder::where('user_id', $user->id)
            ->where('vip_grade_id', (int) $grade->id)
            ->whereIn('status', [self::STATUS_PENDING_PAY, self::STATUS_PENDING_REVIEW])
            ->order('id', 'desc')
            ->find();
        if ($existing) {
            $existing = self::refreshStatus($existing);
            if (in_array((string) $existing->status, [self::STATUS_PENDING_PAY, self::STATUS_PENDING_REVIEW], true)) {
                return ['ok' => true, 'order' => $existing, 'reused' => true];
            }
        }

        $minutes = $bank['expire_minutes'];
        $orderNo = self::makeOrderNo();
        $order = VipPayOrder::create([
            'order_no' => $orderNo,
            'user_id' => (int) $user->id,
            'vip_grade_id' => (int) $grade->id,
            'grade_code' => (string) $grade->code,
            'grade_name' => (string) $grade->name,
            'amount' => round((float) $grade->price_year, 2),
            'company_name' => $payload['company_name'] ?? $user->company_name,
            'contact_name' => $payload['contact_name'] ?? $user->name,
            'mobile' => $payload['mobile'] ?? $user->mobile,
            'phone' => $payload['phone'] ?? null,
            'email' => $payload['email'] ?? $user->email,
            'remark' => $payload['remark'] ?? null,
            'status' => self::STATUS_PENDING_PAY,
            'expire_at' => date('Y-m-d H:i:s', time() + $minutes * 60),
            'bank_name' => $bank['bank_name'],
            'bank_account' => $bank['bank_account'],
            'bank_account_name' => $bank['bank_account_name'],
            'transfer_note' => $orderNo,
        ]);

        return ['ok' => true, 'order' => $order];
    }

    /**
     * @return array{ok:bool,message?:string,order?:VipPayOrder}
     */
    public static function submitVoucher(VipPayOrder $order, string $url): array
    {
        $order = self::refreshStatus($order);
        if ($order->status === self::STATUS_EXPIRED) {
            return ['ok' => false, 'message' => '订单已超时，请重新下单'];
        }
        if ($order->status === self::STATUS_APPROVED) {
            return ['ok' => false, 'message' => '订单已开通，无需再传凭证'];
        }
        if ($order->status === self::STATUS_CANCELLED) {
            return ['ok' => false, 'message' => '订单已取消'];
        }
        if ($order->status === self::STATUS_PENDING_PAY && self::remainSeconds($order) <= 0) {
            $order->status = self::STATUS_EXPIRED;
            $order->save();

            return ['ok' => false, 'message' => '订单已超时，请重新下单'];
        }
        $url = trim($url);
        if ($url === '' || !preg_match('#^(/|https?://)#i', $url)) {
            return ['ok' => false, 'message' => '请上传有效的付款凭证图片'];
        }
        $order->voucher_url = $url;
        $order->voucher_uploaded_at = date('Y-m-d H:i:s');
        $order->status = self::STATUS_PENDING_REVIEW;
        $order->admin_remark = null;
        $order->save();

        return ['ok' => true, 'order' => $order];
    }

    public static function cancel(VipPayOrder $order): array
    {
        $order = self::refreshStatus($order);
        if (!in_array($order->status, [self::STATUS_PENDING_PAY, self::STATUS_PENDING_REVIEW], true)) {
            return ['ok' => false, 'message' => '当前状态不可取消'];
        }
        $order->status = self::STATUS_CANCELLED;
        $order->save();

        return ['ok' => true, 'order' => $order];
    }
}
