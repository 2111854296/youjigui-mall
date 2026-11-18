<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftOrder extends Model
{
    
    protected $fillable = ['user_id', 'gift_id', 'points', 'status', 'receiver_name', 'receiver_phone', 'receiver_address'];
    
}
