<?php

namespace app\model;

class JobPost extends BaseModel
{
    protected $name = 'job_posts';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
