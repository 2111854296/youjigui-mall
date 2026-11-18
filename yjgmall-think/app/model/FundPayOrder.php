<?php

namespace app\model;

class FundPayOrder extends BaseModel
{
    protected $name = 'fund_pay_orders';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
