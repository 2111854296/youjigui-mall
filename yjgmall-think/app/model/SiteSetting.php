<?php

namespace app\model;

class SiteSetting extends BaseModel
{
    protected $name = 'site_settings';

    protected $pk = 'key';

    public $incrementing = false;

    protected $keyType = 'string';
}
