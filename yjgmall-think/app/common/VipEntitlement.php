<?php

namespace app\common;

use app\model\Purchase;
use app\model\User;
use app\model\VipContactUnlock;
use app\model\VipGrade;
use app\model\VipQuotaUsage;
use think\facade\Db;

/**
 * Enterprise VIP entitlements from vip_grades.limits.
 */
class VipEntitlement
{
    public static function freeLimits(): array
    {
        return [
            'code' => 'free',
            'name' => '普通会员',
            'level' => 0,
            'price_year' => 0,
            'badge' => '',
            'contact_quota_month' => 0,
            'quote_history_days' => 7,
            'exposure_weight' => 0,
            'top_price' => 1000,
            'seat_count' => 1,
            'cert_bonus_contacts' => 5,
            'cert_bonus_days' => 30,
            'resume_contact_quota_month' => 1,
            'job_sticky_per_month' => 0,
            'jobs_online_limit' => 5,
            'matrix' => [],
            'perks' => [],
        ];
    }

    /** 0 = unlimited */
    public static function jobsOnlineLimit(?User $user): int
    {
        return max(0, (int) (self::resolveGrade($user)['jobs_online_limit'] ?? 5));
    }

    public static function resumeFullVisible(?User $user): bool
    {
        return $user ? self::isPaidActive($user) : false;
    }

    /** Count approved (online) jobs for company root + seat members. */
    public static function countOnlineJobs(User $user): int
    {
        $ids = self::companyMemberIds($user);
        if (!$ids) {
            return 0;
        }

        return (int) \app\model\JobPost::whereIn('user_id', $ids)->where('status', 'approved')->count();
    }

    /** @return int[] */
    public static function companyMemberIds(User $user): array
    {
        $root = self::companyRootId($user);
        $ids = [$root];
        $owned = User::where('vip_owner_id', $root)->column('id');
        foreach ($owned as $id) {
            $ids[] = (int) $id;
        }
        try {
            $seatIds = \app\model\VipSeat::where('owner_user_id', $root)
                ->where('status', 'accepted')
                ->whereNotNull('member_user_id')
                ->column('member_user_id');
            foreach ($seatIds as $id) {
                $ids[] = (int) $id;
            }
        } catch (\Throwable $e) {
            // seats table may be absent on old deploys
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * @return array{ok:bool,code?:string,message?:string,online?:int,limit?:int}
     */
    public static function assertCanGoOnline(User $user, ?int $excludeJobId = null): array
    {
        $limit = self::jobsOnlineLimit($user);
        if ($limit <= 0) {
            return ['ok' => true, 'online' => self::countOnlineJobs($user), 'limit' => 0];
        }
        $ids = self::companyMemberIds($user);
        $q = \app\model\JobPost::whereIn('user_id', $ids)->where('status', 'approved');
        if ($excludeJobId) {
            $q->where('id', '<>', $excludeJobId);
        }
        $online = (int) $q->count();
        if ($online >= $limit) {
            return [
                'ok' => false,
                'code' => 'online_limit',
                'message' => "当前会员同时在线职位上限为 {$limit} 个，请下架其他职位或升级会员",
                'online' => $online,
                'limit' => $limit,
            ];
        }

        return ['ok' => true, 'online' => $online, 'limit' => $limit];
    }

    /** Company root used for shared unlocks (owner of seats). */
    public static function companyRootId(User $user): int
    {
        $owner = (int) ($user->vip_owner_id ?? 0);
        if ($owner > 0) {
            return $owner;
        }

        return (int) $user->id;
    }

    public static function ensureInviteCode(User $user): string
    {
        if (!empty($user->invite_code)) {
            return (string) $user->invite_code;
        }
        for ($i = 0; $i < 5; $i++) {
            $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            try {
                $user->save(['invite_code' => $code]);

                return $code;
            } catch (\Throwable $e) {
                continue;
            }
        }

        return '';
    }

    public static function packDiscount(User $user): float
    {
        $g = self::resolveGrade($user);
        $d = (float) ($g['pack_discount'] ?? 1);

        return $d > 0 && $d <= 1 ? $d : 1.0;
    }

    public static function addVipDays(User $user, int $days, ?string $gradeCode = null): void
    {
        if ($days <= 0) {
            return;
        }
        $base = time();
        $exp = $user->vip_expire_at ?? null;
        if ($exp && strtotime((string) $exp) > $base) {
            $base = strtotime((string) $exp);
        }
        $data = [
            'vip_expire_at' => date('Y-m-d H:i:s', $base + $days * 86400),
            'group_code' => 'vip',
            'status' => 'active',
        ];
        if ($gradeCode && $gradeCode !== 'free') {
            $grade = VipGrade::where('code', $gradeCode)->find();
            if ($grade) {
                $data['vip_grade_code'] = $gradeCode;
                $data['level'] = max((int) $user->level, (int) $grade->level);
            }
        } elseif (!self::isPaidActive($user)) {
            // temporary silver for free inviters
            $silver = VipGrade::where('code', 'silver')->find();
            if ($silver) {
                $data['vip_grade_code'] = 'silver';
                $data['level'] = max((int) $user->level, (int) $silver->level);
            }
        }
        $user->save($data);
    }

    /**
     * Register reward: inviter gets 7 days of own grade, or 3 days silver if free.
     */
    public static function rewardInviteRegister(User $inviter, User $invitee): void
    {
        $exists = \app\model\VipInviteReward::where('inviter_user_id', $inviter->id)
            ->where('invitee_user_id', $invitee->id)
            ->where('reward_type', 'register')
            ->find();
        if ($exists) {
            return;
        }
        $paid = self::isPaidActive($inviter);
        $days = $paid ? 7 : 3;
        $code = $paid ? (string) $inviter->vip_grade_code : 'silver';
        self::addVipDays($inviter, $days, $code);
        \app\model\VipInviteReward::create([
            'inviter_user_id' => $inviter->id,
            'invitee_user_id' => $invitee->id,
            'reward_type' => 'register',
            'days_granted' => $days,
            'note' => $paid ? "拉新注册奖励{$days}天{$code}" : "拉新注册奖励{$days}天白银",
        ]);
    }

    /**
     * Upgrade reward: inviter gets 30 days of own grade, or 15 days silver if free.
     */
    public static function rewardInviteUpgrade(User $invitee): void
    {
        $inviterId = (int) ($invitee->invited_by_user_id ?? 0);
        if ($inviterId <= 0) {
            return;
        }
        $inviter = User::find($inviterId);
        if (!$inviter) {
            return;
        }
        $exists = \app\model\VipInviteReward::where('inviter_user_id', $inviter->id)
            ->where('invitee_user_id', $invitee->id)
            ->where('reward_type', 'upgrade')
            ->find();
        if ($exists) {
            return;
        }
        $paid = self::isPaidActive($inviter);
        $days = $paid ? 30 : 15;
        $code = $paid ? (string) $inviter->vip_grade_code : 'silver';
        self::addVipDays($inviter, $days, $code);
        \app\model\VipInviteReward::create([
            'inviter_user_id' => $inviter->id,
            'invitee_user_id' => $invitee->id,
            'reward_type' => 'upgrade',
            'days_granted' => $days,
            'note' => $paid ? "被邀请人升级奖励{$days}天{$code}" : "被邀请人升级奖励{$days}天白银",
        ]);
    }

    public static function addContactBonus(User $user, int $times, int $validDays = 365): void
    {
        $left = max(0, (int) ($user->contact_bonus_left ?? 0));
        $exp = $user->contact_bonus_expire ?? null;
        $base = time();
        if ($exp && strtotime((string) $exp) > $base) {
            $base = strtotime((string) $exp);
        }
        $user->save([
            'contact_bonus_left' => $left + $times,
            'contact_bonus_expire' => date('Y-m-d H:i:s', $base + $validDays * 86400),
        ]);
    }

    /** 对外只这一套：普通会员、白银会员、黄金会员、星钻会员。 */
    public static function tierLabel(string $code): string
    {
        return [
            'silver' => '白银会员',
            'gold' => '黄金会员',
            'diamond' => '星钻会员',
        ][$code] ?? '普通会员';
    }

    /**
     * 会员只有这一套。已开通且未过期的白银/黄金/星钻优先；
     * 没写档位、但有效期还在的，用等级 1/2/3。没开通也没有效期的是普通会员。
     */
    public static function tierCode($user): string
    {
        if (!$user) {
            return 'free';
        }
        $code = (string) ($user->vip_grade_code ?? '');
        $exp = $user->vip_expire_at ?? null;
        $expired = $exp && strtotime((string) $exp) < time();
        if (!$expired && in_array($code, ['silver', 'gold', 'diamond'], true)) {
            return $code;
        }
        if ($exp && !$expired) {
            $level = (int) ($user->level ?? 0);
            if ($level >= 3) {
                return 'diamond';
            }
            if ($level === 2) {
                return 'gold';
            }
            if ($level === 1) {
                return 'silver';
            }
        }

        return 'free';
    }

    /** 列表上的会员标识，和默认排序用的会员等级一致。 */
    public static function memberMark($user): array
    {
        $empty = ['member_level' => 0, 'member_badge' => '', 'member_grade' => ''];
        $code = self::tierCode($user);
        $byCode = [
            'silver' => [1, '白银会员'],
            'gold' => [2, '黄金会员'],
            'diamond' => [3, '星钻会员'],
        ];
        if (!isset($byCode[$code])) {
            return $empty;
        }

        return [
            'member_level' => $byCode[$code][0],
            'member_badge' => $byCode[$code][1],
            'member_grade' => $code,
        ];
    }

    public static function isPaidActive(User $user): bool
    {
        return self::tierCode($user) !== 'free';
    }

    public static function resolveGrade(?User $user): array
    {
        $free = self::freeLimits();
        if (!$user) {
            return $free;
        }

        $code = self::tierCode($user);

        $grade = VipGrade::where('code', $code)->where('is_active', 1)->find();
        if (!$grade && $code !== 'free') {
            $grade = VipGrade::where('code', 'free')->where('is_active', 1)->find();
            $code = 'free';
        }
        if (!$grade) {
            return $free;
        }

        return self::normalizeGradeRow($grade);
    }

    public static function normalizeGradeRow($grade): array
    {
        $limits = $grade->limits ?? null;
        if (is_string($limits)) {
            $limits = json_decode($limits, true) ?: [];
        }
        if (!is_array($limits)) {
            $limits = [];
        }
        $perks = $grade->perks ?? [];
        if (is_string($perks)) {
            $perks = json_decode($perks, true) ?: [];
        }
        if (!is_array($perks)) {
            $perks = [];
        }

        return array_merge(self::freeLimits(), $limits, [
            'code' => (string) $grade->code,
            'name' => self::tierLabel((string) $grade->code),
            'level' => (int) $grade->level,
            'price_year' => (float) $grade->price_year,
            'badge' => (string) ($limits['badge'] ?? ''),
            'contact_quota_month' => (int) ($limits['contact_quota_month'] ?? 0),
            'quote_history_days' => (int) ($limits['quote_history_days'] ?? 7),
            'exposure_weight' => (int) ($limits['exposure_weight'] ?? 0),
            'top_price' => (int) ($limits['top_price'] ?? 0),
            'seat_count' => (int) ($limits['seat_count'] ?? 1),
            'resume_contact_quota_month' => (int) ($limits['resume_contact_quota_month'] ?? 1),
            'job_sticky_per_month' => (int) ($limits['job_sticky_per_month'] ?? 0),
            'jobs_online_limit' => array_key_exists('jobs_online_limit', $limits) ? (int) $limits['jobs_online_limit'] : 5,
            'matrix' => is_array($limits['matrix'] ?? null) ? $limits['matrix'] : [],
            'perks' => $perks,
            'id' => (int) $grade->id,
        ]);
    }

    public static function quoteHistoryDays(?User $user): int
    {
        return max(1, (int) (self::resolveGrade($user)['quote_history_days'] ?? 7));
    }

    public static function exposureWeight(?User $user): int
    {
        return (int) (self::resolveGrade($user)['exposure_weight'] ?? 0);
    }

    public static function monthKey(?string $at = null): string
    {
        return date('Y-m', $at ? strtotime($at) : time());
    }

    public static function maskMobile(?string $mobile): ?string
    {
        $m = preg_replace('/\s+/', '', (string) $mobile);
        if ($m === '') {
            return null;
        }
        $len = strlen($m);
        if ($len < 7) {
            return str_repeat('*', $len);
        }

        return substr($m, 0, 3) . str_repeat('*', max(4, $len - 7)) . substr($m, -4);
    }

    public static function entitlementSummary(?User $user): array
    {
        $grade = self::resolveGrade($user);
        $companyId = $user ? self::companyRootId($user) : 0;
        $month = self::monthKey();
        $used = 0;
        if ($companyId) {
            $row = VipQuotaUsage::where('company_user_id', $companyId)
                ->where('quota_type', 'contact')
                ->where('month_key', $month)
                ->find();
            $used = $row ? (int) $row->used : 0;
        }
        $bonusLeft = 0;
        $bonusExpire = null;
        if ($user) {
            $bonusExpire = $user->contact_bonus_expire ?? null;
            if ($bonusExpire && strtotime((string) $bonusExpire) >= time()) {
                $bonusLeft = max(0, (int) ($user->contact_bonus_left ?? 0));
            }
        }
        $quota = (int) $grade['contact_quota_month'];
        $remainMonth = max(0, $quota - $used);

        $resumeQuota = (int) ($grade['resume_contact_quota_month'] ?? 0);
        $resumeUsed = 0;
        if ($companyId) {
            $rRow = VipQuotaUsage::where('company_user_id', $companyId)
                ->where('quota_type', 'resume_contact')
                ->where('month_key', $month)
                ->find();
            $resumeUsed = $rRow ? (int) $rRow->used : 0;
        }
        $resumeRemainMonth = max(0, $resumeQuota - $resumeUsed);
        $jobsOnlineLimit = array_key_exists('jobs_online_limit', $grade) ? (int) $grade['jobs_online_limit'] : 5;
        $jobsOnlineUsed = $user ? self::countOnlineJobs($user) : 0;
        $stickyLimit = (int) ($grade['job_sticky_per_month'] ?? 0);
        $stickyUsed = 0;
        if ($user) {
            $usedMonth = (string) ($user->job_sticky_used_month ?? '');
            if ($usedMonth === $month) {
                $stickyUsed = (int) ($user->job_sticky_used_count ?? 0);
            }
        }

        return [
            'grade_code' => $grade['code'],
            'grade_name' => self::tierLabel((string) $grade['code']),
            'badge' => $grade['badge'],
            'level' => $grade['level'],
            'is_paid' => $user ? self::isPaidActive($user) : false,
            'vip_expire_at' => $user->vip_expire_at ?? null,
            'quote_history_days' => (int) $grade['quote_history_days'],
            'exposure_weight' => (int) $grade['exposure_weight'],
            'top_price' => (int) ($grade['top_price'] ?? 0),
            'contact_quota_month' => $quota,
            'contact_used_month' => $used,
            'contact_remain_month' => $remainMonth,
            'contact_bonus_left' => $bonusLeft,
            'contact_bonus_expire' => $bonusExpire,
            'contact_remain_total' => $remainMonth + $bonusLeft,
            'seat_count' => (int) $grade['seat_count'],
            'resume_contact_quota_month' => $resumeQuota,
            'resume_contact_used_month' => $resumeUsed,
            'resume_contact_remain_month' => $resumeRemainMonth,
            'resume_contact_remain_total' => $resumeRemainMonth + $bonusLeft,
            'resume_full_visible' => $user ? self::resumeFullVisible($user) : false,
            'job_sticky_per_month' => $stickyLimit,
            'job_sticky_used_month' => $stickyUsed,
            'job_sticky_remain_month' => max(0, $stickyLimit - $stickyUsed),
            'jobs_online_limit' => $jobsOnlineLimit,
            'jobs_online_used' => $jobsOnlineUsed,
            'jobs_online_remain' => $jobsOnlineLimit <= 0 ? null : max(0, $jobsOnlineLimit - $jobsOnlineUsed),
            'pack_discount' => (float) ($grade['pack_discount'] ?? 1),
            'job_module_visible' => !empty($grade['job_module_visible']),
            'industry_report' => !empty(($grade['matrix'] ?? [])['industry_report']),
            'invite_code' => $user ? self::ensureInviteCode($user) : null,
            'matrix' => $grade['matrix'],
        ];
    }

    /**
     * Grant cert bonus once when enterprise gets certified (idempotent if already has bonus expire in future with left>0 from previous grant — caller controls).
     */
    public static function grantCertBonus(User $user): void
    {
        $free = VipGrade::where('code', 'free')->find();
        $limits = [];
        if ($free) {
            $limits = is_array($free->limits) ? $free->limits : (json_decode((string) $free->limits, true) ?: []);
        }
        $n = (int) ($limits['cert_bonus_contacts'] ?? 5);
        $days = (int) ($limits['cert_bonus_days'] ?? 30);
        $user->save([
            'contact_bonus_left' => $n,
            'contact_bonus_expire' => date('Y-m-d H:i:s', strtotime("+{$days} days")),
        ]);
    }

    /**
     * @return array{ok:bool,code?:string,message?:string,contact?:array,already?:bool,entitlement?:array}
     */
    public static function unlockContact(User $user, string $targetType, int $targetId): array
    {
        $allowed = ['purchase', 'job', 'resume'];
        if (!in_array($targetType, $allowed, true)) {
            $targetType = 'purchase';
        }
        $companyId = self::companyRootId($user);
        $month = self::monthKey();
        $quotaType = $targetType === 'resume' || $targetType === 'job' ? 'resume_contact' : 'contact';

        $existing = VipContactUnlock::where('company_user_id', $companyId)
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->find();
        if ($existing) {
            $payload = $existing->contact_payload;
            if (is_string($payload)) {
                $payload = json_decode($payload, true) ?: [];
            }

            return [
                'ok' => true,
                'already' => true,
                'contact' => is_array($payload) ? $payload : [],
                'entitlement' => self::entitlementSummary($user),
            ];
        }

        $contact = self::resolveContactPayload($targetType, $targetId);
        if ($contact === null) {
            return ['ok' => false, 'code' => 'not_found', 'message' => '目标不存在'];
        }

        if (!empty($contact['owner_user_id']) && (
            (int) $contact['owner_user_id'] === (int) $user->id
            || (int) $contact['owner_user_id'] === $companyId
        )) {
            self::saveUnlock($companyId, $user->id, $targetType, $targetId, $month, $contact, false);

            return [
                'ok' => true,
                'already' => false,
                'contact' => self::publicContact($contact),
                'entitlement' => self::entitlementSummary($user),
            ];
        }

        $summary = self::entitlementSummary($user);
        $grade = self::resolveGrade($user);

        if ($quotaType === 'resume_contact') {
            $quota = (int) ($grade['resume_contact_quota_month'] ?? 0);
            $usage = VipQuotaUsage::where('company_user_id', $companyId)
                ->where('quota_type', 'resume_contact')
                ->where('month_key', $month)
                ->find();
            $used = $usage ? (int) $usage->used : 0;
            $remain = max(0, $quota - $used) + (int) $summary['contact_bonus_left'];
            if ($remain <= 0) {
                return [
                    'ok' => false,
                    'code' => 'quota_exceeded',
                    'message' => '本月简历/招聘联系方式查看次数已用完',
                    'entitlement' => $summary,
                ];
            }
        } else {
            $remain = (int) $summary['contact_remain_total'];
            if ($remain <= 0) {
                return [
                    'ok' => false,
                    'code' => 'quota_exceeded',
                    'message' => '本月联系方式查看次数已用完，请升级会员、购买次数包或下月再试',
                    'entitlement' => $summary,
                ];
            }
        }

        Db::startTrans();
        try {
            $lockedUser = User::where('id', $user->id)->lock(true)->find();
            if (!$lockedUser) {
                throw new \RuntimeException('user missing');
            }
            $bonusLeft = (int) ($lockedUser->contact_bonus_left ?? 0);
            if ($bonusLeft > 0) {
                $lockedUser->save(['contact_bonus_left' => max(0, $bonusLeft - 1)]);
            } else {
                $usage = VipQuotaUsage::where('company_user_id', $companyId)
                    ->where('quota_type', $quotaType)
                    ->where('month_key', $month)
                    ->lock(true)
                    ->find();
                if (!$usage) {
                    $usage = VipQuotaUsage::create([
                        'company_user_id' => $companyId,
                        'quota_type' => $quotaType,
                        'month_key' => $month,
                        'used' => 0,
                    ]);
                    $usage = VipQuotaUsage::where('id', $usage->id)->lock(true)->find() ?: $usage;
                }
                $usedNow = (int) $usage->used;
                $quota = $quotaType === 'resume_contact'
                    ? (int) ($grade['resume_contact_quota_month'] ?? 0)
                    : (int) ($grade['contact_quota_month'] ?? 0);
                // 事务内重算，避免并发超配额
                if ($quota > 0 && $usedNow >= $quota) {
                    Db::rollback();

                    return [
                        'ok' => false,
                        'code' => 'quota_exceeded',
                        'message' => $quotaType === 'resume_contact'
                            ? '本月简历/招聘联系方式查看次数已用完'
                            : '本月联系方式查看次数已用完，请升级会员、购买次数包或下月再试',
                        'entitlement' => self::entitlementSummary($lockedUser),
                    ];
                }
                $usage->save(['used' => $usedNow + 1]);
            }
            self::saveUnlock($companyId, $lockedUser->id, $targetType, $targetId, $month, $contact, true);
            Db::commit();
            $user = $lockedUser;
        } catch (\Throwable $e) {
            Db::rollback();

            return ['ok' => false, 'code' => 'error', 'message' => '解锁失败，请稍后重试'];
        }

        $user = User::find($user->id) ?: $user;

        return [
            'ok' => true,
            'already' => false,
            'contact' => self::publicContact($contact),
            'entitlement' => self::entitlementSummary($user),
        ];
    }

    public static function hasUnlocked(User $user, string $targetType, int $targetId): bool
    {
        return (bool) VipContactUnlock::where('company_user_id', self::companyRootId($user))
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->find();
    }

    /** 研究报告单份售价（余额支付）。 */
    public static function reportPrice(): float
    {
        return 19.8;
    }

    /** 标题是否属于「研究报告」栏目（避开「本周报价」误伤）。 */
    public static function isResearchReport(string $title, ?string $type = null): bool
    {
        $t = (string) $title;
        if ($t === '') {
            return false;
        }
        foreach (['研究报告', '行业报告', '深度报告', '研报', '专刊', '月报', '简析', '展望'] as $kw) {
            if (mb_strpos($t, $kw) !== false) {
                return true;
            }
        }
        // 「周报」：排除「本周报价」这类无「简析」的行情短讯
        if (preg_match('/(?<!本)周报(?!价)/u', $t)) {
            return true;
        }

        return false;
    }

    /** 黄金 / 星钻矩阵里的行业报告权益。 */
    public static function canReadIndustryReport(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        $grade = self::resolveGrade($user);
        $matrix = is_array($grade['matrix'] ?? null) ? $grade['matrix'] : [];

        return !empty($matrix['industry_report']);
    }

    /** 当前用户对某份研究报告是否可读全文。 */
    public static function canAccessReport(?User $user, int $articleId): bool
    {
        if (!$user) {
            return false;
        }
        if (self::canReadIndustryReport($user)) {
            return true;
        }

        return self::hasUnlocked($user, 'report', $articleId);
    }

    /**
     * 非会员、未购买的普通/白银只留导读以上；没有导读则只留摘要或第一段。
     */
    public static function reportPreviewHtml(string $html, ?string $summary = null): string
    {
        $html = trim($html);
        if ($html === '') {
            return $summary ? '<p>'.htmlspecialchars((string) $summary, ENT_QUOTES, 'UTF-8').'</p>' : '';
        }
        if (preg_match('/导\s*读/u', $html, $m, PREG_OFFSET_CAPTURE)) {
            $before = substr($html, 0, (int) $m[0][1]);
            if (preg_match('/<(p|div|h[1-6]|section|article|li|blockquote)\b[^>]*>\s*$/i', $before, $open, PREG_OFFSET_CAPTURE)) {
                $before = substr($before, 0, (int) $open[0][1]);
            }
            $before = trim($before);
            if ($before !== '') {
                return $before;
            }
            if ($summary && trim((string) $summary) !== '') {
                return '<p>'.htmlspecialchars(trim((string) $summary), ENT_QUOTES, 'UTF-8').'</p>';
            }

            return '';
        }
        if ($summary && trim((string) $summary) !== '') {
            return '<p>'.htmlspecialchars(trim((string) $summary), ENT_QUOTES, 'UTF-8').'</p>';
        }
        if (preg_match('/<(p|div)(\s[^>]*)?>.*?<\/\1>/is', $html, $part)) {
            return $part[0];
        }
        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($html)) ?? '');
        if ($plain === '') {
            return '';
        }
        if (mb_strlen($plain) > 160) {
            $plain = mb_substr($plain, 0, 160).'…';
        }

        return '<p>'.htmlspecialchars($plain, ENT_QUOTES, 'UTF-8').'</p>';
    }

    /**
     * 校验研究报告是否需要购买。已可读时 already=true。
     *
     * @return array{ok:bool,code?:string,message?:string,already?:bool,price?:float,title?:string}
     */
    public static function prepareReportPurchase(User $user, int $articleId): array
    {
        if ($articleId <= 0) {
            return ['ok' => false, 'code' => 'bad_id', 'message' => '参数错误'];
        }
        $article = \app\model\Article::publishedQuery()->find($articleId);
        if (!$article) {
            return ['ok' => false, 'code' => 'not_found', 'message' => '报告不存在或已下架'];
        }
        if (!self::isResearchReport((string) $article->title, (string) ($article->type ?? ''))) {
            return ['ok' => false, 'code' => 'not_report', 'message' => '该资讯不是研究报告'];
        }
        if (self::canReadIndustryReport($user) || self::hasUnlocked($user, 'report', $articleId)) {
            return ['ok' => true, 'already' => true, 'message' => '您已可阅读本报告', 'price' => 0.0, 'title' => (string) $article->title];
        }

        return [
            'ok' => true,
            'already' => false,
            'price' => self::reportPrice(),
            'title' => (string) $article->title,
        ];
    }

    /** 对公凭证审核通过后开通全文，不扣余额。失败返回原因。 */
    public static function grantPurchasedReport(User $user, int $articleId): ?string
    {
        $article = \app\model\Article::publishedQuery()->find($articleId);
        if (!$article || !self::isResearchReport((string) $article->title, (string) ($article->type ?? ''))) {
            return '报告不存在或已下架';
        }
        if (self::canReadIndustryReport($user) || self::hasUnlocked($user, 'report', $articleId)) {
            return null;
        }
        self::saveUnlock(
            self::companyRootId($user),
            (int) $user->id,
            'report',
            $articleId,
            self::monthKey(),
            ['name' => (string) $article->title, 'company_name' => null, 'mobile' => null, 'qq' => null, 'phone' => null, 'email' => null],
            true
        );

        return null;
    }

    /** @return array<string,mixed>|null */
    private static function resolveContactPayload(string $targetType, int $targetId): ?array
    {
        if ($targetType === 'purchase') {
            $p = Purchase::with(['user'])->find($targetId);
            if (!$p) {
                return null;
            }
            $u = $p->user;

            return [
                'owner_user_id' => $u ? (int) $u->id : null,
                'name' => $u->name ?? null,
                'company_name' => $u->company_name ?? null,
                'mobile' => $u->mobile ?? null,
                'qq' => $u->qq ?? null,
                'phone' => $u->mobile ?? null,
            ];
        }
        if ($targetType === 'job') {
            $job = \app\model\JobPost::find($targetId);
            if (!$job) {
                return null;
            }

            return [
                'owner_user_id' => (int) ($job->user_id ?? 0),
                'name' => $job->contact_name ?? null,
                'company_name' => $job->company_name ?? null,
                'mobile' => $job->contact_phone ?? null,
                'phone' => $job->contact_phone ?? null,
                'qq' => null,
            ];
        }
        if ($targetType === 'resume') {
            $r = \app\model\Resume::find($targetId);
            if (!$r) {
                return null;
            }

            return [
                'owner_user_id' => (int) ($r->user_id ?? 0),
                'name' => $r->truename ?? $r->name ?? null,
                'company_name' => null,
                'mobile' => $r->mobile ?? null,
                'phone' => $r->mobile ?? null,
                'qq' => null,
                'email' => $r->email ?? null,
            ];
        }

        return null;
    }

    /** @param array<string,mixed> $contact */
    private static function publicContact(array $contact): array
    {
        return [
            'name' => $contact['name'] ?? null,
            'company_name' => $contact['company_name'] ?? null,
            'mobile' => $contact['mobile'] ?? null,
            'qq' => $contact['qq'] ?? null,
            'phone' => $contact['phone'] ?? ($contact['mobile'] ?? null),
            'email' => $contact['email'] ?? null,
        ];
    }

    /** @param array<string,mixed> $contact */
    private static function saveUnlock(int $companyId, int $by, string $type, int $id, string $month, array $contact, bool $_counted): void
    {
        VipContactUnlock::create([
            'company_user_id' => $companyId,
            'target_type' => $type,
            'target_id' => $id,
            'unlocked_by' => $by,
            'month_key' => $month,
            'contact_payload' => self::publicContact($contact),
        ]);
    }
}
