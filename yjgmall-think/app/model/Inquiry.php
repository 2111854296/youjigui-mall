<?php

namespace app\model;

class Inquiry extends BaseModel
{
    protected $name = 'inquiries';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
