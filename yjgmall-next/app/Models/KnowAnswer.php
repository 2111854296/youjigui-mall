<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowAnswer extends Model
{
    protected $fillable = ['know_question_id', 'user_id', 'content', 'is_best', 'status'];

    protected function casts(): array
    {
        return ['is_best' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
