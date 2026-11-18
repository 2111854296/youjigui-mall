<?php

use App\Models\User;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$demo = User::where('username', '2111854296')->first();
if ($demo) {
    $demo->update([
        'group_code' => 'personal',
        'status' => 'active',
        'balance' => max((float) $demo->balance, 500),
    ]);
    echo "demo ok {$demo->id}\n";
} else {
    echo "no demo\n";
}

$admin = User::where('is_admin', 1)->first();
if ($admin) {
    $admin->update(['group_code' => 'admin']);
    echo "admin ok {$admin->id}\n";
}

User::whereNull('group_code')->orWhere('group_code', '')->update(['group_code' => 'personal']);
echo "groups backfilled\n";
