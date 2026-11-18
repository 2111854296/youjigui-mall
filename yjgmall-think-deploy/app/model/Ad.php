<?php
namespace app\model;
class Ad extends BaseModel {
  protected $name = 'ads';
  public static function activeQuery(){ return static::where('is_active',1); }
}