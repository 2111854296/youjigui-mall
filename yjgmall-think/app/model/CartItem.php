<?php

namespace app\model;

class CartItem extends BaseModel
{
    protected $name = 'cart_items';

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
