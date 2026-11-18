<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Spread extends Model
{
    protected $fillable = [
        'user_id', 'module', 'keyword', 'bid', 'days', 'status', 'starts_at', 'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'bid' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
