<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BuyController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\MallController;
use App\Http\Controllers\Api\V1\MemberController;
use App\Http\Controllers\Api\V1\NewsController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SellController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/home', HomeController::class);
    Route::get('/search', SearchController::class);

    Route::get('/sell', [SellController::class, 'index']);
    Route::get('/sell/{supply}', [SellController::class, 'show']);

    Route::get('/buy', [BuyController::class, 'index']);
    Route::get('/buy/{purchase}', [BuyController::class, 'show']);

    Route::get('/companies', [CompanyController::class, 'index']);
    Route::get('/companies/{company}', [CompanyController::class, 'show']);

    Route::get('/mall', [MallController::class, 'index']);
    Route::get('/mall/{product}', [MallController::class, 'show']);

    Route::get('/news', [NewsController::class, 'index']);
    Route::get('/news/{article}', [NewsController::class, 'show']);

    Route::get('/pages/{slug}', [PageController::class, 'show']);

    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::get('/member/dashboard', [MemberController::class, 'dashboard']);
        Route::put('/member/profile', [MemberController::class, 'updateProfile']);

        Route::get('/member/supplies', [MemberController::class, 'supplies']);
        Route::post('/member/supplies', [MemberController::class, 'storeSupply']);
        Route::put('/member/supplies/{supply}', [MemberController::class, 'updateSupply']);
        Route::delete('/member/supplies/{supply}', [MemberController::class, 'destroySupply']);
        Route::post('/member/supplies/{supply}/refresh', [MemberController::class, 'refreshSupply']);

        Route::get('/member/purchases', [MemberController::class, 'purchases']);
        Route::post('/member/purchases', [MemberController::class, 'storePurchase']);
        Route::delete('/member/purchases/{purchase}', [MemberController::class, 'destroyPurchase']);

        Route::get('/member/orders', [MemberController::class, 'orders']);
        Route::get('/member/cart', [MemberController::class, 'cart']);
        Route::post('/member/cart/{product}', [MemberController::class, 'addCart']);
        Route::post('/member/checkout', [MemberController::class, 'checkout']);
        Route::get('/member/messages', [MemberController::class, 'messages']);
    });
});
