<?php

namespace app\model;

class CompanyLink extends BaseModel
{
    protected $name = 'company_links';

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
