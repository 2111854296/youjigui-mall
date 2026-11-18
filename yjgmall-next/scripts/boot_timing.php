<?php

$t0 = microtime(true);
require __DIR__.'/../vendor/autoload.php';
$t1 = microtime(true);
$app = require __DIR__.'/../bootstrap/app.php';
$t2 = microtime(true);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$t3 = microtime(true);

echo 'autoload_ms='.(int)(($t1 - $t0) * 1000)."\n";
echo 'app_ms='.(int)(($t2 - $t1) * 1000)."\n";
echo 'bootstrap_ms='.(int)(($t3 - $t2) * 1000)."\n";
echo 'total_ms='.(int)(($t3 - $t0) * 1000)."\n";
