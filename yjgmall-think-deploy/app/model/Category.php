<?php

namespace app\model;

class Category extends BaseModel
{
    protected $name = 'categories';

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->where('is_active', 1)->order('sort', 'asc');
    }

    public function toListArray(): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'parent_id' => $this->parent_id,
            'module' => $this->module,
            'children' => [],
        ];
    }
}