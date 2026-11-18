<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Article extends Model
{
    protected $fillable = [
        'user_id', 'category_id', 'title', 'thumb', 'author', 'summary', 'content',
        'type', 'is_top', 'status', 'views', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_top' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function topics()
    {
        return $this->belongsToMany(ArticleTopic::class, 'article_topic');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
