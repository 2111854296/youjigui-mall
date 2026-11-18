<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $fillable = [
        'name', 'slug', 'logo', 'website', 'country', 'intro', 'content',
        'sort', 'is_featured', 'status', 'views',
    ];

    protected function casts(): array
    {
        return ['is_featured' => 'boolean'];
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
