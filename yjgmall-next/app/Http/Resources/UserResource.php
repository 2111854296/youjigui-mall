<?php

namespace App\Http\Resources;

use App\Http\Controllers\Api\V1\MemberParityController;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $group = $this->group_code ?: 'personal';

        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'member_type' => $this->member_type ?: 'personal',
            'is_enterprise' => $this->isEnterpriseMember(),
            'is_personal' => $this->isPersonalMember(),
            'capabilities' => [
                'shop' => $this->isEnterpriseMember(),
                'supply' => $this->isEnterpriseMember(),
                'product' => $this->isEnterpriseMember(),
                'seller_orders' => $this->isEnterpriseMember(),
                'purchase' => true,
                'article' => $this->isEnterpriseMember(),
                'ads' => $this->isEnterpriseMember(),
                'spread' => $this->isEnterpriseMember(),
            ],
            'group_code' => $group,
            'group_label' => MemberParityController::groupLabels()[$group] ?? '个人会员',
            'gender' => $this->gender,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'qq' => $this->qq,
            'area' => $this->area,
            'province' => $this->province,
            'city' => $this->city,
            'district' => $this->district,
            'company_name' => $this->company_name,
            'company_type' => $this->company_type,
            'level' => (int) $this->level,
            'is_vip' => $this->isVip(),
            'vip_expire_at' => optional($this->vip_expire_at)?->toDateTimeString(),
            'is_certified' => (bool) $this->is_certified,
            'is_admin' => (bool) $this->is_admin,
            'status' => $this->status,
            'points' => (int) $this->points,
            'credit' => (int) ($this->credit ?? 0),
            'balance' => (string) $this->balance,
            'deposit' => (string) ($this->deposit ?? 0),
            'avatar' => $this->avatar,
            'sound' => (int) ($this->sound ?? 1),
            'online_status' => $this->online_status ?: 'online',
            'mobile_verified' => (bool) $this->mobile_verified,
            'bank_verified' => (bool) $this->bank_verified,
            'truename_verified' => (bool) $this->truename_verified,
            'truename' => $this->truename,
            'bank_name' => $this->bank_name,
            'bank_account' => $this->bank_account,
            'has_pay_password' => filled($this->pay_password),
            'oauth_qq' => (bool) $this->oauth_qq,
            'oauth_weibo' => (bool) $this->oauth_weibo,
        ];
    }
}
