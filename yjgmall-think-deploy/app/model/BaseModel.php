<?php

namespace app\model;

use think\Model;

class BaseModel extends Model
{
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    protected $autoWriteTimestamp = 'datetime';
}
