<?php

namespace app\model;

class Special extends BaseModel
{
    protected $name = 'specials';

    public function items()
    {
        return $this->hasMany(SpecialItem::class, 'special_id');
    }
}
