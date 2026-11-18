<?php

namespace app\model;

class SpecialItem extends BaseModel
{
    protected $name = 'special_items';

    public function special()
    {
        return $this->belongsTo(Special::class, 'special_id');
    }
}
