<?php

use think\facade\Route;

Route::get('think', function () {
    return 'hello,ThinkPHP8!';
});

$apiV1 = function () {
    Route::get('ping', 'api.Ping/index');
    Route::get('home', 'api.v1.Home/index');
    Route::get('search', 'api.v1.Search/index');
    Route::get('categories', 'api.v1.Portal/categories');
    Route::get('ads', 'api.v1.Portal/ads');
    Route::get('ad-positions', 'api.v1.Portal/adPositions');
    Route::get('ad-places', 'api.v1.Vip/adPlaces');
    Route::get('links', 'api.v1.Portal/links');
    Route::get('announcements', 'api.v1.Portal/announcements');
    Route::get('hotwords', 'api.v1.Portal/hotwords');

    Route::get('sell', 'api.v1.Sell/index');
    Route::get('sell/compare', 'api.v1.Sell/compare');
    Route::get('sell/:id', 'api.v1.Sell/show');

    Route::get('buy', 'api.v1.Buy/index');
    Route::get('buy/:id', 'api.v1.Buy/show');
    Route::post('buy/:id/quote', 'api.v1.Buy/quote');

    Route::get('companies', 'api.v1.Company/index');
    Route::get('companies/:company', 'api.v1.Company/show');

    Route::get('mall', 'api.v1.Mall/index');
    Route::get('mall/:id', 'api.v1.Mall/show');
    Route::get('mall/:product/reviews', 'api.v1.Review/index');

    Route::get('news', 'api.v1.News/index');
    Route::get('news/rank', 'api.v1.News/rank');
    Route::get('news/topics', 'api.v1.News/topics');
    Route::get('news/topics/:slug', 'api.v1.News/topic');
    Route::get('news/:id', 'api.v1.News/show');

    Route::get('brands', 'api.v1.Brand/index');
    Route::get('brands/:brand', 'api.v1.Brand/show');

    Route::get('downloads', 'api.v1.Download/index');
    Route::get('downloads/:id', 'api.v1.Download/show');
    Route::post('downloads/:id/hit', 'api.v1.Download/hit');

    Route::get('invest', 'api.v1.Invest/index');
    Route::get('invest/:id', 'api.v1.Invest/show');
    Route::post('invest/apply', 'api.v1.Invest/apply');

    Route::get('pages/:slug', 'api.v1.Page/show');
    Route::get('vip/grades', 'api.v1.Vip/grades');
    Route::get('quotes', 'api.v1.Quote/index');

    Route::get('jobs', 'api.v1.ExtraModule/jobs');
    Route::get('jobs/:id', 'api.v1.ExtraModule/jobShow');
    Route::get('know', 'api.v1.ExtraModule/knows');
    Route::get('know/:id', 'api.v1.ExtraModule/knowShow');
    Route::post('know/:id/answers', 'api.v1.ExtraModule/answerKnow');
    Route::get('club', 'api.v1.ExtraModule/clubs');
    Route::get('club/:id', 'api.v1.ExtraModule/clubShow');
    Route::get('special', 'api.v1.ExtraModule/specials');
    Route::get('special/:id', 'api.v1.ExtraModule/specialShow');
    Route::get('photo', 'api.v1.ExtraModule/photos');
    Route::get('photo/:id', 'api.v1.ExtraModule/photoShow');
    Route::get('video', 'api.v1.ExtraModule/videos');
    Route::get('video/:id', 'api.v1.ExtraModule/videoShow');
    Route::get('group', 'api.v1.ExtraModule/groups');
    Route::get('group/:id', 'api.v1.ExtraModule/groupShow');
    Route::get('exhibit', 'api.v1.ExtraModule/exhibits');
    Route::get('exhibit/:id', 'api.v1.ExtraModule/exhibitShow');
    Route::get('gift', 'api.v1.ExtraModule/gifts');
    Route::get('gift/:id', 'api.v1.ExtraModule/giftShow');

    Route::post('auth/register', 'api.v1.Auth/register');
    Route::post('auth/login', 'api.v1.Auth/login');
    Route::post('inquiries', 'api.v1.Inquiry/store');
};

Route::group('api/v1', $apiV1)->middleware(\app\middleware\OptionalApiAuth::class);

$authMember = function () {
    Route::post('auth/logout', 'api.v1.Auth/logout');
    Route::get('auth/me', 'api.v1.Auth/me');
    Route::post('vip/apply', 'api.v1.Vip/apply');

    Route::get('member/dashboard', 'api.v1.Member/dashboard');
    Route::put('member/profile', 'api.v1.Member/updateProfile');
    Route::post('member/upload', 'api.v1.Upload/store');

    Route::get('member/supplies', 'api.v1.Member/supplies');
    Route::get('member/supplies/:id', 'api.v1.Member/showSupply');
    Route::post('member/supplies', 'api.v1.Member/storeSupply');
    Route::put('member/supplies/:id', 'api.v1.Member/updateSupply');
    Route::delete('member/supplies/:id', 'api.v1.Member/destroySupply');
    Route::post('member/supplies/:id/refresh', 'api.v1.Member/refreshSupply');

    Route::get('member/purchases', 'api.v1.Member/purchases');
    Route::get('member/purchases/:id', 'api.v1.Member/showPurchase');
    Route::post('member/purchases', 'api.v1.Member/storePurchase');
    Route::put('member/purchases/:id', 'api.v1.Member/updatePurchase');
    Route::delete('member/purchases/:id', 'api.v1.Member/destroyPurchase');

    Route::get('member/orders', 'api.v1.Member/orders');
    Route::get('member/orders/:id', 'api.v1.Member/showOrder');
    Route::post('member/orders/:id/status', 'api.v1.Member/updateOrderStatus');
    Route::get('member/seller-orders', 'api.v1.Member/sellerOrders');
    Route::get('member/cart', 'api.v1.Member/cart');
    Route::post('member/cart/:product', 'api.v1.Member/addCart');
    Route::put('member/cart/:id', 'api.v1.Member/updateCart');
    Route::delete('member/cart/:id', 'api.v1.Member/removeCart');
    Route::post('member/checkout', 'api.v1.Member/checkout');
    Route::get('member/messages', 'api.v1.Member/messages');
    Route::post('member/messages', 'api.v1.Member/sendMessage');
    Route::post('member/messages/:id/read', 'api.v1.Member/readMessage');

    Route::get('member/quotes', 'api.v1.Buy/myQuotes');
    Route::post('member/quotes/:id/status', 'api.v1.Buy/updateQuote');

    Route::get('member/favorites', 'api.v1.Favorite/index');
    Route::post('member/favorites', 'api.v1.Favorite/store');
    Route::delete('member/favorites/:id', 'api.v1.Favorite/destroy');

    Route::get('member/addresses', 'api.v1.Address/index');
    Route::post('member/addresses', 'api.v1.Address/store');
    Route::put('member/addresses/:id', 'api.v1.Address/update');
    Route::delete('member/addresses/:id', 'api.v1.Address/destroy');

    Route::get('member/inquiries', 'api.v1.Inquiry/index');
    Route::post('member/inquiries/:id/reply', 'api.v1.Inquiry/reply');
    Route::post('member/inquiries/:id/status', 'api.v1.Inquiry/updateStatus');
    Route::get('member/reviews', 'api.v1.Review/mine');
    Route::post('mall/:product/reviews', 'api.v1.Review/store');

    Route::get('member/shop', 'api.v1.Shop/show');
    Route::put('member/shop', 'api.v1.Shop/update');
    Route::post('member/shop/news', 'api.v1.Shop/storeNews');
    Route::delete('member/shop/news/:id', 'api.v1.Shop/destroyNews');
    Route::post('member/shop/honors', 'api.v1.Shop/storeHonor');
    Route::delete('member/shop/honors/:id', 'api.v1.Shop/destroyHonor');
    Route::post('member/shop/pages', 'api.v1.Shop/storePage');
    Route::delete('member/shop/pages/:id', 'api.v1.Shop/destroyPage');
    Route::post('member/shop/links', 'api.v1.Shop/storeLink');
    Route::delete('member/shop/links/:id', 'api.v1.Shop/destroyLink');

    Route::get('member/finance', 'api.v1.Finance/records');
    Route::post('member/finance/charge', 'api.v1.Finance/charge');
    Route::get('member/spreads', 'api.v1.Finance/spreads');
    Route::post('member/spreads', 'api.v1.Finance/storeSpread');
    Route::get('member/ad-bookings', 'api.v1.Finance/adBookings');
    Route::post('member/ad-bookings', 'api.v1.Finance/bookAd');

    Route::get('member/jobs', 'api.v1.ExtraModule/memberJobs');
    Route::post('member/jobs', 'api.v1.ExtraModule/storeJob');
    Route::get('member/jobs/:id', 'api.v1.ExtraModule/memberJobShow');
    Route::put('member/jobs/:id', 'api.v1.ExtraModule/updateJob');
    Route::delete('member/jobs/:id', 'api.v1.ExtraModule/destroyJob');

    Route::get('member/know', 'api.v1.ExtraModule/memberKnows');
    Route::post('member/know', 'api.v1.ExtraModule/storeKnow');
    Route::get('member/know/:id', 'api.v1.ExtraModule/memberKnowShow');
    Route::put('member/know/:id', 'api.v1.ExtraModule/updateKnow');
    Route::delete('member/know/:id', 'api.v1.ExtraModule/destroyKnow');

    Route::get('member/club', 'api.v1.ExtraModule/memberClubs');
    Route::post('member/club', 'api.v1.ExtraModule/storeClub');
    Route::get('member/club/:id', 'api.v1.ExtraModule/memberClubShow');
    Route::put('member/club/:id', 'api.v1.ExtraModule/updateClub');
    Route::delete('member/club/:id', 'api.v1.ExtraModule/destroyClub');
    Route::post('gift/:id/redeem', 'api.v1.ExtraModule/redeemGift');
    Route::get('member/friends', 'api.v1.ExtraModule/friends');
    Route::post('member/friends', 'api.v1.ExtraModule/addFriend');

    Route::get('member/profile-deep', 'api.v1.MemberParity/profileDeep');
    Route::put('member/profile-deep', 'api.v1.MemberParity/updateProfileDeep');
    Route::put('member/passwords', 'api.v1.MemberParity/updatePasswords');
    Route::get('member/validations', 'api.v1.MemberParity/validations');
    Route::post('member/validations', 'api.v1.MemberParity/submitValidation');
    Route::get('member/oauth', 'api.v1.MemberParity/oauth');
    Route::post('member/oauth', 'api.v1.MemberParity/bindOauth');

    Route::get('member/chats', 'api.v1.MemberParity/chats');
    Route::post('member/chats', 'api.v1.MemberParity/startChat');
    Route::get('member/chats/:id/messages', 'api.v1.MemberParity/chatMessages');
    Route::post('member/chats/:id/messages', 'api.v1.MemberParity/sendChat');

    Route::get('member/alerts', 'api.v1.MemberParity/alerts');
    Route::post('member/alerts', 'api.v1.MemberParity/storeAlert');
    Route::delete('member/alerts/:id', 'api.v1.MemberParity/destroyAlert');

    Route::get('member/mail', 'api.v1.MemberParity/mailSub');
    Route::put('member/mail', 'api.v1.MemberParity/saveMailSub');

    Route::get('member/withdraws', 'api.v1.MemberParity/withdraws');
    Route::post('member/withdraws', 'api.v1.MemberParity/storeWithdraw');
    Route::post('member/deposit', 'api.v1.MemberParity/addDeposit');
    Route::post('member/points/buy', 'api.v1.MemberParity/buyPoints');

    Route::get('member/products', 'api.v1.MemberParity/products');
    Route::get('member/products/:id', 'api.v1.MemberParity/showProduct');
    Route::post('member/products', 'api.v1.MemberParity/storeProduct');
    Route::put('member/products/:id', 'api.v1.MemberParity/updateProduct');
    Route::delete('member/products/:id', 'api.v1.MemberParity/destroyProduct');

    Route::get('member/articles', 'api.v1.MemberParity/articles');
    Route::get('member/articles/:id', 'api.v1.MemberParity/showArticle');
    Route::post('member/articles', 'api.v1.MemberParity/storeArticle');
    Route::put('member/articles/:id', 'api.v1.MemberParity/updateArticle');
    Route::delete('member/articles/:id', 'api.v1.MemberParity/destroyArticle');

    Route::get('member/asks', 'api.v1.MemberParity/asks');
    Route::post('member/asks', 'api.v1.MemberParity/storeAsk');
};

Route::group('api/v1', $authMember)->middleware(\app\middleware\ApiAuth::class);

$admin = function () {
    Route::get('dashboard', 'api.v1.Admin/dashboard');
    Route::get('menu', 'api.v1.AdminResource/menu');
    Route::get('options/:relation', 'api.v1.AdminResource/options');
    Route::get('resources/:resource/meta', 'api.v1.AdminResource/meta');
    Route::get('resources/:resource', 'api.v1.AdminResource/index');
    Route::get('resources/:resource/:id', 'api.v1.AdminResource/show');
    Route::post('resources/:resource', 'api.v1.AdminResource/store');
    Route::put('resources/:resource/:id', 'api.v1.AdminResource/update');
    Route::delete('resources/:resource/:id', 'api.v1.AdminResource/destroy');

    Route::get('users', 'api.v1.Admin/users');
    Route::put('users/:id', 'api.v1.Admin/updateUser');
    Route::get('supplies', 'api.v1.Admin/supplies');
    Route::put('supplies/:id', 'api.v1.Admin/updateSupply');
    Route::get('purchases', 'api.v1.Admin/purchases');
    Route::put('purchases/:id', 'api.v1.Admin/updatePurchase');
    Route::get('products', 'api.v1.Admin/products');
    Route::put('products/:id', 'api.v1.Admin/updateProduct');
    Route::get('orders', 'api.v1.Admin/orders');
    Route::put('orders/:id', 'api.v1.Admin/updateOrder');
};

Route::group('api/v1/admin', $admin)->middleware([
    \app\middleware\ApiAuth::class,
    \app\middleware\AdminAuth::class,
]);
