<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Special extends Model
{
    
    protected $fillable = ['name', 'slug', 'thumb', 'summary', 'content', 'sort', 'status', 'views'];
    
}
