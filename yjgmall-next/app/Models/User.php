<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'username', 'email', 'mobile', 'password', 'pay_password', 'company_name',
        'member_type', 'gender', 'qq', 'area', 'province', 'city', 'district', 'company_type',
        'level', 'vip_expire_at', 'is_certified', 'status', 'group_code', 'is_admin',
        'points', 'credit', 'balance', 'deposit', 'avatar', 'sound', 'online_status',
        'mobile_verified', 'bank_verified', 'truename_verified',
        'truename', 'bank_name', 'bank_account', 'oauth_qq', 'oauth_weibo',
    ];

    protected $hidden = ['password', 'pay_password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_certified' => 'boolean',
            'mobile_verified' => 'boolean',
            'bank_verified' => 'boolean',
            'truename_verified' => 'boolean',
            'vip_expire_at' => 'datetime',
            'balance' => 'decimal:2',
            'deposit' => 'decimal:2',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_admin;
    }

    public function isVip(): bool
    {
        if ((int) $this->level < 1) {
            return false;
        }
        if ($this->vip_expire_at && $this->vip_expire_at->isPast()) {
            return false;
        }

        return true;
    }

    public function isBanned(): bool
    {
        return ($this->group_code ?? '') === 'banned' || $this->status === 'banned';
    }

    /** 企业侧能力：发供应/商品、旺铺、卖出订单等 */
    public function isEnterpriseMember(): bool
    {
        if (($this->member_type ?? '') === 'company') {
            return true;
        }

        return in_array($this->group_code ?? '', ['enterprise', 'vip'], true);
    }

    public function isPersonalMember(): bool
    {
        return ! $this->isEnterpriseMember();
    }

    public function ensureEnterprise(): void
    {
        abort_unless($this->isEnterpriseMember(), 403, '该功能仅对企业会员开放，请先升级为企业会员');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    public function company(): HasOne
    {
        return $this->hasOne(Company::class);
    }

    public function supplies(): HasMany
    {
        return $this->hasMany(Supply::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }
}
