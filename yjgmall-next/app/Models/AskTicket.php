<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AskTicket extends Model
{
    protected $fillable = [
        'user_id', 'title', 'content', 'contact_name', 'contact_phone', 'status', 'reply',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
