<?php

namespace app\model;

use think\Model;
use think\model\contract\Modelable;

class User extends Model
{
    protected $name = 'users';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    protected $autoWriteTimestamp = 'datetime';
    protected $hidden = ['password', 'remember_token', 'pay_password'];

    public function isBanned(): bool
    {
        return ($this->status ?? '') === 'banned' || ($this->group_code ?? '') === 'banned';
    }

    public function isEnterpriseMember(): bool
    {
        $type = $this->member_type ?: 'personal';
        $group = $this->group_code ?: 'personal';

        return $type === 'company' || in_array($group, ['enterprise', 'company', 'vip'], true);
    }

    public function isPersonalMember(): bool
    {
        return !$this->isEnterpriseMember();
    }

    /** 已通过审核的企业旺铺，或已认证的企业会员，才能看人才库。 */
    public function canBrowseTalents(): bool
    {
        if ($this->is_admin) {
            return true;
        }
        if ($this->isEnterpriseMember() && $this->is_certified) {
            return true;
        }
        try {
            return (bool) \app\model\Company::where('user_id', $this->id)
                ->where('status', 'approved')
                ->find();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** 是否付费 VIP（与权益档位一致，不含免费企业） */
    public function isVip(): bool
    {
        return \app\common\VipEntitlement::isPaidActive($this);
    }

    /**
     * 新注册用户的主键用 6 位随机数。已有账号保持原 ID。
     * 先查重，主键冲突再换一个，避免并发下撞号。
     */
    public static function create(array|object $data, array $allowField = [], bool $replace = false, string $suffix = ''): Modelable
    {
        if (is_object($data)) {
            $data = (array) $data;
        }
        $given = (int) ($data['id'] ?? 0);
        if ($given > 0) {
            return parent::create($data, $allowField, $replace, $suffix);
        }

        $last = null;
        for ($i = 0; $i < 12; $i++) {
            $id = static::allocPublicId();
            $data['id'] = $id;
            try {
                parent::create($data, $allowField, $replace, $suffix);
            } catch (\Throwable $e) {
                $last = $e;
                if (!static::isDuplicatePrimary($e)) {
                    throw $e;
                }
                continue;
            }
            $saved = static::find($id);
            if ($saved) {
                return $saved;
            }
            $last = new \RuntimeException('用户ID写入失败');
        }
        throw $last ?: new \RuntimeException('用户ID分配失败');
    }

    public static function allocPublicId(): int
    {
        for ($i = 0; $i < 30; $i++) {
            $id = random_int(100000, 999999);
            if (!static::where('id', $id)->value('id')) {
                return $id;
            }
        }
        throw new \RuntimeException('用户ID分配失败');
    }

    private static function isDuplicatePrimary(\Throwable $e): bool
    {
        $msg = $e->getMessage();
        if ((str_contains($msg, '1062') || stripos($msg, 'Duplicate') !== false)
            && stripos($msg, 'PRIMARY') !== false) {
            return true;
        }
        $prev = $e->getPrevious();
        return $prev ? static::isDuplicatePrimary($prev) : false;
    }

    public function createToken(string $name = 'web'): string
    {
        $plain = bin2hex(random_bytes(20));
        $row = PersonalAccessToken::create([
            'tokenable_type' => 'App\\Models\\User',
            'tokenable_id'   => $this->id,
            'name'           => $name,
            'token'          => hash('sha256', $plain),
            'abilities'      => json_encode(['*']),
        ]);

        return $row->id.'|'.$plain;
    }

    public function toApiArray(): array
    {
        $group = $this->group_code ?: 'personal';
        $labels = [
            'pending'    => '待审核',
            'personal'   => '个人会员',
            'enterprise' => '企业会员',
            'company'    => '企业会员',
            'vip'        => 'VIP会员',
            'banned'     => '已禁用',
            'admin'      => '管理员',
        ];

        return [
            'id'                 => (int) $this->id,
            'name'               => (string) $this->getAttr('name'),
            'username'           => $this->username,
            'member_type'        => $this->member_type ?: 'personal',
            'is_enterprise'      => $this->isEnterpriseMember(),
            'is_personal'        => $this->isPersonalMember(),
            'capabilities'       => [
                'shop'          => $this->isEnterpriseMember(),
                'supply'        => $this->isEnterpriseMember(),
                'product'       => $this->isEnterpriseMember(),
                'seller_orders' => $this->isEnterpriseMember(),
                'purchase'      => true,
                'article'       => $this->isEnterpriseMember(),
                'ads'           => $this->isEnterpriseMember(),
                'spread'        => $this->isEnterpriseMember(),
            ],
            'group_code'         => $group,
            'group_label'        => $labels[$group] ?? '个人会员',
            'gender'             => $this->gender,
            'email'              => $this->email,
            'mobile'             => $this->mobile,
            'qq'                 => $this->qq,
            'area'               => $this->area,
            'province'           => $this->province,
            'city'               => $this->city,
            'district'           => $this->district,
            'company_name'       => $this->company_name,
            'company_type'       => $this->company_type,
            'level'              => (int) $this->level,
            'vip_grade_code'     => $this->vip_grade_code ?: null,
            'is_vip'             => $this->isVip(),
            'vip_expire_at'      => $this->vip_expire_at,
            'vip_entitlement'    => \app\common\VipEntitlement::entitlementSummary($this),
            'is_certified'       => (bool) $this->is_certified,
            'company_certified'  => $this->canBrowseTalents(),
            'is_admin'           => (bool) $this->is_admin,
            'status'             => $this->status,
            'points'             => (int) $this->points,
            'credit'             => (int) ($this->credit ?? 0),
            'balance'            => (string) $this->balance,
            'deposit'            => (string) ($this->deposit ?? 0),
            'avatar'             => $this->avatar,
            'sound'              => (int) ($this->sound ?? 1),
            'online_status'      => $this->online_status ?: 'online',
            'mobile_verified'    => (bool) $this->mobile_verified,
            'bank_verified'      => (bool) $this->bank_verified,
            'truename_verified'  => (bool) $this->truename_verified,
            'truename'           => $this->truename,
            'bank_name'          => $this->bank_name,
            'bank_account'       => $this->bank_account,
            'has_pay_password'   => !is_null($this->pay_password) && $this->pay_password !== '',
            'oauth_qq'           => (bool) $this->oauth_qq,
            'oauth_weibo'        => (bool) $this->oauth_weibo,
        ];
    }

    /** 简历被收藏：只计上次打开「我的简历」之后的新增。 */
    public function unseenResumeFavoredCount(): int
    {
        try {
            $ids = \app\model\Resume::where('user_id', $this->id)->column('id');
            if (!$ids) {
                return 0;
            }
            $q = \app\model\Favorite::where('favoritable_type', 'resume')->whereIn('favoritable_id', $ids);
            $seen = (int) ($this->fav_seen_id ?? 0);
            if ($seen > 0) {
                $q->where('id', '>', $seen);
            }
            return (int) $q->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** 平台公告：只计上次打开公告页之后的新增。 */
    public function unseenAnnounceCount(): int
    {
        try {
            $q = \app\model\Announcement::where('is_active', 1);
            $seen = (int) ($this->announce_seen_id ?? 0);
            if ($seen > 0) {
                $q->where('id', '>', $seen);
            }
            return (int) $q->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function markNoticeSeen(string $type): void
    {
        if ($type === 'chat') {
            \app\model\ChatMessage::where('to_user_id', $this->id)->where('is_read', 0)->update(['is_read' => 1]);
            return;
        }
        if ($type === 'favored') {
            $ids = \app\model\Resume::where('user_id', $this->id)->column('id');
            $max = 0;
            if ($ids) {
                $max = (int) \app\model\Favorite::where('favoritable_type', 'resume')->whereIn('favoritable_id', $ids)->max('id');
            }
            $this->fav_seen_id = $max;
            $this->save();
            return;
        }
        if ($type === 'announce') {
            $this->announce_seen_id = (int) \app\model\Announcement::where('is_active', 1)->max('id');
            $this->save();
        }
    }
}