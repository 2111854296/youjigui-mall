<?php

namespace app\model;

class GiftOrder extends BaseModel
{
    protected $name = 'gift_orders';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function gift()
    {
        return $this->belongsTo(Gift::class, 'gift_id');
    }
}
