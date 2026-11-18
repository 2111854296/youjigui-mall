<?php

namespace app\model;

class Article extends BaseModel
{
    protected $name = 'articles';

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public static function publishedQuery()
    {
        return static::where('status', 'published');
    }

    public function toListArray(): array
    {
        return [
            'id' => (int) $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'summary' => $this->summary,
            'views' => (int) $this->views,
            'published_at' => $this->published_at,
            'thumb' => $this->thumb,
        ];
    }
}
