<?php

namespace app\common;

use app\model\FinanceRecord;
use app\model\FundPayOrder;
use app\model\User;

/**
 * 资金对公充值：创建 → 转账 → 凭证 → 审核入账
 * 收款账户复用 VIP 对公配置
 */
class FundPay
{
    public const TYPE_CHARGE = 'charge';
    public const TYPE_REPORT = 'report';
    public const TYPE_PACK = 'contact_pack';
    public const TYPE_MALL = 'mall';
    public const TYPE_GROUP = 'group';
    public const TYPE_POINTS = 'points';

    public const STATUS_PENDING_PAY = 'pending_pay';
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    public static function bankConfig(): array
    {
        return VipPay::bankConfig();
    }

    public static function makeOrderNo(): string
    {
        return 'FC' . date('YmdHis') . substr((string) microtime(true), -3) . random_int(100, 999);
    }

    public static function refreshStatus(FundPayOrder $order): FundPayOrder
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

    public static function remainSeconds(FundPayOrder $order): int
    {
        if ($order->status !== self::STATUS_PENDING_PAY) {
            return 0;
        }
        $expire = strtotime((string) $order->expire_at);

        return max(0, ($expire ?: 0) - time());
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING_PAY => '待付款',
            self::STATUS_PENDING_REVIEW => '待审核',
            self::STATUS_APPROVED => '已入账',
            self::STATUS_REJECTED => '已驳回',
            self::STATUS_EXPIRED => '已超时',
            self::STATUS_CANCELLED => '已取消',
            default => $status,
        };
    }

    /** @return array<string, mixed> */
    public static function serialize(FundPayOrder $order, bool $withBank = true): array
    {
        $order = self::refreshStatus($order);
        $data = $order->toArray();
        $data['remain_seconds'] = self::remainSeconds($order);
        $data['status_label'] = self::statusLabel((string) $order->status);
        $data['amount_yuan'] = number_format((float) $order->amount, 2, '.', '');
        $data['pay_type_label'] = self::typeLabel((string) $order->pay_type);
        $data['effect_hint'] = self::effectHint((string) $order->pay_type);
        $data['done_label'] = ((string) $order->pay_type === self::TYPE_CHARGE) ? '已入账' : '已通过';
        $data['return_path'] = self::returnPath($order);
        if ((string) $order->status === self::STATUS_APPROVED && (string) $order->pay_type !== self::TYPE_CHARGE) {
            $data['status_label'] = '已通过';
        }
        $status = (string) $order->status;
        $data['can_upload_voucher'] = match ($status) {
            self::STATUS_PENDING_PAY => self::remainSeconds($order) > 0,
            self::STATUS_PENDING_REVIEW, self::STATUS_REJECTED => true,
            default => false,
        };
        $data['can_cancel'] = in_array($status, [self::STATUS_PENDING_PAY, self::STATUS_PENDING_REVIEW], true);
        if ($withBank) {
            $bank = self::bankConfig();
            $data['pay_hint'] = $bank['pay_hint'] ?: '请使用对公账户转账，备注填写订单号；上传凭证后由财务审核入账。';
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

    /**
     * @return array{ok:bool,message?:string,order?:FundPayOrder,reused?:bool}
     */
    public static function createCharge(User $user, float $amount, ?string $remark = null): array
    {
        $amount = round($amount, 2);
        if ($amount < 1) {
            return ['ok' => false, 'message' => '充值金额至少 1 元'];
        }
        if ($amount > 500000) {
            return ['ok' => false, 'message' => '单笔充值金额过大，请联系客服'];
        }
        $bank = self::bankConfig();
        if (trim($bank['bank_account']) === '' || trim($bank['bank_account_name']) === '') {
            return ['ok' => false, 'message' => '收款账户尚未配置，请联系客服'];
        }

        $existing = FundPayOrder::where('user_id', (int) $user->id)
            ->where('pay_type', self::TYPE_CHARGE)
            ->whereIn('status', [self::STATUS_PENDING_PAY, self::STATUS_PENDING_REVIEW])
            ->order('id', 'desc')
            ->find();
        if ($existing) {
            $existing = self::refreshStatus($existing);
            if (in_array((string) $existing->status, [self::STATUS_PENDING_PAY, self::STATUS_PENDING_REVIEW], true)) {
                return ['ok' => true, 'order' => $existing, 'reused' => true];
            }
        }

        $minutes = (int) ($bank['expire_minutes'] ?? 30);
        $orderNo = self::makeOrderNo();
        $order = FundPayOrder::create([
            'order_no' => $orderNo,
            'user_id' => (int) $user->id,
            'pay_type' => self::TYPE_CHARGE,
            'amount' => $amount,
            'title' => '账户充值',
            'remark' => $remark,
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
     * 研究报告、次数包、商城、团购、积分：与充值同一套对公凭证，审核通过后再生效。
     *
     * @param array<string, mixed> $payload
     * @return array{ok:bool,message?:string,order?:FundPayOrder,reused?:bool}
     */
    public static function createIntent(User $user, string $payType, float $amount, string $title, string $bizKey, array $payload, ?string $remark = null): array
    {
        $amount = round($amount, 2);
        if ($amount < 0.01) {
            return ['ok' => false, 'message' => '应付金额无效'];
        }
        if ($amount > 500000) {
            return ['ok' => false, 'message' => '单笔金额过大，请联系客服'];
        }
        $bank = self::bankConfig();
        if (trim($bank['bank_account']) === '' || trim($bank['bank_account_name']) === '') {
            return ['ok' => false, 'message' => '收款账户尚未配置，请联系客服'];
        }
        $bizKey = mb_substr(trim($bizKey), 0, 80);
        $existing = FundPayOrder::where('user_id', (int) $user->id)
            ->where('biz_key', $bizKey)
            ->whereIn('status', [self::STATUS_PENDING_PAY, self::STATUS_PENDING_REVIEW])
            ->order('id', 'desc')
            ->find();
        if ($existing) {
            $existing = self::refreshStatus($existing);
            if ((string) $existing->status === self::STATUS_PENDING_REVIEW) {
                return ['ok' => true, 'order' => $existing, 'reused' => true];
            }
            if ((string) $existing->status === self::STATUS_PENDING_PAY) {
                $minutes = (int) ($bank['expire_minutes'] ?? 30);
                $existing->amount = $amount;
                $existing->title = mb_substr($title, 0, 128);
                $existing->remark = $remark;
                $existing->payload = json_encode($payload, JSON_UNESCAPED_UNICODE);
                $existing->expire_at = date('Y-m-d H:i:s', time() + $minutes * 60);
                $existing->save();

                return ['ok' => true, 'order' => $existing, 'reused' => true];
            }
        }

        $minutes = (int) ($bank['expire_minutes'] ?? 30);
        $orderNo = self::makeOrderNo();
        $order = FundPayOrder::create([
            'order_no' => $orderNo,
            'user_id' => (int) $user->id,
            'pay_type' => $payType,
            'biz_key' => $bizKey,
            'amount' => $amount,
            'title' => mb_substr($title, 0, 128),
            'remark' => $remark,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'status' => self::STATUS_PENDING_PAY,
            'expire_at' => date('Y-m-d H:i:s', time() + $minutes * 60),
            'bank_name' => $bank['bank_name'],
            'bank_account' => $bank['bank_account'],
            'bank_account_name' => $bank['bank_account_name'],
            'transfer_note' => $orderNo,
        ]);

        return ['ok' => true, 'order' => $order];
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_CHARGE => '账户充值',
            self::TYPE_REPORT => '研究报告',
            self::TYPE_PACK => '联系方式次数包',
            self::TYPE_MALL => '商城订单',
            self::TYPE_GROUP => '团购订单',
            self::TYPE_POINTS => '购买积分',
            default => $type,
        };
    }

    public static function effectHint(string $type): string
    {
        return match ($type) {
            self::TYPE_REPORT => '财务核对到账后将开通本报告全文',
            self::TYPE_PACK => '财务核对到账后将增加联系方式查看次数',
            self::TYPE_MALL => '财务核对到账后商城订单生效',
            self::TYPE_GROUP => '财务核对到账后团购订单生效',
            self::TYPE_POINTS => '财务核对到账后积分到账',
            default => '财务核对到账后将为您入账余额',
        };
    }

    public static function returnPath(FundPayOrder $order): string
    {
        $payload = json_decode((string) ($order->payload ?? ''), true);
        $payload = is_array($payload) ? $payload : [];

        return match ((string) $order->pay_type) {
            self::TYPE_REPORT => '/news/'.(int) ($payload['article_id'] ?? 0),
            self::TYPE_PACK => '/member/contact-packs',
            self::TYPE_MALL => '/member/orders',
            self::TYPE_GROUP => '/member/group-orders',
            self::TYPE_POINTS => '/member/credit',
            default => '/member/finance',
        };
    }

    /** @return array{ok:bool,message?:string,order?:FundPayOrder} */
    public static function submitVoucher(FundPayOrder $order, string $url): array
    {
        $order = self::refreshStatus($order);
        if ($order->status === self::STATUS_EXPIRED) {
            return ['ok' => false, 'message' => '订单已超时，请重新下单'];
        }
        if ($order->status === self::STATUS_APPROVED) {
            return ['ok' => false, 'message' => '已入账，无需再传凭证'];
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

    /** @return array{ok:bool,message?:string,order?:FundPayOrder} */
    public static function cancel(FundPayOrder $order): array
    {
        $order = self::refreshStatus($order);
        if (!in_array($order->status, [self::STATUS_PENDING_PAY, self::STATUS_PENDING_REVIEW], true)) {
            return ['ok' => false, 'message' => '当前状态不可取消'];
        }
        $order->status = self::STATUS_CANCELLED;
        $order->save();

        return ['ok' => true, 'order' => $order];
    }

    /**
     * 审核通过后按订单类型生效。充值入余额，其余不开余额、直接交付。
     *
     * @return array{ok:bool,message?:string}
     */
    public static function approve(FundPayOrder $order, int $adminId = 0): array
    {
        if ((string) $order->status === self::STATUS_APPROVED && !empty($order->reviewed_at)) {
            return ['ok' => true];
        }
        $user = User::where('id', (int) $order->user_id)->lock(false)->find();
        if (!$user) {
            return ['ok' => false, 'message' => '用户不存在'];
        }
        \think\facade\Db::startTrans();
        try {
            $fresh = FundPayOrder::where('id', (int) $order->id)->lock(true)->find();
            if (!$fresh) {
                \think\facade\Db::rollback();

                return ['ok' => false, 'message' => '订单不存在'];
            }
            if (!empty($fresh->reviewed_at)) {
                \think\facade\Db::commit();

                return ['ok' => true];
            }
            $order = $fresh;
            $locked = User::where('id', (int) $user->id)->lock(true)->find();
            if (!$locked) {
                throw new \RuntimeException('用户不存在');
            }
            $err = self::fulfill($order, $locked);
            if ($err !== null) {
                \think\facade\Db::rollback();
                $order->status = self::STATUS_PENDING_REVIEW;
                $order->save();

                return ['ok' => false, 'message' => $err];
            }
            $order->status = self::STATUS_APPROVED;
            $order->reviewed_at = date('Y-m-d H:i:s');
            if ($adminId > 0) {
                $order->reviewed_by = $adminId;
            }
            $order->save();
            \think\facade\Db::commit();
        } catch (\Throwable $e) {
            \think\facade\Db::rollback();
            $order->status = self::STATUS_PENDING_REVIEW;
            $order->save();

            return ['ok' => false, 'message' => '处理失败，请稍后重试'];
        }

        return ['ok' => true];
    }

    /** @return string|null 失败原因 */
    private static function fulfill(FundPayOrder $order, User $user): ?string
    {
        $payload = json_decode((string) ($order->payload ?? ''), true);
        $payload = is_array($payload) ? $payload : [];
        $type = (string) $order->pay_type;
        if ($type === '' || $type === self::TYPE_CHARGE) {
            $amount = round((float) $order->amount, 2);
            $user->balance = round((float) $user->balance + $amount, 2);
            $user->save();
            FinanceRecord::create([
                'user_id' => (int) $user->id,
                'type' => 'charge',
                'amount' => $amount,
                'balance_after' => $user->balance,
                'title' => '对公充值入账',
                'remark' => '订单 ' . $order->order_no,
            ]);

            return null;
        }
        if ($type === self::TYPE_REPORT) {
            return VipEntitlement::grantPurchasedReport($user, (int) ($payload['article_id'] ?? 0));
        }
        if ($type === self::TYPE_PACK) {
            $times = (int) ($payload['times'] ?? 0);
            if ($times <= 0) {
                return '次数包数据无效';
            }
            VipEntitlement::addContactBonus($user, $times, 365);
            \app\model\VipContactPackOrder::create([
                'user_id' => (int) $user->id,
                'pack_id' => (int) ($payload['pack_id'] ?? 0),
                'times' => $times,
                'list_price' => (float) ($payload['list_price'] ?? 0),
                'pay_price' => round((float) $order->amount, 2),
                'discount' => (float) ($payload['discount'] ?? 1),
                'payment_method' => 'voucher',
                'status' => 'paid',
            ]);

            return null;
        }
        if ($type === self::TYPE_MALL) {
            $lines = $payload['lines'] ?? [];
            if (!is_array($lines) || $lines === []) {
                return '商城订单明细无效';
            }
            $mall = \app\model\Order::create([
                'order_no' => 'YG' . date('YmdHis') . random_int(1000, 9999),
                'user_id' => (int) $user->id,
                'amount' => round((float) $order->amount, 2),
                'status' => 'paid',
                'payment_method' => 'voucher',
                'receiver_name' => (string) ($payload['receiver_name'] ?? $user->name),
                'receiver_phone' => (string) ($payload['receiver_phone'] ?? $user->mobile),
                'receiver_address' => (string) ($payload['receiver_address'] ?? ''),
                'remark' => (string) ($payload['remark'] ?? ''),
                'paid_at' => date('Y-m-d H:i:s'),
            ]);
            $pids = [];
            foreach ($lines as $line) {
                if (!is_array($line)) {
                    continue;
                }
                $pid = (int) ($line['product_id'] ?? 0);
                $qty = max(1, (int) ($line['quantity'] ?? 1));
                if ($pid > 0) {
                    $product = \app\model\Product::where('id', $pid)->lock(true)->find();
                    if (!$product || (string) $product->status !== 'approved' || (int) ($product->is_listed ?? 0) !== 1) {
                        return '商品已下架或不可售，请驳回后让用户重新下单';
                    }
                    $stock = (int) ($product->stock ?? 0);
                    // stock=0 视为售罄（与礼品一致）；有库存则扣减
                    if ($stock <= 0) {
                        return ($product->title ?: '商品').'已售罄，请驳回后让用户重新下单';
                    }
                    if ($qty > $stock) {
                        return ($product->title ?: '商品').'库存不足，请驳回后让用户重新下单';
                    }
                    $product->stock = $stock - $qty;
                    $product->save();
                }
                \app\model\OrderItem::create([
                    'order_id' => (int) $mall->id,
                    'product_id' => $pid,
                    'title' => (string) ($line['title'] ?? ''),
                    'price' => $line['price'] ?? 0,
                    'quantity' => $qty,
                ]);
                $pids[] = $pid;
            }
            if ($pids) {
                \app\model\CartItem::where('user_id', (int) $user->id)->whereIn('product_id', $pids)->delete();
            }

            return null;
        }
        if ($type === self::TYPE_GROUP) {
            $dealId = (int) ($payload['group_deal_id'] ?? 0);
            $deal = \app\model\GroupDeal::where('id', $dealId)->lock(true)->find();
            if (!$deal || !in_array((string) $deal->status, ['approved', 'published'], true)) {
                return '团购不存在或已下架';
            }
            $qty = max(1, (int) ($payload['quantity'] ?? 1));
            $stock = (int) ($deal->stock ?? 0);
            // stock=0 视为售罄，不再当无限库存
            if ($stock <= 0 || $qty > $stock) {
                return '库存不足，请驳回后让用户重新下单';
            }
            $deal->stock = $stock - $qty;
            $deal->sold = (int) ($deal->sold ?? 0) + $qty;
            $deal->save();
            \app\model\GroupOrder::create([
                'group_deal_id' => (int) $deal->id,
                'buyer_user_id' => (int) $user->id,
                'seller_user_id' => $deal->user_id ?: null,
                'title' => $deal->title,
                'thumb' => $deal->thumb,
                'price' => (float) $deal->price,
                'quantity' => $qty,
                'amount' => round((float) $order->amount, 2),
                'receiver_name' => (string) ($payload['receiver_name'] ?? ''),
                'receiver_phone' => (string) ($payload['receiver_phone'] ?? ''),
                'receiver_address' => (string) ($payload['receiver_address'] ?? ''),
                'remark' => (string) ($payload['remark'] ?? ''),
                'status' => 'paid',
                'payment_method' => 'voucher',
                'paid_at' => date('Y-m-d H:i:s'),
            ]);

            return null;
        }
        if ($type === self::TYPE_POINTS) {
            $points = (int) ($payload['points'] ?? 0);
            if ($points <= 0) {
                return '积分数量无效';
            }
            $user->points = (int) $user->points + $points;
            $user->save();

            return null;
        }

        return '未知的付款类型';
    }

    public static function reject(FundPayOrder $order, int $adminId = 0): void
    {
        $order->status = self::STATUS_REJECTED;
        $order->reviewed_at = date('Y-m-d H:i:s');
        if ($adminId > 0) {
            $order->reviewed_by = $adminId;
        }
        $order->save();
    }
}
