<?php

namespace app\model;

class Product extends BaseModel
{
    protected $name = 'products';

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public static function publishedQuery()
    {
        return static::where('status', 'approved')->where('is_listed', 1);
    }

    public function toListArray(): array
    {
        return [
            'id' => (int) $this->id,
            'title' => $this->title,
            'thumb' => $this->thumb,
            'price' => $this->price !== null ? (string) $this->price : null,
            'unit' => $this->unit,
            'sales' => (int) ($this->sales ?? 0),
            'summary' => $this->summary,
        ];
    }
}