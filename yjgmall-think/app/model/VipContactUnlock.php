<?php

namespace app\model;

class VipContactUnlock extends BaseModel
{
    protected $name = 'vip_contact_unlocks';

    /** @var array<string, string> */
    protected $type = [
        'contact_payload' => 'json',
    ];
}
