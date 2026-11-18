<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    
    protected $fillable = ['user_id', 'title', 'thumb', 'images', 'summary', 'content', 'status', 'views'];
    protected function casts(): array { return ['images' => 'array']; }
}
