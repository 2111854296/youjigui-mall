<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyHonor extends Model
{
    protected $fillable = ['company_id', 'title', 'image', 'authority', 'issued_at'];

    protected function casts(): array
    {
        return ['issued_at' => 'date'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
