<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exhibit extends Model
{
    
    protected $fillable = ['title', 'area', 'venue', 'starts_on', 'ends_on', 'organizer', 'summary', 'content', 'status', 'views'];
    protected function casts(): array { return ['starts_on' => 'date', 'ends_on' => 'date']; }
}
