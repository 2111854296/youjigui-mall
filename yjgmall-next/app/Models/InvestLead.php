<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestLead extends Model
{
    protected $fillable = [
        'invest_project_id', 'user_id', 'company_name', 'contact_name',
        'contact_phone', 'content', 'status',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(InvestProject::class, 'invest_project_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
