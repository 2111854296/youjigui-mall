<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowQuestion extends Model
{
    
    protected $fillable = ['user_id', 'title', 'content', 'reward', 'status', 'views', 'answers_count'];
    
}
