<?php

namespace app\model;

class VipPayOrder extends BaseModel
{
    protected $name = 'vip_pay_orders';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function grade()
    {
        return $this->belongsTo(VipGrade::class, 'vip_grade_id');
    }

    /** @return list<string> */
    public static function openStatuses(): array
    {
        return ['pending_pay', 'pending_review'];
    }
}
