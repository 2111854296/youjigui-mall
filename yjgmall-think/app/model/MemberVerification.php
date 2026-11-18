<?php

namespace app\model;

class MemberVerification extends BaseModel
{
    protected $name = 'member_verifications';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
