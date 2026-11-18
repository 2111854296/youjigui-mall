<?php

namespace app\model;

class CompanyNews extends BaseModel
{
    protected $name = 'company_news';

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
