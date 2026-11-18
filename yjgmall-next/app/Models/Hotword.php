<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hotword extends Model
{
    protected $fillable = ['word', 'module', 'sort', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
