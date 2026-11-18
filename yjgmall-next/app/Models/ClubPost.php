<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClubPost extends Model
{
    
    protected $fillable = ['user_id', 'title', 'circle', 'content', 'status', 'views', 'replies'];
    
}
