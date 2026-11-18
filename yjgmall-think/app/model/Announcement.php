<?php
namespace app\model;
class Announcement extends BaseModel {
  protected $name = 'announcements';
  public static function activeQuery(){ return static::where('is_active',1); }
}