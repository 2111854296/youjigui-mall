<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ArticleTopic extends Model
{
    protected $fillable = ['name', 'slug', 'summary', 'sort', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_topic');
    }
}
