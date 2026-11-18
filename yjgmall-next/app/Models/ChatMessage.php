<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $fillable = ['chat_session_id', 'from_user_id', 'to_user_id', 'content', 'is_read'];

    protected function casts(): array
    {
        return ['is_read' => 'boolean'];
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }
}
