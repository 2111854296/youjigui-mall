<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Supply extends Model
{
    protected $fillable = [
        'user_id', 'category_id', 'title', 'thumb', 'thumb1', 'thumb2',
        'price', 'unit', 'min_amount', 'amount', 'days', 'brand', 'area',
        'attr1_name', 'attr1_value', 'attr2_name', 'attr2_value', 'attr3_name', 'attr3_value',
        'summary', 'content', 'status', 'is_urgent', 'is_elite', 'level',
        'views', 'refreshed_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'min_amount' => 'decimal:2',
            'is_urgent' => 'boolean',
            'is_elite' => 'boolean',
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

    public function scopePublished($query)
    {
        return $query->where('status', 'approved');
    }
}
