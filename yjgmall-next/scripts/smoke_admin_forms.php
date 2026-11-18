<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$fields = config('admin_resource_fields');
echo 'fields resources: '.count($fields).PHP_EOL;

$ctrl = app(App\Http\Controllers\Api\V1\AdminResourceController::class);
$ref = new ReflectionClass($ctrl);
$m = $ref->getMethod('cfg');
$m->setAccessible(true);

foreach (['users', 'supplies', 'articles', 'vip-applications', 'pages'] as $key) {
    $cfg = $m->invoke($ctrl, $key);
    echo $key
        .' fields='.count($cfg['fields'] ?? [])
        .' editable='.count($cfg['editable'] ?? [])
        .' create='.(($cfg['create'] ?? false) ? '1' : '0')
        .PHP_EOL;
}

$user = App\Models\User::query()->where('email', 'admin@yjgmall.test')->first()
    ?: App\Models\User::query()->where('is_admin', true)->first();
if (! $user) {
    echo "NO ADMIN USER\n";
    exit(1);
}
$token = $user->createToken('smoke-admin-forms')->plainTextToken;
echo 'token ok len='.strlen($token).PHP_EOL;

$base = env('APP_URL', 'http://127.0.0.1:8080');
// use internal request
$request = Illuminate\Http\Request::create('/api/v1/admin/resources/supplies/meta', 'GET');
$request->headers->set('Authorization', 'Bearer '.$token);
$request->setUserResolver(fn () => $user);

$meta = $ctrl->meta('supplies');
$payload = json_decode($meta->getContent(), true);
echo 'supplies meta fields: '.count($payload['data']['fields'] ?? []).PHP_EOL;
echo 'first field: '.($payload['data']['fields'][0]['name'] ?? '?').'/'.($payload['data']['fields'][0]['type'] ?? '?').PHP_EOL;

$showId = App\Models\Supply::query()->value('id');
if ($showId) {
    $show = $ctrl->show('supplies', (int) $showId);
    $s = json_decode($show->getContent(), true);
    echo 'show supply #'.$showId.' title='.($s['data']['title'] ?? '').PHP_EOL;
}

$opts = $ctrl->options(Illuminate\Http\Request::create('/x', 'GET', ['q' => '']), 'user');
$o = json_decode($opts->getContent(), true);
echo 'user options: '.count($o['data'] ?? []).PHP_EOL;

echo "OK\n";
