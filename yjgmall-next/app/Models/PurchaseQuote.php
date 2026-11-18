<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseQuote extends Model
{
    protected $fillable = [
        'purchase_id', 'user_id', 'price', 'unit', 'quantity',
        'content', 'contact_name', 'contact_phone', 'status',
    ];

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
