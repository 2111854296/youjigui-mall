<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuoteProduct extends Model
{
    protected $fillable = [
        'name', 'unit', 'price_min', 'price_max', 'price_avg',
        'market', 'quoted_on', 'sort', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_min' => 'decimal:2',
            'price_max' => 'decimal:2',
            'price_avg' => 'decimal:2',
            'quoted_on' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
