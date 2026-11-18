<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$files = ['p1.png', 'p2.jpg', 'p3.png', 'p4.jpg', 'p5.jpg', 'p6.jpg', 'p7.jpg', 'p8.jpg', 'p9.jpg', 'p10.png', 'p11.jpg', 'p12.jpg'];
$i = 0;
foreach (App\Models\Product::query()->orderBy('id')->cursor() as $p) {
    $p->thumb = '/img/products/' . $files[$i % count($files)];
    $p->save();
    $i++;
}
echo "updated={$i}\n";
