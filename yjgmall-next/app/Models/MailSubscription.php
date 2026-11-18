<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailSubscription extends Model
{
    protected $fillable = ['user_id', 'email', 'topics', 'is_active'];

    protected function casts(): array
    {
        return [
            'topics' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
