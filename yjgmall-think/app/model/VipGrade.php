<?php

namespace app\model;

class VipGrade extends BaseModel
{
    protected $name = 'vip_grades';

    /** @var array<string, string> */
    protected $type = [
        'perks' => 'json',
        'limits' => 'json',
    ];
}
