<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdBooking extends Model
{
    protected $fillable = [
        'user_id', 'place_code', 'title', 'image', 'contact_name', 'contact_phone', 'remark', 'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
