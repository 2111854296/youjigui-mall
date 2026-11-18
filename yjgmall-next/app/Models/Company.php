<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Company extends Model
{
    protected $fillable = [
        'user_id', 'name', 'slug', 'category_id', 'logo', 'contact', 'phone',
        'website', 'address', 'area', 'business', 'intro', 'content',
        'status', 'is_featured', 'is_certified', 'template', 'views',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_certified' => 'boolean',
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

    public function supplies()
    {
        return $this->hasMany(Supply::class, 'user_id', 'user_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'user_id', 'user_id');
    }

    public function news()
    {
        return $this->hasMany(CompanyNews::class);
    }

    public function honors()
    {
        return $this->hasMany(CompanyHonor::class);
    }

    public function pages()
    {
        return $this->hasMany(CompanyPage::class);
    }

    public function links()
    {
        return $this->hasMany(CompanyLink::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'approved');
    }
}
