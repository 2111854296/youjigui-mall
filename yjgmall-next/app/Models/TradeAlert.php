<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradeAlert extends Model
{
    protected $fillable = [
        'user_id', 'module', 'keyword', 'area', 'price_min', 'price_max', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'price_min' => 'decimal:2',
            'price_max' => 'decimal:2',
        ];
    }
}
