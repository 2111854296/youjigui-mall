<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VipApplication extends Model
{
    protected $fillable = [
        'user_id', 'vip_grade_id', 'company_name', 'contact_name',
        'phone', 'mobile', 'email', 'qq', 'remark', 'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(VipGrade::class, 'vip_grade_id');
    }
}
