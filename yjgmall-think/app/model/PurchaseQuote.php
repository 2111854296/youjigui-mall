<?php
namespace app\model;
class PurchaseQuote extends BaseModel {
  protected $name = 'purchase_quotes';
  public function purchase(){ return $this->belongsTo(Purchase::class, 'purchase_id'); }
  public function user(){ return $this->belongsTo(User::class, 'user_id'); }
}
