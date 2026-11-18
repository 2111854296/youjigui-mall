<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdPlace extends Model
{
    protected $fillable = ['code', 'name', 'type', 'size', 'price_note', 'sort', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
