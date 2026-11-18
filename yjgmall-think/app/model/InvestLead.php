<?php

namespace app\model;

class InvestLead extends BaseModel
{
    protected $name = 'invest_leads';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function project()
    {
        return $this->belongsTo(InvestProject::class, 'invest_project_id');
    }
}
