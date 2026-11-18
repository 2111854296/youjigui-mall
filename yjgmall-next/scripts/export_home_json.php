<?php

use App\Http\Controllers\Api\V1\HomeController;
use Illuminate\Support\Facades\Cache;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

Cache::store('file')->forget('api:home:v6');

$t = microtime(true);
$controller = $app->make(HomeController::class);
$resp = $controller();
$payload = $resp->getData(true);

$outDir = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'yjgmall-web'.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'data';
if (! is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}
$file = $outDir.DIRECTORY_SEPARATOR.'home.json';
file_put_contents($file, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo 'wrote '.$file.' bytes='.filesize($file).' in '.(int) ((microtime(true) - $t) * 1000)."ms\n";
