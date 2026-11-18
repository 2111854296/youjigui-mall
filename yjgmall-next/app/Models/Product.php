<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'user_id', 'category_id', 'title', 'thumb', 'price', 'stock', 'unit',
        'summary', 'content', 'status', 'sales', 'views',
    ];

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
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
