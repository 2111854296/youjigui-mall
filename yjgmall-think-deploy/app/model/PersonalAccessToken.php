<?php

namespace app\model;

use think\Model;

class PersonalAccessToken extends Model
{
    protected $name = 'personal_access_tokens';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    protected $autoWriteTimestamp = 'datetime';
}
