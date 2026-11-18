<?php

namespace app\model;

class CompanyPage extends BaseModel
{
    protected $name = 'company_pages';

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
