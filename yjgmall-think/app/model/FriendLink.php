<?php
namespace app\model;
class FriendLink extends BaseModel {
  protected $name = 'friend_links';
  public static function activeQuery(){ return static::where('is_active',1); }
}