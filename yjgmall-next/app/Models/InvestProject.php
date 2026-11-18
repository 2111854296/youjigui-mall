<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvestProject extends Model
{
    protected $fillable = [
        'title', 'area', 'amount', 'summary', 'content',
        'contact_name', 'contact_phone', 'sort', 'status', 'views',
    ];

    public function leads(): HasMany
    {
        return $this->hasMany(InvestLead::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
