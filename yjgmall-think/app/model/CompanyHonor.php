<?php

namespace app\model;

class CompanyHonor extends BaseModel
{
    protected $name = 'company_honors';

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
