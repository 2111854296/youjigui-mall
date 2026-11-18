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
    Route::get('site', 'api.v1.Site/index');

    Route::get('sell', 'api.v1.Sell/index');
    Route::get('sell/compare', 'api.v1.Sell/compare');
    Route::get('sell/:id', 'api.v1.Sell/show');

    Route::get('buy', 'api.v1.Buy/index');
    Route::get('buy/:id', 'api.v1.Buy/show');
    Route::post('buy/:id/quote', 'api.v1.Buy/quote');

    Route::get('companies', 'api.v1.Company/index');
    Route::get('companies/:company', 'api.v1.Company/show');

    Route::get('samples', 'api.v1.Mall/samples');
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
    // ticket 须在 :id 之前；仅防护 JSON，不影响 /quotes HTML 收录
    Route::get('quotes/ticket', 'api.v1.QuoteTicket/issue');
    Route::get('quotes', 'api.v1.Quote/index')->middleware(\app\middleware\QuoteGuard::class);
    Route::get('quotes/:id', 'api.v1.Quote/show')->middleware(\app\middleware\QuoteGuard::class);

    Route::get('jobs', 'api.v1.ExtraModule/jobs');
    Route::get('jobs/:id', 'api.v1.ExtraModule/jobShow');
    Route::get('talents', 'api.v1.ExtraModule/talents');
    Route::get('talents/:id', 'api.v1.ExtraModule/talentShow');
    Route::get('know', 'api.v1.ExtraModule/knows');
    Route::get('know/:id', 'api.v1.ExtraModule/knowShow');
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

    Route::get('captcha/slide', 'api.v1.Captcha/gen');
    Route::post('captcha/slide/check', 'api.v1.Captcha/check');

    Route::post('auth/register', 'api.v1.Auth/register');
    Route::post('auth/login', 'api.v1.Auth/login');
    Route::post('auth/forgot-password', 'api.v1.Auth/sendResetCode');
    Route::post('auth/reset-password', 'api.v1.Auth/resetPassword');
    Route::post('inquiries', 'api.v1.Inquiry/store');
};

Route::group('api/v1', $apiV1)->middleware(\app\middleware\OptionalApiAuth::class);

$authMember = function () {
    Route::post('auth/logout', 'api.v1.Auth/logout');
    Route::get('auth/me', 'api.v1.Auth/me');
    Route::post('vip/apply', 'api.v1.Vip/apply');
    Route::get('vip/me', 'api.v1.Vip/me');
    Route::get('vip/pay/config', 'api.v1.Vip/payConfig');
    Route::post('vip/pay/orders', 'api.v1.Vip/createPayOrder');
    Route::get('vip/pay/orders', 'api.v1.Vip/payOrders');
    Route::get('vip/pay/orders/:id', 'api.v1.Vip/payOrder');
    Route::post('vip/pay/orders/:id/voucher', 'api.v1.Vip/submitPayVoucher');
    Route::post('vip/pay/orders/:id/cancel', 'api.v1.Vip/cancelPayOrder');
    Route::post('vip/unlock-contact', 'api.v1.Vip/unlockContact');
    Route::post('vip/buy-report', 'api.v1.Vip/buyReport');
    Route::get('vip/seats', 'api.v1.Vip/seats');
    Route::post('vip/seats/invite', 'api.v1.Vip/inviteSeat');
    Route::post('vip/seats/accept', 'api.v1.Vip/acceptSeat');
    Route::post('vip/seats/:id/revoke', 'api.v1.Vip/revokeSeat');
    Route::get('vip/contact-packs', 'api.v1.Vip/contactPacks');
    Route::post('vip/contact-packs/buy', 'api.v1.Vip/buyContactPack');
    Route::get('vip/invite', 'api.v1.Vip/inviteStats');
    Route::post('vip/jobs/sticky', 'api.v1.Vip/stickyJob');

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
    Route::post('member/quotes/:id/read', 'api.v1.Buy/readQuote');
    Route::post('member/quotes/:id/status', 'api.v1.Buy/updateQuote');

    Route::get('member/favorites/check', 'api.v1.Favorite/check');
    Route::get('member/favorites', 'api.v1.Favorite/index');
    Route::post('member/favorites', 'api.v1.Favorite/store');
    Route::delete('member/favorites/:id', 'api.v1.Favorite/destroy');

    Route::get('member/addresses', 'api.v1.Address/index');
    Route::post('member/addresses', 'api.v1.Address/store');
    Route::put('member/addresses/:id', 'api.v1.Address/update');
    Route::delete('member/addresses/:id', 'api.v1.Address/destroy');

    Route::get('member/inquiries', 'api.v1.Inquiry/index');
    Route::post('member/inquiries/:id/read', 'api.v1.Inquiry/read');
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
    Route::get('member/fund/orders', 'api.v1.Finance/fundOrders');
    Route::get('member/fund/orders/:id', 'api.v1.Finance/fundOrder');
    Route::post('member/fund/orders/:id/voucher', 'api.v1.Finance/submitFundVoucher');
    Route::post('member/fund/orders/:id/cancel', 'api.v1.Finance/cancelFundOrder');
    Route::get('member/spreads', 'api.v1.Finance/spreads');
    Route::post('member/spreads', 'api.v1.Finance/storeSpread');
    Route::get('member/ad-bookings', 'api.v1.Finance/adBookings');
    Route::post('member/ad-bookings', 'api.v1.Finance/bookAd');

    Route::get('member/jobs', 'api.v1.ExtraModule/memberJobs');
    Route::post('member/jobs', 'api.v1.ExtraModule/storeJob');
    Route::get('member/jobs/:id', 'api.v1.ExtraModule/memberJobShow');
    Route::put('member/jobs/:id', 'api.v1.ExtraModule/updateJob');
    Route::post('member/jobs/:id/status', 'api.v1.ExtraModule/setJobStatus');
    Route::delete('member/jobs/:id', 'api.v1.ExtraModule/destroyJob');

    Route::post('know/:id/answers', 'api.v1.ExtraModule/answerKnow');
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

    Route::get('member/photo', 'api.v1.ExtraModule/memberPhotos');
    Route::post('member/photo', 'api.v1.ExtraModule/storePhoto');
    Route::get('member/photo/:id', 'api.v1.ExtraModule/memberPhotoShow');
    Route::put('member/photo/:id', 'api.v1.ExtraModule/updatePhoto');
    Route::delete('member/photo/:id', 'api.v1.ExtraModule/destroyPhoto');

    Route::get('member/video', 'api.v1.ExtraModule/memberVideos');
    Route::post('member/video', 'api.v1.ExtraModule/storeVideo');
    Route::get('member/video/:id', 'api.v1.ExtraModule/memberVideoShow');
    Route::put('member/video/:id', 'api.v1.ExtraModule/updateVideo');
    Route::delete('member/video/:id', 'api.v1.ExtraModule/destroyVideo');

    Route::get('member/group', 'api.v1.ExtraModule/memberGroups');
    Route::post('member/group', 'api.v1.ExtraModule/storeGroup');
    Route::get('member/group/:id', 'api.v1.ExtraModule/memberGroupShow');
    Route::put('member/group/:id', 'api.v1.ExtraModule/updateGroup');
    Route::delete('member/group/:id', 'api.v1.ExtraModule/destroyGroup');

    Route::get('member/exhibit', 'api.v1.ExtraModule/memberExhibits');
    Route::post('member/exhibit', 'api.v1.ExtraModule/storeExhibit');
    Route::get('member/exhibit/:id', 'api.v1.ExtraModule/memberExhibitShow');
    Route::put('member/exhibit/:id', 'api.v1.ExtraModule/updateExhibit');
    Route::delete('member/exhibit/:id', 'api.v1.ExtraModule/destroyExhibit');

    Route::get('member/brand', 'api.v1.ExtraModule/memberBrands');
    Route::post('member/brand', 'api.v1.ExtraModule/storeBrand');
    Route::get('member/brand/:id', 'api.v1.ExtraModule/memberBrandShow');
    Route::put('member/brand/:id', 'api.v1.ExtraModule/updateBrand');
    Route::delete('member/brand/:id', 'api.v1.ExtraModule/destroyBrand');

    Route::post('group/:id/buy', 'api.v1.ExtraModule/buyGroup');
    Route::get('member/group-orders', 'api.v1.ExtraModule/memberGroupOrders');
    Route::get('member/group-orders/:id', 'api.v1.ExtraModule/memberGroupOrderShow');
    Route::get('member/group-seller-orders', 'api.v1.ExtraModule/memberGroupSellerOrders');
    Route::post('member/group-orders/:id/status', 'api.v1.ExtraModule/updateGroupOrderStatus');

    Route::get('member/resumes', 'api.v1.ExtraModule/memberResumes');
    Route::post('member/resumes', 'api.v1.ExtraModule/storeResume');
    Route::get('member/resumes/:id', 'api.v1.ExtraModule/memberResumeShow');
    Route::put('member/resumes/:id', 'api.v1.ExtraModule/updateResume');
    Route::post('member/resumes/:id/default', 'api.v1.ExtraModule/setDefaultResume');
    Route::delete('member/resumes/:id', 'api.v1.ExtraModule/destroyResume');
    Route::get('jobs/:id/apply', 'api.v1.ExtraModule/applyJobOptions');
    Route::post('jobs/:id/apply', 'api.v1.ExtraModule/applyJob');
    Route::get('member/job-applications', 'api.v1.ExtraModule/memberJobApplications');
    Route::get('member/job-received-applications', 'api.v1.ExtraModule/memberReceivedApplications');
    Route::post('member/job-applications/:id/read', 'api.v1.ExtraModule/readJobApplication');
    Route::post('member/job-applications/:id/status', 'api.v1.ExtraModule/updateJobApplicationStatus');

    Route::post('downloads/:id/fetch', 'api.v1.Download/fetch');
    Route::post('gift/:id/redeem', 'api.v1.ExtraModule/redeemGift');
    Route::get('member/friends', 'api.v1.ExtraModule/friends');
    Route::post('member/friends', 'api.v1.ExtraModule/addFriend');
    Route::delete('member/friends/:id', 'api.v1.ExtraModule/removeFriend');

    Route::get('member/profile-deep', 'api.v1.MemberParity/profileDeep');
    Route::put('member/profile-deep', 'api.v1.MemberParity/updateProfileDeep');
    Route::put('member/passwords', 'api.v1.MemberParity/updatePasswords');
    Route::get('member/validations', 'api.v1.MemberParity/validations');
    Route::post('member/validations/sms', 'api.v1.MemberParity/sendMobileCode');
    Route::post('member/validations', 'api.v1.MemberParity/submitValidation');
    Route::get('member/oauth', 'api.v1.MemberParity/oauth');
    Route::post('member/oauth', 'api.v1.MemberParity/bindOauth');

    Route::post('member/notice-seen', 'api.v1.MemberParity/markNoticeSeen');
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

    Route::post('member/products/shelf', 'api.v1.MemberParity/shelfProducts');
    Route::post('member/products/showcase', 'api.v1.MemberParity/showcaseProduct');
    Route::post('member/products/sample', 'api.v1.MemberParity/sampleProduct');
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
    Route::post('resources/:resource/batch', 'api.v1.AdminResource/batch');
    Route::post('resources/quote-products/import', 'api.v1.AdminResource/importQuotePrices');
    Route::get('resources/quote-products/overview', 'api.v1.AdminResource/quoteOverview');
    Route::get('resources/quote-products/:id/board', 'api.v1.AdminResource/quoteBoard');
    Route::get('resources/quote-products/:id/prices', 'api.v1.AdminResource/quotePrices');
    Route::post('resources/quote-products/:id/prices', 'api.v1.AdminResource/saveQuotePrice');
    Route::delete('resources/quote-products/:id/prices', 'api.v1.AdminResource/clearQuotePrices');
    Route::delete('resources/quote-products/:id/prices/:priceId', 'api.v1.AdminResource/destroyQuotePrice');
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

    Route::get('site-settings', 'api.v1.AdminSite/show');
    Route::put('site-settings', 'api.v1.AdminSite/update');

    Route::get('security', 'api.v1.AdminSecurity/status');
    Route::put('security/second-password', 'api.v1.AdminSecurity/setPassword');
    Route::post('security/verify', 'api.v1.AdminSecurity/verify');
    Route::get('recycle', 'api.v1.AdminSecurity/recycleIndex');
    Route::post('recycle/:id/restore', 'api.v1.AdminSecurity/recycleRestore');
    Route::delete('recycle/:id', 'api.v1.AdminSecurity/recyclePurge');

    Route::get('modules/company/templates', 'api.v1.AdminModule/templates');
    Route::post('modules/company/templates/assign', 'api.v1.AdminModule/assignTemplate');
    Route::post('modules/:module/refresh', 'api.v1.AdminModule/refresh');
    Route::get('modules/:module/settings', 'api.v1.AdminModule/settingsShow');
    Route::put('modules/:module/settings', 'api.v1.AdminModule/settingsUpdate');
};

Route::group('api/v1/admin', $admin)->middleware([
    \app\middleware\ApiAuth::class,
    \app\middleware\AdminAuth::class,
]);
