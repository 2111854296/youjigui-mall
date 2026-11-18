<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BuyController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MallController;
use App\Http\Controllers\Member\MemberController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SellController;
use Illuminate\Support\Facades\Route;

/**
 * 宝塔同域部署：SERVE_SPA=true 时由 Nuxt 静态页接管前台/会员/Vue 后台，
 * API 仍走 /api/v1，旧 Filament 在 /filament。
 */
if (filter_var(env('SERVE_SPA', false), FILTER_VALIDATE_BOOLEAN)) {
    Route::get('/{any?}', function () {
        $index = public_path('index.html');
        abort_unless(is_file($index), 404, '前端未部署：缺少 public/index.html');

        return response(file_get_contents($index), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    })->where('any', '^(?!api|filament|up|storage).*$');

    return;
}

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [SearchController::class, 'index'])->name('search');

Route::get('/sell', [SellController::class, 'index'])->name('sell.index');
Route::get('/sell/{supply}', [SellController::class, 'show'])->name('sell.show');

Route::get('/buy', [BuyController::class, 'index'])->name('buy.index');
Route::get('/buy/{purchase}', [BuyController::class, 'show'])->name('buy.show');

Route::get('/company', [CompanyController::class, 'index'])->name('company.index');
Route::get('/company/{company:slug}', [CompanyController::class, 'show'])->name('company.show');

Route::get('/mall', [MallController::class, 'index'])->name('mall.index');
Route::middleware('auth')->group(function () {
    Route::get('/mall/cart', [MallController::class, 'cart'])->name('mall.cart');
    Route::post('/mall/{product}/cart', [MallController::class, 'addCart'])->name('mall.cart.add');
    Route::post('/mall/checkout', [MallController::class, 'checkout'])->name('mall.checkout');
});
Route::get('/mall/{product}', [MallController::class, 'show'])->name('mall.show');

Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::get('/news/{article}', [NewsController::class, 'show'])->name('news.show');

Route::get('/page/{slug}', [PageController::class, 'show'])->name('page.show');

Route::middleware('guest')->group(function () {
    Route::get('/member/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/member/login', [AuthController::class, 'login']);
    Route::get('/member/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/member/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->prefix('member')->name('member.')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', [MemberController::class, 'index'])->name('index');
    Route::get('/profile', [MemberController::class, 'profile'])->name('profile');
    Route::put('/profile', [MemberController::class, 'updateProfile'])->name('profile.update');

    Route::get('/supplies', [MemberController::class, 'supplies'])->name('supplies');
    Route::get('/supplies/create', [MemberController::class, 'createSupply'])->name('supplies.create');
    Route::post('/supplies', [MemberController::class, 'storeSupply'])->name('supplies.store');
    Route::get('/supplies/{supply}/edit', [MemberController::class, 'editSupply'])->name('supplies.edit');
    Route::put('/supplies/{supply}', [MemberController::class, 'updateSupply'])->name('supplies.update');
    Route::delete('/supplies/{supply}', [MemberController::class, 'destroySupply'])->name('supplies.destroy');
    Route::post('/supplies/{supply}/refresh', [MemberController::class, 'refreshSupply'])->name('supplies.refresh');

    Route::get('/purchases', [MemberController::class, 'purchases'])->name('purchases');
    Route::get('/purchases/create', [MemberController::class, 'createPurchase'])->name('purchases.create');
    Route::post('/purchases', [MemberController::class, 'storePurchase'])->name('purchases.store');
    Route::delete('/purchases/{purchase}', [MemberController::class, 'destroyPurchase'])->name('purchases.destroy');

    Route::get('/orders', [MemberController::class, 'orders'])->name('orders');
    Route::get('/orders/{order}/pay-mock', [MemberController::class, 'payMock'])->name('orders.pay.mock');
    Route::get('/messages', [MemberController::class, 'messages'])->name('messages');
});
