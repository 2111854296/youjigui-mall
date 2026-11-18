<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupDeal extends Model
{
    
    protected $fillable = ['user_id', 'title', 'thumb', 'price', 'market_price', 'stock', 'sold', 'ends_at', 'summary', 'content', 'status', 'views'];
    protected function casts(): array { return ['ends_at' => 'datetime', 'price' => 'decimal:2', 'market_price' => 'decimal:2']; }
}
