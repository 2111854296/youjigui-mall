<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyNews extends Model
{
    protected $table = 'company_news';

    protected $fillable = ['company_id', 'title', 'content', 'status'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
