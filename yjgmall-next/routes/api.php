<?php

use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\AdminResourceController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\BuyController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\DownloadController;
use App\Http\Controllers\Api\V1\ExtraModuleController;
use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\FinanceController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\InquiryController;
use App\Http\Controllers\Api\V1\InvestController;
use App\Http\Controllers\Api\V1\MallController;
use App\Http\Controllers\Api\V1\MemberController;
use App\Http\Controllers\Api\V1\MemberParityController;
use App\Http\Controllers\Api\V1\NewsController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\PortalController;
use App\Http\Controllers\Api\V1\UploadController;
use App\Http\Controllers\Api\V1\QuoteController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SellController;
use App\Http\Controllers\Api\V1\ShopController;
use App\Http\Controllers\Api\V1\VipController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/home', HomeController::class);
    Route::get('/search', SearchController::class);
    Route::get('/categories', [PortalController::class, 'categories']);
    Route::get('/ads', [PortalController::class, 'ads']);
    Route::get('/ad-positions', [PortalController::class, 'adPositions']);
    Route::get('/ad-places', [VipController::class, 'adPlaces']);
    Route::get('/links', [PortalController::class, 'links']);
    Route::get('/announcements', [PortalController::class, 'announcements']);
    Route::get('/hotwords', [PortalController::class, 'hotwords']);

    Route::get('/sell', [SellController::class, 'index']);
    Route::get('/sell/compare', [SellController::class, 'compare']);
    Route::get('/sell/{supply}', [SellController::class, 'show']);

    Route::get('/buy', [BuyController::class, 'index']);
    Route::get('/buy/{purchase}', [BuyController::class, 'show']);
    Route::post('/buy/{purchase}/quote', [BuyController::class, 'quote']);

    Route::get('/companies', [CompanyController::class, 'index']);
    Route::get('/companies/{company}', [CompanyController::class, 'show']);

    Route::get('/mall', [MallController::class, 'index']);
    Route::get('/mall/{product}', [MallController::class, 'show']);
    Route::get('/mall/{product}/reviews', [ReviewController::class, 'index']);

    Route::get('/news', [NewsController::class, 'index']);
    Route::get('/news/rank', [NewsController::class, 'rank']);
    Route::get('/news/topics', [NewsController::class, 'topics']);
    Route::get('/news/topics/{slug}', [NewsController::class, 'topic']);
    Route::get('/news/{article}', [NewsController::class, 'show']);

    Route::get('/brands', [BrandController::class, 'index']);
    Route::get('/brands/{brand}', [BrandController::class, 'show']);

    Route::get('/downloads', [DownloadController::class, 'index']);
    Route::get('/downloads/{download}', [DownloadController::class, 'show']);
    Route::post('/downloads/{download}/hit', [DownloadController::class, 'hit']);

    Route::get('/invest', [InvestController::class, 'index']);
    Route::get('/invest/{invest}', [InvestController::class, 'show']);
    Route::post('/invest/apply', [InvestController::class, 'apply']);

    Route::get('/pages/{slug}', [PageController::class, 'show']);
    Route::get('/vip/grades', [VipController::class, 'grades']);
    Route::get('/quotes', [QuoteController::class, 'index']);

    Route::get('/jobs', [ExtraModuleController::class, 'jobs']);
    Route::get('/jobs/{job}', [ExtraModuleController::class, 'jobShow']);
    Route::get('/know', [ExtraModuleController::class, 'knows']);
    Route::get('/know/{know}', [ExtraModuleController::class, 'knowShow']);
    Route::post('/know/{know}/answers', [ExtraModuleController::class, 'answerKnow']);
    Route::get('/club', [ExtraModuleController::class, 'clubs']);
    Route::get('/club/{club}', [ExtraModuleController::class, 'clubShow']);
    Route::get('/special', [ExtraModuleController::class, 'specials']);
    Route::get('/special/{special}', [ExtraModuleController::class, 'specialShow']);
    Route::get('/photo', [ExtraModuleController::class, 'photos']);
    Route::get('/photo/{photo}', [ExtraModuleController::class, 'photoShow']);
    Route::get('/video', [ExtraModuleController::class, 'videos']);
    Route::get('/video/{video}', [ExtraModuleController::class, 'videoShow']);
    Route::get('/group', [ExtraModuleController::class, 'groups']);
    Route::get('/group/{group}', [ExtraModuleController::class, 'groupShow']);
    Route::get('/exhibit', [ExtraModuleController::class, 'exhibits']);
    Route::get('/exhibit/{exhibit}', [ExtraModuleController::class, 'exhibitShow']);
    Route::get('/gift', [ExtraModuleController::class, 'gifts']);
    Route::get('/gift/{gift}', [ExtraModuleController::class, 'giftShow']);

    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/inquiries', [InquiryController::class, 'store']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/vip/apply', [VipController::class, 'apply']);

        Route::get('/member/dashboard', [MemberController::class, 'dashboard']);
        Route::put('/member/profile', [MemberController::class, 'updateProfile']);
        Route::post('/member/upload', [UploadController::class, 'store']);

        Route::get('/member/supplies', [MemberController::class, 'supplies']);
        Route::get('/member/supplies/{supply}', [MemberController::class, 'showSupply']);
        Route::post('/member/supplies', [MemberController::class, 'storeSupply']);
        Route::put('/member/supplies/{supply}', [MemberController::class, 'updateSupply']);
        Route::delete('/member/supplies/{supply}', [MemberController::class, 'destroySupply']);
        Route::post('/member/supplies/{supply}/refresh', [MemberController::class, 'refreshSupply']);

        Route::get('/member/purchases', [MemberController::class, 'purchases']);
        Route::get('/member/purchases/{purchase}', [MemberController::class, 'showPurchase']);
        Route::post('/member/purchases', [MemberController::class, 'storePurchase']);
        Route::put('/member/purchases/{purchase}', [MemberController::class, 'updatePurchase']);
        Route::delete('/member/purchases/{purchase}', [MemberController::class, 'destroyPurchase']);

        Route::get('/member/orders', [MemberController::class, 'orders']);
        Route::get('/member/orders/{order}', [MemberController::class, 'showOrder']);
        Route::post('/member/orders/{order}/status', [MemberController::class, 'updateOrderStatus']);
        Route::get('/member/seller-orders', [MemberController::class, 'sellerOrders']);
        Route::get('/member/cart', [MemberController::class, 'cart']);
        Route::post('/member/cart/{product}', [MemberController::class, 'addCart']);
        Route::put('/member/cart/{cartItem}', [MemberController::class, 'updateCart']);
        Route::delete('/member/cart/{cartItem}', [MemberController::class, 'removeCart']);
        Route::post('/member/checkout', [MemberController::class, 'checkout']);
        Route::get('/member/messages', [MemberController::class, 'messages']);
        Route::post('/member/messages', [MemberController::class, 'sendMessage']);
        Route::post('/member/messages/{message}/read', [MemberController::class, 'readMessage']);

        Route::get('/member/quotes', [BuyController::class, 'myQuotes']);
        Route::post('/member/quotes/{quote}/status', [BuyController::class, 'updateQuote']);

        Route::get('/member/favorites', [FavoriteController::class, 'index']);
        Route::post('/member/favorites', [FavoriteController::class, 'store']);
        Route::delete('/member/favorites/{favorite}', [FavoriteController::class, 'destroy']);

        Route::get('/member/addresses', [AddressController::class, 'index']);
        Route::post('/member/addresses', [AddressController::class, 'store']);
        Route::put('/member/addresses/{address}', [AddressController::class, 'update']);
        Route::delete('/member/addresses/{address}', [AddressController::class, 'destroy']);

        Route::get('/member/inquiries', [InquiryController::class, 'index']);
        Route::post('/member/inquiries/{inquiry}/reply', [InquiryController::class, 'reply']);
        Route::post('/member/inquiries/{inquiry}/status', [InquiryController::class, 'updateStatus']);
        Route::get('/member/reviews', [ReviewController::class, 'mine']);
        Route::post('/mall/{product}/reviews', [ReviewController::class, 'store']);

        Route::get('/member/shop', [ShopController::class, 'show']);
        Route::put('/member/shop', [ShopController::class, 'update']);
        Route::post('/member/shop/news', [ShopController::class, 'storeNews']);
        Route::delete('/member/shop/news/{news}', [ShopController::class, 'destroyNews']);
        Route::post('/member/shop/honors', [ShopController::class, 'storeHonor']);
        Route::delete('/member/shop/honors/{honor}', [ShopController::class, 'destroyHonor']);
        Route::post('/member/shop/pages', [ShopController::class, 'storePage']);
        Route::delete('/member/shop/pages/{page}', [ShopController::class, 'destroyPage']);
        Route::post('/member/shop/links', [ShopController::class, 'storeLink']);
        Route::delete('/member/shop/links/{link}', [ShopController::class, 'destroyLink']);

        Route::get('/member/finance', [FinanceController::class, 'records']);
        Route::post('/member/finance/charge', [FinanceController::class, 'charge']);
        Route::get('/member/spreads', [FinanceController::class, 'spreads']);
        Route::post('/member/spreads', [FinanceController::class, 'storeSpread']);
        Route::get('/member/ad-bookings', [FinanceController::class, 'adBookings']);
        Route::post('/member/ad-bookings', [FinanceController::class, 'bookAd']);

        Route::get('/member/jobs', [ExtraModuleController::class, 'memberJobs']);
        Route::post('/member/jobs', [ExtraModuleController::class, 'storeJob']);
        Route::get('/member/jobs/{job}', [ExtraModuleController::class, 'memberJobShow']);
        Route::put('/member/jobs/{job}', [ExtraModuleController::class, 'updateJob']);
        Route::delete('/member/jobs/{job}', [ExtraModuleController::class, 'destroyJob']);

        Route::get('/member/know', [ExtraModuleController::class, 'memberKnows']);
        Route::post('/member/know', [ExtraModuleController::class, 'storeKnow']);
        Route::get('/member/know/{know}', [ExtraModuleController::class, 'memberKnowShow']);
        Route::put('/member/know/{know}', [ExtraModuleController::class, 'updateKnow']);
        Route::delete('/member/know/{know}', [ExtraModuleController::class, 'destroyKnow']);

        Route::get('/member/club', [ExtraModuleController::class, 'memberClubs']);
        Route::post('/member/club', [ExtraModuleController::class, 'storeClub']);
        Route::get('/member/club/{club}', [ExtraModuleController::class, 'memberClubShow']);
        Route::put('/member/club/{club}', [ExtraModuleController::class, 'updateClub']);
        Route::delete('/member/club/{club}', [ExtraModuleController::class, 'destroyClub']);
        Route::post('/gift/{gift}/redeem', [ExtraModuleController::class, 'redeemGift']);
        Route::get('/member/friends', [ExtraModuleController::class, 'friends']);
        Route::post('/member/friends', [ExtraModuleController::class, 'addFriend']);

        // Phase E — Destoon 会员深度对齐
        Route::get('/member/profile-deep', [MemberParityController::class, 'profileDeep']);
        Route::put('/member/profile-deep', [MemberParityController::class, 'updateProfileDeep']);
        Route::put('/member/passwords', [MemberParityController::class, 'updatePasswords']);
        Route::get('/member/validations', [MemberParityController::class, 'validations']);
        Route::post('/member/validations', [MemberParityController::class, 'submitValidation']);
        Route::get('/member/oauth', [MemberParityController::class, 'oauth']);
        Route::post('/member/oauth', [MemberParityController::class, 'bindOauth']);

        Route::get('/member/chats', [MemberParityController::class, 'chats']);
        Route::post('/member/chats', [MemberParityController::class, 'startChat']);
        Route::get('/member/chats/{chat}/messages', [MemberParityController::class, 'chatMessages']);
        Route::post('/member/chats/{chat}/messages', [MemberParityController::class, 'sendChat']);

        Route::get('/member/alerts', [MemberParityController::class, 'alerts']);
        Route::post('/member/alerts', [MemberParityController::class, 'storeAlert']);
        Route::delete('/member/alerts/{alert}', [MemberParityController::class, 'destroyAlert']);

        Route::get('/member/mail', [MemberParityController::class, 'mailSub']);
        Route::put('/member/mail', [MemberParityController::class, 'saveMailSub']);

        Route::get('/member/withdraws', [MemberParityController::class, 'withdraws']);
        Route::post('/member/withdraws', [MemberParityController::class, 'storeWithdraw']);
        Route::post('/member/deposit', [MemberParityController::class, 'addDeposit']);
        Route::post('/member/points/buy', [MemberParityController::class, 'buyPoints']);

        Route::get('/member/products', [MemberParityController::class, 'products']);
        Route::get('/member/products/{product}', [MemberParityController::class, 'showProduct']);
        Route::post('/member/products', [MemberParityController::class, 'storeProduct']);
        Route::put('/member/products/{product}', [MemberParityController::class, 'updateProduct']);
        Route::delete('/member/products/{product}', [MemberParityController::class, 'destroyProduct']);

        Route::get('/member/articles', [MemberParityController::class, 'articles']);
        Route::get('/member/articles/{article}', [MemberParityController::class, 'showArticle']);
        Route::post('/member/articles', [MemberParityController::class, 'storeArticle']);
        Route::put('/member/articles/{article}', [MemberParityController::class, 'updateArticle']);
        Route::delete('/member/articles/{article}', [MemberParityController::class, 'destroyArticle']);

        Route::get('/member/asks', [MemberParityController::class, 'asks']);
        Route::post('/member/asks', [MemberParityController::class, 'storeAsk']);

        // Vue3 前后端分离运营后台
        Route::middleware('admin')->prefix('admin')->group(function () {
            Route::get('/dashboard', [AdminController::class, 'dashboard']);
            Route::get('/menu', [AdminResourceController::class, 'menu']);
            Route::get('/options/{relation}', [AdminResourceController::class, 'options']);
            Route::get('/resources/{resource}/meta', [AdminResourceController::class, 'meta']);
            Route::get('/resources/{resource}', [AdminResourceController::class, 'index']);
            Route::get('/resources/{resource}/{id}', [AdminResourceController::class, 'show']);
            Route::post('/resources/{resource}', [AdminResourceController::class, 'store']);
            Route::put('/resources/{resource}/{id}', [AdminResourceController::class, 'update']);
            Route::delete('/resources/{resource}/{id}', [AdminResourceController::class, 'destroy']);

            // 兼容首批专用路由
            Route::get('/users', [AdminController::class, 'users']);
            Route::put('/users/{user}', [AdminController::class, 'updateUser']);
            Route::get('/supplies', [AdminController::class, 'supplies']);
            Route::put('/supplies/{supply}', [AdminController::class, 'updateSupply']);
            Route::get('/purchases', [AdminController::class, 'purchases']);
            Route::put('/purchases/{purchase}', [AdminController::class, 'updatePurchase']);
            Route::get('/products', [AdminController::class, 'products']);
            Route::put('/products/{product}', [AdminController::class, 'updateProduct']);
            Route::get('/orders', [AdminController::class, 'orders']);
            Route::put('/orders/{order}', [AdminController::class, 'updateOrder']);
        });
    });
});
