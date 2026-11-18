<?php

namespace app\model;

class Message extends BaseModel
{
    protected $name = 'messages';

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
