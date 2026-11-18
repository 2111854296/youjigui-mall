<?php

namespace app\controller\api;

use app\BaseController;
use think\Response;

class Ping extends BaseController
{
    public function index(): Response
    {
        return json(['ok' => true, 'framework' => 'thinkphp', 'version' => '8.1']);
    }
}
