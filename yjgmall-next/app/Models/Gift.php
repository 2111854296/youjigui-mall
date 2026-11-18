<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gift extends Model
{
    
    protected $fillable = ['title', 'thumb', 'points', 'stock', 'summary', 'content', 'status'];
    
}
