<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VipGrade extends Model
{
    protected $fillable = ['code', 'name', 'price_year', 'level', 'perks', 'sort', 'is_active'];

    protected function casts(): array
    {
        return [
            'price_year' => 'decimal:2',
            'is_active' => 'boolean',
            'perks' => 'array',
        ];
    }

    public function applications(): HasMany
    {
        return $this->hasMany(VipApplication::class);
    }
}
