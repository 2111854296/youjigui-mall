<?php

namespace app\model;

class Supply extends BaseModel
{
    protected $name = 'supplies';

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
            'thumb' => $this->thumb,
            'price' => $this->price !== null ? (string) $this->price : null,
            'unit' => $this->unit,
            'area' => $this->area,
            'brand' => $this->brand,
            'summary' => $this->summary,
            'is_elite' => (bool) $this->is_elite,
            'is_urgent' => (bool) $this->is_urgent,
            'level' => (int) $this->level,
            'views' => (int) $this->views,
            'refreshed_at' => $this->refreshed_at,
            'category' => $this->category ? [
                'id' => (int) $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null,
            'user' => $this->user ? [
                'id' => (int) $this->user->id,
                'name' => $this->user->name,
                'username' => $this->user->username,
                'company_name' => $this->user->company_name,
            ] : null,
        ];
    }
}