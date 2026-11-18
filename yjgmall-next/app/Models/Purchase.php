<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    protected $fillable = [
        'user_id', 'category_id', 'title', 'thumb', 'budget', 'quantity', 'unit', 'area', 'days',
        'summary', 'content', 'status', 'is_urgent', 'level', 'views', 'refreshed_at',
    ];

    protected function casts(): array
    {
        return [
            'budget' => 'decimal:2',
            'is_urgent' => 'boolean',
            'refreshed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function quotes()
    {
        return $this->hasMany(PurchaseQuote::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'approved');
    }
}
