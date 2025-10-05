<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\Frontend\HomeController;
use App\Http\Controllers\Api\Auth\SocialLoginController;
use App\Http\Controllers\Web\Frontend\ContactController;
use App\Http\Controllers\Web\Frontend\AffiliateController;
use App\Http\Controllers\Web\Frontend\SubscriberController;
use App\Http\Controllers\Api\Frontend\Product\ProductController;
use App\Http\Controllers\Api\Gateway\Stripe\StripeWebHookController;
use App\Http\Controllers\Api\Gateway\Stripe\StripeOnBoardingController;
use App\Http\Controllers\Api\Frontend\RentedPayment\RentedPaymentController;

Route::get('/',[HomeController::class, 'index'])->name('home');

Route::get('/affiliate/{slug}',[AffiliateController::class, 'store'])->name('store');

Route::get('/post',[HomeController::class, 'index'])->name('post.index');
Route::get('/post/show/{slug}',[HomeController::class, 'post'])->name('post.show');

//Social login test routes
Route::get('social-login/{provider}',[SocialLoginController::class,'RedirectToProvider'])->name('social.login');
Route::get('social-login/{provider}/callback',[SocialLoginController::class, 'HandleProviderCallback']);

Route::post('subscriber/store',[SubscriberController::class, 'store'])->name('subscriber.data.store');

Route::post('contact/store',[ContactController::class, 'store'])->name('contact.store');



Route::controller(NotificationController::class)->prefix('notification')->name('notification.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('read/single/{id}', 'readSingle')->name('read.single');
    Route::POST('read/all', 'readAll')->name('read.all');
})->middleware('auth');

require __DIR__.'/auth.php';


Route::post('/boosting/webhook', [ProductController::class, 'handleWebhook']);
Route::post('/checkout/webhook', [StripeWebHookController::class, 'handleWebhook']);

Route::post('/rental/webhook', [RentedPaymentController::class, 'handleWebhook']);


