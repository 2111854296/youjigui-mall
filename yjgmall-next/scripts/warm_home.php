<?php

use App\Http\Controllers\Api\V1\HomeController;
use Illuminate\Support\Facades\Cache;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

Cache::store('file')->forget('api:home:v4');
Cache::store('file')->forget('api:home:v5');
Cache::store('file')->forget('api:home:v6');

$t = microtime(true);
$controller = $app->make(HomeController::class);
$resp = $controller();
$data = $resp->getData(true);
Cache::store('file')->forever('api:home:v6', $data['data']);
echo 'warmed ok floors='.count($data['data']['floors']).' in '.(int) ((microtime(true) - $t) * 1000)."ms\n";
