<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Middleware\EnsureAdministrator;
use App\Http\Middleware\EnsureCustomerSignedIn;
use App\Http\Middleware\EnsureVerifiedCustomer;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/', function () { return view('pages.home'); });
// Route::get('/service', function () {
//     return view('pages.service');
// });
// Route::get('/booking', function () {
//     return view('pages.booking');
// });https://d3d2-120-188-76-241.ngrok-free.app/
// Route::get('/booking', [App\Http\Controllers\bookingController::class , 'index']);
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
Route::get('/api/auth/google/callback', [GoogleAuthController::class, 'callback']);
Route::get('/track_booking', [App\Http\Controllers\CustomerBookingController::class, 'track'])->name('tracking.form');
Route::post('/track_booking', [App\Http\Controllers\CustomerBookingController::class, 'lookup'])->middleware('throttle:10,1')->name('tracking.lookup');
Route::post('/transfer', [App\Http\Controllers\TransferOrderController::class, 'store'])->middleware('throttle:10,1')->name('transfer.store');
Route::get('/transfer/confirmation', [App\Http\Controllers\TransferOrderController::class, 'confirmation'])->name('transfer.confirmation');
Route::get('/customer/login', [App\Http\Controllers\CustomerAuthController::class, 'loginForm'])->name('customer.login');
Route::post('/customer/login', [App\Http\Controllers\CustomerAuthController::class, 'login'])->middleware('throttle:10,1')->name('customer.login.submit');
Route::post('/customer/password/setup', [App\Http\Controllers\CustomerAuthController::class, 'requestSetup'])->middleware('throttle:5,1')->name('customer.password.request');
Route::get('/customer/password/setup/{token}', [App\Http\Controllers\CustomerAuthController::class, 'setupForm'])->name('customer.password.form');
Route::post('/customer/password/confirm', [App\Http\Controllers\CustomerAuthController::class, 'setupPassword'])->middleware('throttle:10,1')->name('customer.password.setup');
Route::get('/customer/verify-email', [App\Http\Controllers\CustomerAuthController::class, 'verificationNotice'])->middleware(EnsureCustomerSignedIn::class)->name('customer.verify.notice');
Route::post('/customer/verify-email', [App\Http\Controllers\CustomerAuthController::class, 'sendVerification'])->middleware([EnsureCustomerSignedIn::class, 'throttle:6,1'])->name('customer.verify.send');
Route::get('/customer/verify-email/{id}/{hash}', [App\Http\Controllers\CustomerAuthController::class, 'verifyEmail'])->middleware([EnsureCustomerSignedIn::class, 'signed', 'throttle:6,1'])->name('verification.verify');
Route::get('/customer/bookings', [App\Http\Controllers\CustomerBookingController::class, 'dashboard'])->middleware(EnsureVerifiedCustomer::class)->name('customer.dashboard');
Route::put('/customer/password', [App\Http\Controllers\CustomerAuthController::class, 'changePassword'])->middleware([EnsureVerifiedCustomer::class, 'throttle:10,1'])->name('customer.password.change');
Route::get('/r/{token}', [App\Http\Controllers\PageController::class, 'openReviewLink'])
    ->where('token', '[A-Za-z0-9]{22}')->name('review_links.open');

Route::get('/checkout/confirmation', [App\Http\Controllers\TourCheckoutController::class, 'confirmation'])->name('checkout.confirmation');
Route::get('/checkout/{type}/{id}', [App\Http\Controllers\TourCheckoutController::class, 'show'])->whereNumber('id')->name('checkout.show');
Route::post('/checkout/{type}/{id}', [App\Http\Controllers\TourCheckoutController::class, 'store'])->whereNumber('id')->middleware('throttle:10,1')->name('checkout.store');



Route::get('/lang', [App\Http\Controllers\LocalizationController::class, 'lang'])->name('lang');
Route::get('/currency', [App\Http\Controllers\LocalizationController::class, 'currency'])->name('currency');

Route::prefix('{locale}')->where(['locale' => 'en|id'])->group(function () {
    Route::get('/', [App\Http\Controllers\bookingController::class , 'home'])->name('home');
    Route::get('/about-us', function () { return view('pages.about'); })->name('about-us');
    Route::get('/transfer', [App\Http\Controllers\TransferOrderController::class, 'show'])->name('transfer');
    Route::get('/events', [App\Http\Controllers\bookingController::class , 'event'])->name('events');
    Route::get('/tour_packages', [App\Http\Controllers\bookingController::class , 'tour'])->name('tour_packages');
    Route::get('/tour_packages/{slug}', [App\Http\Controllers\bookingController::class , 'tourDetail'])->name('tour_packages.detail');
    Route::get('/mix-detail/{slug}', [App\Http\Controllers\bookingController::class , 'mixDetail'])->name('mix_packages.detail');
    Route::get('/destinations', [App\Http\Controllers\bookingController::class , 'destination'])->name('destinations');
    Route::get('/destinations/{slug}', [App\Http\Controllers\bookingController::class , 'destinationDetail'])->name('destinations.detail');
    Route::get('/activities', [App\Http\Controllers\bookingController::class , 'activity'])->name('activities');
    Route::get('/activities/{slug}', [App\Http\Controllers\bookingController::class , 'activityDetail'])->name('activities.detail');
});

Route::get('/tour_packages/{slug}/review', [App\Http\Controllers\bookingController::class, 'tourDetail'])->middleware('signed')->name('tour_reviews.form');
Route::post('/tour_packages/{slug}/review', [App\Http\Controllers\bookingController::class, 'tourReviewStore'])->middleware(['signed', 'throttle:10,1'])->name('tour_reviews.store');
Route::get('/mix-detail/{slug}/review', [App\Http\Controllers\bookingController::class, 'mixDetail'])->middleware('signed')->name('product_reviews.form');
Route::post('/mix-detail/{slug}/review', [App\Http\Controllers\bookingController::class, 'productReviewStore'])->middleware(['signed', 'throttle:10,1'])->name('product_reviews.store');

foreach ([
    '/' => 'home', '/about-us' => 'about-us', '/transfer' => 'transfer',
    '/events' => 'events', '/transport' => 'transport', '/tour_packages' => 'tour_packages',
    '/service' => 'service', '/mix-packages' => 'mix_packages', '/destinations' => 'destinations',
    '/activities' => 'activities', '/tour_packages/{slug}' => 'tour_packages.detail',
    '/mix-detail/{slug}' => 'mix_packages.detail', '/destinations/{slug}' => 'destinations.detail',
    '/activities/{slug}' => 'activities.detail',
] as $path => $name) {
    Route::get($path, App\Http\Controllers\LegacyPublicRedirectController::class)->name('legacy.'.$name);
}

// Route::post('/contact-us', ['App\Http\Controllers\ContactUsController', 'send'])->name('contact.send');
Route::post('/contact-us', [App\Http\Controllers\ContactUsController::class, 'send'])->name('contact_us');
Route::post('/review-store', [App\Http\Controllers\bookingController::class, 'reviewstore'])->name('review.store');

//================paypal
// Route::get('paypal', [App\Http\Controllers\PayPalController::class, 'index'])->name('paypal');
// Route::get('paypal/payment', [App\Http\Controllers\PayPalController::class, 'payment'])->name('paypal.payment');
// Route::get('paypal/payment/success', [App\Http\Controllers\PayPalController::class, 'paymentSuccess'])->name('paypal.payment.success');
// Route::get('paypal/payment/cancel', [App\Http\Controllers\PayPalController::class, 'paymentCancel'])->name('paypal.payment/cancel');

//====tess
// Route::get('display-user', [App\Http\Controllers\bookingController::class, 'getLoc']);
// Route::post('get-product', [App\Http\Controllers\bookingController::class, 'getProduct'])->name('get.product');
// Route::post('donation/pay', [App\Http\Controllers\OrderController::class, 'pay'])->name('donation.pay');
//=====callback
// Route::post('/midtrans-status', [App\Http\Controllers\callbackController::class, 'midtrans']);
// Route::post('/book-status', [App\Http\Controllers\callbackController::class, 'suksesPayment']);
// Route::post('/paypal-callback', [App\Http\Controllers\callbackController::class, 'paypalComplete']);


Route::get('/sitemap', fn () => \App\Support\PublicSitemap::generate())->name('sitemap');

//======================guest
Route::post('guest-order', [App\Http\Controllers\backendController::class, 'guestOrder'])->middleware(EnsureVerifiedCustomer::class)->name('guest.order');
Route::post('guest-login', [App\Http\Controllers\backendController::class, 'guestLogin'])->name('guest.login');

Route::group(['prefix' => 'laravel-filemanager', 'middleware' => ['web', 'auth', EnsureAdministrator::class]], function () {
    \UniSharp\LaravelFilemanager\Lfm::routes();
});

Auth::routes();
Route::group(['middleware' => ['auth', EnsureAdministrator::class]], function() {
    


    // your routes
    Route::get('rates', ['as' => 'pages.rates', 'uses' => 'App\Http\Controllers\PageController@rates']);
    Route::get('orders', [App\Http\Controllers\PageController::class, 'orders'])->name('pages.orders');
    Route::get('transfer-orders', [App\Http\Controllers\TransferOrderController::class, 'index'])->name('pages.transfer-order');
    Route::get('orders/data', [App\Http\Controllers\PageController::class, 'ordersData'])->name('pages.orders.data');
    Route::get('orders/{order}', [App\Http\Controllers\PageController::class, 'orderDetail'])->whereNumber('order')->name('pages.orders.detail');
    Route::put('orders/{order}', [App\Http\Controllers\PageController::class, 'orderUpdate'])->whereNumber('order')->name('pages.orders.update');
    Route::post('orders/{order}/review-link', [App\Http\Controllers\PageController::class, 'orderReviewLink'])->whereNumber('order')->name('pages.orders.review-link');
    Route::delete('orders/{order}/reviews/{review}', [App\Http\Controllers\PageController::class, 'orderReviewDelete'])->whereNumber('order')->whereNumber('review')->name('pages.orders.reviews.delete');
    Route::delete('orders/{order}', [App\Http\Controllers\PageController::class, 'orderDelete'])->whereNumber('order')->name('pages.orders.delete');
    Route::get('tour', ['as' => 'pages.tour', 'uses' => 'App\Http\Controllers\PageController@tour']);
    Route::get('destinasi', ['as' => 'pages.destinasi', 'uses' => 'App\Http\Controllers\PageController@destinasi']);
    // Route::get('activity', ['as' => 'pages.activity', 'uses' => 'App\Http\Controllers\PageController@activity']);
    Route::get('activity/{slug}', [App\Http\Controllers\PageController::class, 'activity'])->name('pages.activity');
    Route::get('products/{slug}', ['as' => 'pages.products', 'uses' => 'App\Http\Controllers\PageController@products']);
    //=========tour
    Route::get('tour-add', ['as' => 'pages.tour_add', 'uses' => 'App\Http\Controllers\PageController@tourAdd']);
    Route::post('tour/media', [App\Http\Controllers\backendController::class, 'storeMediaTour'])->name('tour.storeMedia');
    Route::post('tour/media/delete', [App\Http\Controllers\backendController::class, 'deleteMediaTour'])->name('tour.deleteMedia');
    Route::post('tour-store', [App\Http\Controllers\backendController::class, 'storeTour'])->name('tour.store');
    Route::get('tour-edit/{tour_code}', [App\Http\Controllers\backendController::class, 'editTour'])->name('tour.edit');
    Route::get('tour-review-link/{slug}', [App\Http\Controllers\bookingController::class, 'tourReviewLinkForm'])->name('tour_reviews.link');
    Route::post('tour-review-link/{slug}', [App\Http\Controllers\bookingController::class, 'tourReviewLinkCreate'])->name('tour_reviews.link.create');
    //=========destinasi
    Route::get('destinasi-add', ['as' => 'pages.destinasi_add', 'uses' => 'App\Http\Controllers\PageController@destinasiAdd']);
    Route::post('destinasi/media', [App\Http\Controllers\backendController::class, 'storeMediaDestinasi'])->name('destinasi.storeMedia');
    Route::post('destinasi/media/delete', [App\Http\Controllers\backendController::class, 'deleteMediaDestinasi'])->name('destinasi.deleteMedia');
    Route::post('destinasi-store', [App\Http\Controllers\backendController::class, 'storeDestinasi'])->name('destinasi.store');
    Route::get('destinasi-edit', [App\Http\Controllers\backendController::class, 'editDestinasi'])->name('destinasi.edit');
    //=========bali aktiviti
    Route::get('activity-add', ['as' => 'pages.activity_add', 'uses' => 'App\Http\Controllers\PageController@activityAdd']);
    Route::post('activity/media', [App\Http\Controllers\backendController::class, 'storeMediaActivity'])->name('activity.storeMedia');
    Route::post('activity/media/delete', [App\Http\Controllers\backendController::class, 'deleteMediaActivity'])->name('activity.deleteMedia');
    Route::post('activity-store', [App\Http\Controllers\backendController::class, 'storeActivity'])->name('activity.store');
    Route::get('activity-edit/{activity_code}', [App\Http\Controllers\backendController::class, 'editActivity'])->name('activity.edit');

    //=========bali products
    Route::get('products-add', ['as' => 'pages.products_add', 'uses' => 'App\Http\Controllers\PageController@productsAdd']);
    Route::post('products/media', [App\Http\Controllers\backendController::class, 'storeMediaProducts'])->name('products.storeMedia');
    Route::post('products/media/delete', [App\Http\Controllers\backendController::class, 'deleteMediaProducts'])->name('products.deleteMedia');
    Route::post('products-store', [App\Http\Controllers\backendController::class, 'storeProducts'])->name('products.store');
    Route::get('products-edit/{product_code}', [App\Http\Controllers\backendController::class, 'editProducts'])->name('products.edit');

    Route::get('/dashboard', [App\Http\Controllers\HomeController::class, 'index'])->name('dashboard');
    Route::get('profile', ['as' => 'profile.edit', 'uses' => 'App\Http\Controllers\ProfileController@edit']);
    Route::put('profile', ['as' => 'profile.update', 'uses' => 'App\Http\Controllers\ProfileController@update']);
    Route::put('profile/password', ['as' => 'profile.password', 'uses' => 'App\Http\Controllers\ProfileController@password']);
    Route::resource('user', 'App\Http\Controllers\UserController', ['except' => ['show', 'register']]);
    Route::get('maps', ['as' => 'pages.maps', 'uses' => 'App\Http\Controllers\PageController@maps']);
    Route::get('notifications', ['as' => 'pages.notifications', 'uses' => 'App\Http\Controllers\PageController@notifications']);
});
