<?php

namespace app\model;

class Company extends BaseModel
{
    protected $name = 'companies';

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public static function publishedQuery()
    {
        return static::where('status', 'approved');
    }

    public function toListArray(): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'logo' => $this->logo,
            'area' => $this->area,
            'business' => $this->business,
            'is_certified' => (bool) $this->is_certified,
            'is_featured' => (bool) $this->is_featured,
            'intro' => $this->intro,
        ];
    }
}