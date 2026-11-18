<?php

namespace app\model;

use think\Model;

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

    public function isVip(): bool
    {
        if ((int) $this->level < 1) {
            return false;
        }
        if (empty($this->vip_expire_at)) {
            return (int) $this->level >= 1;
        }

        return strtotime((string) $this->vip_expire_at) >= time();
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
            'name'               => $this->name,
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
            'is_vip'             => $this->isVip(),
            'vip_expire_at'      => $this->vip_expire_at,
            'is_certified'       => (bool) $this->is_certified,
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
}