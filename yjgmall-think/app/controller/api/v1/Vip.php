<?php
namespace app\controller\api\v1;

use app\BaseController;
use app\common\FundPay;
use app\common\VipEntitlement;
use app\common\VipPay;
use app\model\AdPlace;
use app\model\User;
use app\model\VipApplication;
use app\model\VipContactPack;
use app\model\VipContactPackOrder;
use app\model\VipGrade;
use app\model\VipInviteReward;
use app\model\VipPayOrder;
use app\model\VipSeat;
use think\facade\Db;
use think\Response;

class Vip extends BaseController
{
    public function grades(): Response
    {
        $rows = VipGrade::where('is_active', 1)
            ->whereIn('code', ['free', 'silver', 'gold', 'diamond'])
            ->order('sort', 'asc')
            ->select();
        $data = [];
        foreach ($rows as $g) {
            $data[] = VipEntitlement::normalizeGradeRow($g);
        }

        return json_data($data);
    }

    public function me(): Response
    {
        $user = $this->request->user;
        VipEntitlement::ensureInviteCode($user);
        $summary = VipEntitlement::entitlementSummary($user);
        $ownerId = VipEntitlement::companyRootId($user);
        $seats = VipSeat::where('owner_user_id', $ownerId)
            ->whereIn('status', ['pending', 'active'])
            ->order('id', 'asc')
            ->select()
            ->toArray();
        $summary['seats'] = $seats;
        $summary['seats_used'] = count(array_filter($seats, fn ($s) => ($s['status'] ?? '') === 'active'));
        $summary['is_seat_member'] = (int) ($user->vip_owner_id ?? 0) > 0;
        $summary['owner_user_id'] = $ownerId;

        return json_data($summary);
    }

    public function adPlaces(): Response
    {
        return json_data(AdPlace::where('is_active', 1)->order('sort', 'asc')->select()->toArray());
    }

    public function apply(): Response
    {
        $user = $this->request->user;
        $data = $this->request->post();
        $gradeId = (int) ($data['vip_grade_id'] ?? $data['grade_id'] ?? 0);
        $grade = VipGrade::where('id', $gradeId)->where('is_active', 1)->find();
        if (!$grade || (float) $grade->price_year <= 0) {
            return json_error('请选择付费会员档位', 422);
        }
        $app = VipApplication::create([
            'user_id' => $user->id,
            'vip_grade_id' => $grade->id,
            'company_name' => $data['company_name'] ?? $user->company_name,
            'contact_name' => $data['contact_name'] ?? $data['contact'] ?? $user->name,
            'phone' => $data['phone'] ?? null,
            'mobile' => $data['mobile'] ?? $user->mobile,
            'email' => $data['email'] ?? $user->email,
            'qq' => $data['qq'] ?? $user->qq,
            'remark' => $data['remark'] ?? $data['content'] ?? null,
            'status' => 'pending',
        ]);

        return json(['data' => $app->toArray(), 'message' => '申请已提交'], 201);
    }

    /** 对公转账收银台配置（收款账户 + 时限） */
    public function payConfig(): Response
    {
        $bank = VipPay::bankConfig();
        // 账号中间打码仅用于预览页；正式支付页下单后返回完整快照
        $acc = $bank['bank_account'];
        if (strlen($acc) > 8 && !str_contains($acc, '请在后台')) {
            $bank['bank_account_masked'] = substr($acc, 0, 4) . str_repeat('*', max(4, strlen($acc) - 8)) . substr($acc, -4);
        } else {
            $bank['bank_account_masked'] = $acc;
        }
        unset($bank['bank_account']);

        return json_data($bank);
    }

    /** 创建对公转账待支付订单 → 跳转收银台 */
    public function createPayOrder(): Response
    {
        $user = $this->request->user;
        $data = $this->request->post();
        $gradeId = (int) ($data['vip_grade_id'] ?? $data['grade_id'] ?? 0);
        $grade = VipGrade::where('id', $gradeId)->where('is_active', 1)->find();
        if (!$grade) {
            return json_error('请选择付费会员档位', 422);
        }
        $contact = trim((string) ($data['contact_name'] ?? $data['contact'] ?? $user->name ?? $user->username ?? ''));
        if ($contact === '') {
            return json_error('请填写联系人', 422);
        }
        $result = VipPay::create($user, $grade, [
            'company_name' => $data['company_name'] ?? $user->company_name,
            'contact_name' => $contact,
            'mobile' => $data['mobile'] ?? $user->mobile,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? $user->email,
            'remark' => $data['remark'] ?? null,
        ]);
        if (empty($result['ok'])) {
            return json_error($result['message'] ?? '下单失败', 422);
        }
        /** @var VipPayOrder $order */
        $order = $result['order'];

        $reused = !empty($result['reused']);

        return json([
            'data' => VipPay::serialize($order),
            'message' => $reused
                ? '已有进行中的付款订单，已为您打开'
                : '订单已生成，请在时限内完成转账并上传凭证',
            'reused' => $reused,
        ], $reused ? 200 : 201);
    }

    public function payOrders(): Response
    {
        $user = $this->request->user;
        $rows = VipPayOrder::where('user_id', $user->id)
            ->order('id', 'desc')
            ->limit(30)
            ->select();
        $list = [];
        foreach ($rows as $row) {
            $list[] = VipPay::serialize($row, false);
        }

        return json_data($list);
    }

    public function payOrder($id): Response
    {
        $user = $this->request->user;
        $order = VipPayOrder::where('id', (int) $id)->where('user_id', $user->id)->find();
        if (!$order) {
            // also allow lookup by order_no
            $order = VipPayOrder::where('order_no', (string) $id)->where('user_id', $user->id)->find();
        }
        if (!$order) {
            return json_error('订单不存在', 404);
        }

        return json_data(VipPay::serialize($order));
    }

    public function submitPayVoucher($id): Response
    {
        $user = $this->request->user;
        $order = VipPayOrder::where('id', (int) $id)->where('user_id', $user->id)->find();
        if (!$order) {
            return json_error('订单不存在', 404);
        }
        $url = (string) ($this->request->post('voucher_url') ?? $this->request->post('url') ?? '');
        $result = VipPay::submitVoucher($order, $url);
        if (empty($result['ok'])) {
            return json_error($result['message'] ?? '提交失败', 422);
        }

        return json([
            'data' => VipPay::serialize($result['order']),
            'message' => '凭证已提交，请等待财务审核',
        ]);
    }

    public function cancelPayOrder($id): Response
    {
        $user = $this->request->user;
        $order = VipPayOrder::where('id', (int) $id)->where('user_id', $user->id)->find();
        if (!$order) {
            return json_error('订单不存在', 404);
        }
        $result = VipPay::cancel($order);
        if (empty($result['ok'])) {
            return json_error($result['message'] ?? '无法取消', 422);
        }

        return json(['data' => VipPay::serialize($result['order'], false), 'message' => '订单已取消']);
    }

    public function unlockContact(): Response
    {
        $user = $this->request->user;
        $type = (string) $this->request->post('target_type', 'purchase');
        $id = (int) $this->request->post('target_id', 0);
        if ($id <= 0) {
            return json_error('参数错误', 422);
        }
        $result = VipEntitlement::unlockContact($user, $type, $id);
        if (empty($result['ok'])) {
            $code = ($result['code'] ?? '') === 'quota_exceeded' ? 403 : 422;

            return json([
                'message' => $result['message'] ?? '解锁失败',
                'code' => $result['code'] ?? 'error',
                'entitlement' => $result['entitlement'] ?? null,
            ], $code);
        }

        return json_data([
            'contact' => $result['contact'],
            'already' => !empty($result['already']),
            'entitlement' => $result['entitlement'],
        ]);
    }

    /** 普通 / 白银购买单份研究报告：生成对公凭证订单 */
    public function buyReport(): Response
    {
        $user = $this->request->user;
        $id = (int) $this->request->post('article_id', $this->request->post('id', 0));
        $prep = VipEntitlement::prepareReportPurchase($user, $id);
        if (empty($prep['ok'])) {
            return json(['message' => $prep['message'] ?? '购买失败'], 422);
        }
        if (!empty($prep['already'])) {
            return json_data(['already' => true, 'message' => $prep['message'] ?? '您已可阅读本报告']);
        }
        $result = FundPay::createIntent(
            $user,
            FundPay::TYPE_REPORT,
            (float) $prep['price'],
            '研究报告：'.(string) ($prep['title'] ?? ''),
            'report:'.$id,
            ['article_id' => $id],
            (string) ($prep['title'] ?? '')
        );
        if (empty($result['ok']) || empty($result['order'])) {
            return json_error($result['message'] ?? '下单失败', 422);
        }

        return json([
            'data' => FundPay::serialize($result['order']),
            'message' => !empty($result['reused']) ? '已有进行中的付款订单' : '请对公转账并上传付款凭证',
            'reused' => !empty($result['reused']),
        ]);
    }

    /** —— 席位 —— */
    public function seats(): Response
    {
        $user = $this->request->user;
        if ((int) ($user->vip_owner_id ?? 0) > 0) {
            return json_error('席位成员无权管理席位', 403);
        }
        $grade = VipEntitlement::resolveGrade($user);
        $limit = max(1, (int) $grade['seat_count']);
        $rows = VipSeat::where('owner_user_id', $user->id)
            ->whereIn('status', ['pending', 'active'])
            ->order('id', 'asc')
            ->select();

        return json_data([
            'seat_limit' => $limit,
            'items' => $rows->toArray(),
        ]);
    }

    public function inviteSeat(): Response
    {
        $user = $this->request->user;
        if ((int) ($user->vip_owner_id ?? 0) > 0) {
            return json_error('席位成员无权邀请', 403);
        }
        $grade = VipEntitlement::resolveGrade($user);
        $limit = max(1, (int) $grade['seat_count']);
        // owner occupies 1 seat conceptually; additional seats = limit - 1 for free/silver (limit 1 means only self)
        $extra = max(0, $limit - 1);
        $used = VipSeat::where('owner_user_id', $user->id)->whereIn('status', ['pending', 'active'])->count();
        if ($extra <= 0) {
            return json_error('当前档位仅 1 个席位（本人），请升级黄金/星钻以增加席位', 422);
        }
        if ($used >= $extra) {
            return json_error('席位已满，请先回收或升级档位', 422);
        }
        $mobile = trim((string) $this->request->post('mobile', ''));
        $email = trim((string) $this->request->post('email', ''));
        // 手机/邮箱仅作备注；真正加入靠邀请链接，不发短信/邮件
        $token = bin2hex(random_bytes(16));
        $seat = VipSeat::create([
            'owner_user_id' => $user->id,
            'mobile' => $mobile !== '' ? $mobile : null,
            'email' => $email !== '' ? $email : null,
            'invite_token' => $token,
            'status' => 'pending',
        ]);

        return json([
            'data' => $seat->toArray(),
            'accept_path' => '/member/seats?token=' . $token,
            'message' => '邀请链接已生成，请复制发给成员打开接受（不会自动发短信/邮件）',
        ], 201);
    }

    public function acceptSeat(): Response
    {
        $user = $this->request->user;
        $token = (string) $this->request->post('token', '');
        if ($token === '') {
            return json_error('缺少邀请令牌', 422);
        }
        $seat = VipSeat::where('invite_token', $token)->where('status', 'pending')->find();
        if (!$seat) {
            return json_error('邀请无效或已使用', 404);
        }
        if ((int) $seat->owner_user_id === (int) $user->id) {
            return json_error('不能接受自己的邀请', 422);
        }
        if ((int) ($user->vip_owner_id ?? 0) > 0) {
            return json_error('您已是其他企业的席位成员', 422);
        }
        $seat->save([
            'member_user_id' => $user->id,
            'status' => 'active',
            'accepted_at' => date('Y-m-d H:i:s'),
        ]);
        $user->save(['vip_owner_id' => (int) $seat->owner_user_id]);

        return json_data(['seat' => $seat->refresh()->toArray(), 'message' => '已加入企业席位']);
    }

    public function revokeSeat($id): Response
    {
        $user = $this->request->user;
        $seat = VipSeat::where('owner_user_id', $user->id)->find($id);
        if (!$seat) {
            return json_error('席位不存在', 404);
        }
        if ($seat->member_user_id) {
            $member = User::find($seat->member_user_id);
            if ($member && (int) ($member->vip_owner_id ?? 0) === (int) $user->id) {
                $member->save(['vip_owner_id' => null]);
            }
        }
        $seat->save([
            'status' => 'revoked',
            'revoked_at' => date('Y-m-d H:i:s'),
        ]);

        return json_data(['message' => '已回收席位']);
    }

    /** —— 次数包 —— */
    public function contactPacks(): Response
    {
        $user = $this->request->user ?? null;
        $discount = $user ? VipEntitlement::packDiscount($user) : 1.0;
        $rows = VipContactPack::where('is_active', 1)->order('sort', 'asc')->select();
        $data = [];
        foreach ($rows as $p) {
            $list = (float) $p->price;
            $pay = round($list * $discount, 2);
            $data[] = [
                'id' => (int) $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'times' => (int) $p->times,
                'list_price' => $list,
                'pay_price' => $pay,
                'discount' => $discount,
            ];
        }

        return json_data($data);
    }

    public function buyContactPack(): Response
    {
        $user = $this->request->user;
        $packId = (int) $this->request->post('pack_id', 0);
        $pack = VipContactPack::where('id', $packId)->where('is_active', 1)->find();
        if (!$pack) {
            return json_error('次数包不存在', 404);
        }
        $discount = VipEntitlement::packDiscount($user);
        $list = (float) $pack->price;
        $pay = round($list * $discount, 2);
        $result = FundPay::createIntent(
            $user,
            FundPay::TYPE_PACK,
            $pay,
            '联系方式次数包：'.(string) $pack->name,
            'pack:'.(int) $pack->id,
            [
                'pack_id' => (int) $pack->id,
                'times' => (int) $pack->times,
                'list_price' => $list,
                'discount' => $discount,
            ],
            (string) $pack->name
        );
        if (empty($result['ok']) || empty($result['order'])) {
            return json_error($result['message'] ?? '下单失败', 422);
        }

        return json([
            'data' => FundPay::serialize($result['order']),
            'message' => !empty($result['reused']) ? '已有进行中的付款订单' : '请对公转账并上传付款凭证',
            'reused' => !empty($result['reused']),
        ]);
    }

    /** —— 拉新 —— */
    public function inviteStats(): Response
    {
        $user = $this->request->user;
        $code = VipEntitlement::ensureInviteCode($user);
        $invitees = User::where('invited_by_user_id', $user->id)
            ->field('id,username,name,company_name,created_at,vip_grade_code,level')
            ->order('id', 'desc')
            ->limit(50)
            ->select()
            ->toArray();
        $rewards = VipInviteReward::where('inviter_user_id', $user->id)
            ->order('id', 'desc')
            ->limit(50)
            ->select()
            ->toArray();

        return json_data([
            'invite_code' => $code,
            'invite_link' => '/register?invite=' . $code,
            'invitees' => $invitees,
            'rewards' => $rewards,
        ]);
    }

    /** 招聘置顶：星钻等月赠优先，否则按 top_price 扣余额 */
    public function stickyJob(): Response
    {
        $user = $this->request->user;
        $jobId = (int) $this->request->post('job_id', 0);
        $job = \app\model\JobPost::where('user_id', $user->id)->find($jobId);
        if (!$job) {
            return json_error('招聘不存在', 404);
        }
        if ((string) $job->status !== 'approved') {
            return json_error('仅已上架职位可置顶', 422);
        }
        $grade = VipEntitlement::resolveGrade($user);
        $limit = (int) ($grade['job_sticky_per_month'] ?? 0);
        $topPrice = (int) ($grade['top_price'] ?? 0);
        $month = VipEntitlement::monthKey();
        $usedMonth = (string) ($user->job_sticky_used_month ?? '');
        $usedCount = (int) ($user->job_sticky_used_count ?? 0);
        if ($usedMonth !== $month) {
            $usedCount = 0;
        }
        $useGift = $limit > 0 && $usedCount < $limit;
        $paid = 0.0;

        Db::startTrans();
        try {
            if ($useGift) {
                $user->save([
                    'job_sticky_used_month' => $month,
                    'job_sticky_used_count' => $usedCount + 1,
                ]);
            } else {
                if ($topPrice <= 0) {
                    Db::rollback();

                    return json_error('当前档位无可用置顶次数，请升级会员', 403);
                }
                $balance = (float) ($user->balance ?? 0);
                if ($balance < $topPrice) {
                    Db::rollback();

                    return json_error("余额不足，置顶需 {$topPrice} 元，请先充值或升级会员", 422);
                }
                $user->balance = round($balance - $topPrice, 2);
                $user->save();
                \app\model\FinanceRecord::create([
                    'user_id' => (int) $user->id,
                    'type' => 'job_sticky',
                    'amount' => -$topPrice,
                    'balance_after' => $user->balance,
                    'title' => '招聘职位置顶',
                    'remark' => '职位#' . $jobId . ' 置顶3天',
                ]);
                $paid = (float) $topPrice;
            }
            $job->save([
                'is_sticky' => 1,
                'sticky_until' => date('Y-m-d H:i:s', strtotime('+3 days')),
            ]);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();

            return json_error('置顶失败，请稍后重试', 500);
        }

        $msg = $useGift ? '已使用本月赠送置顶，生效 3 天' : "已扣款 {$paid} 元置顶，生效 3 天";

        return json_data([
            'message' => $msg,
            'paid' => $paid,
            'used_gift' => $useGift,
            'job' => $job->refresh()->toArray(),
            'entitlement' => VipEntitlement::entitlementSummary(User::find($user->id) ?: $user),
        ]);
    }
}
