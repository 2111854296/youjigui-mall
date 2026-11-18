<?php

namespace app\model;

class Purchase extends BaseModel
{
    protected $name = 'purchases';

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function publishedQuery()
    {
        return static::where('status', 'approved');
    }

    public function toListArray(): array
    {
        return [
            'id' => (int) $this->id,
            'title' => $this->title,
            'budget' => $this->budget !== null ? (string) $this->budget : null,
            'quantity' => $this->quantity,
            'area' => $this->area,
            'summary' => $this->summary,
            'refreshed_at' => $this->refreshed_at,
            'category' => $this->category ? [
                'id' => (int) $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null,
        ];
    }
}