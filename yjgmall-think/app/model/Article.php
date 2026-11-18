<?php

namespace app\model;

class Article extends BaseModel
{
    protected $name = 'articles';

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function quoteProduct()
    {
        return $this->belongsTo(QuoteProduct::class, 'quote_product_id');
    }

    public static function publishedQuery()
    {
        return static::where('status', 'published');
    }

    /** 前台展示浏览量 = 真实访问 + 后台增量 */
    public function displayViews(): int
    {
        return (int) ($this->views ?? 0) + (int) ($this->view_boost ?? 0);
    }

    /** 按展示浏览量排序（真实 + 增量） */
    public static function orderByDisplayViews($query, string $dir = 'desc')
    {
        return $query->orderRaw('(IFNULL(views,0)+IFNULL(view_boost,0)) '.$dir);
    }

    public function toListArray(): array
    {
        return [
            'id' => (int) $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'summary' => $this->summary,
            'views' => $this->displayViews(),
            'views_real' => (int) ($this->views ?? 0),
            'view_boost' => (int) ($this->view_boost ?? 0),
            'published_at' => $this->published_at,
            'thumb' => $this->thumb,
            'author' => $this->author ?? null,
            'source' => $this->source ?? null,
            'area' => $this->area ?? null,
            'level' => (int) ($this->level ?? 0),
            'is_top' => (bool) ($this->is_top ?? false),
        ];
    }
}
