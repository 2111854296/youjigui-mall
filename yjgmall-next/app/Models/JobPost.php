<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobPost extends Model
{
    protected $table = 'job_posts';

    protected $fillable = [
        'user_id', 'title', 'company_name', 'area', 'salary', 'education', 'experience',
        'headcount', 'summary', 'content', 'contact_name', 'contact_phone', 'status', 'views',
    ];
}
