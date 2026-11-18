<?php

namespace app\model;

class Resume extends BaseModel
{
    protected $name = 'resumes';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
