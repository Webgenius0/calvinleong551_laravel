<?php

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Web\Backend\FaqController;
use App\Http\Controllers\Web\Backend\ChatController;
use App\Http\Controllers\Web\Backend\MenuController;
use App\Http\Controllers\Web\Backend\PageController;
use App\Http\Controllers\Web\Backend\PostController;
use App\Http\Controllers\Web\Backend\ImageController;
use App\Http\Controllers\Web\Backend\OrderController;
use App\Http\Controllers\Web\Backend\BookingController;
use App\Http\Controllers\Web\Backend\ContactController;
use App\Http\Controllers\Web\Backend\ProductController;
use App\Http\Controllers\Web\Backend\CategoryController;
use App\Http\Controllers\Web\Backend\LivewireController;
use App\Http\Controllers\Web\Backend\TemplateController;
use App\Http\Controllers\Web\Backend\DashboardController;
use App\Http\Controllers\Web\Backend\SocialLinkController;
use App\Http\Controllers\Web\Backend\SubscriberController;
use App\Http\Controllers\Web\Backend\Access\RoleController;
use App\Http\Controllers\Web\Backend\Access\UserController;
use App\Http\Controllers\Web\Backend\SubcategoryController;
use App\Http\Controllers\Web\Backend\TransactionController;
use App\Http\Controllers\Web\Backend\ProductBrandController;
use App\Http\Controllers\Web\Backend\Settings\EnvController;
use App\Http\Controllers\Web\Backend\Settings\LogoController;
use App\Http\Controllers\Web\Backend\Settings\OtherController;
use App\Http\Controllers\Web\Backend\Settings\SocialController;
use App\Http\Controllers\Web\Backend\Settings\StripeController;
use App\Http\Controllers\Web\Backend\Settings\CaptchaController;
use App\Http\Controllers\Web\Backend\Settings\ProfileController;
use App\Http\Controllers\Web\Backend\Settings\SettingController;
use App\Http\Controllers\Web\Backend\Access\PermissionController;
use App\Http\Controllers\Web\Backend\Settings\FirebaseController;
use App\Http\Controllers\Web\Backend\Boosting\BoostPlanController;
use App\Http\Controllers\Web\Backend\Settings\GoogleMapController;
use App\Http\Controllers\Web\Backend\Settings\SignatureController;
use App\Http\Controllers\Web\Backend\Settings\MailSettingController;
use App\Http\Controllers\Web\Backend\Boosting\BoostingListController;
use App\Http\Controllers\Web\Backend\CMS\Web\Home\HomeAboutController;
use App\Http\Controllers\Web\Backend\CMS\Web\Home\HomeIntroController;
use App\Http\Controllers\Web\Backend\AdminRefund\AdminRefundController;
use App\Http\Controllers\Web\Backend\CMS\Web\Home\HomeBannerController;
use App\Http\Controllers\Web\Backend\CMS\Web\HowitWorks\HeroController;
use App\Http\Controllers\Web\Backend\CMS\Web\Home\HomeExampleController;
use App\Http\Controllers\Web\Backend\CMS\Web\HowitWorks\SafelyShopController;
use App\Http\Controllers\Web\Backend\ProductUploadsTips\UploadTipsController;
use App\Http\Controllers\Web\Backend\CMS\Web\HowitWorks\SimpleSellingController;
use App\Http\Controllers\Web\Backend\CMS\Web\PrivacyTerms\PrivacAndTermsController;

Route::get("dashboard", [DashboardController::class, 'index'])->name('dashboard');


/*
* CRUD
*/

Route::controller(MenuController::class)->prefix('menu')->name('menu.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/store', 'store')->name('store');
    Route::get('/show/{id}', 'show')->name('show');
    Route::get('/edit/{id}', 'edit')->name('edit');
    Route::post('/update/{id}', 'update')->name('update');
    Route::delete('/delete/{id}', 'destroy')->name('destroy');
    Route::get('/status/{id}', 'status')->name('status');
});

Route::controller(TemplateController::class)->prefix('template')->name('template.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/store', 'store')->name('store');
    Route::get('/show/{id}', 'show')->name('show');
    Route::get('/edit/{id}', 'edit')->name('edit');
    Route::post('/update/{id}', 'update')->name('update');
    Route::delete('/delete/{id}', 'destroy')->name('destroy');
    Route::get('/status/{id}', 'status')->name('status');
});



Route::controller(UploadTipsController::class)->prefix('upload-tips')->name('upload-tips.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/store', 'store')->name('store');
    Route::get('/show/{id}', 'show')->name('show');
    Route::get('/edit/{id}', 'edit')->name('edit');
    Route::post('/update/{id}', 'update')->name('update');
    Route::delete('/delete/{id}', 'destroy')->name('destroy');
    Route::get('/status/{id}', 'status')->name('status');
});
Route::controller(BoostPlanController::class)->prefix('boost-plan')->name('boost-plan.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/store', 'store')->name('store');
    Route::get('/show/{id}', 'show')->name('show');
    Route::get('/edit/{id}', 'edit')->name('edit');
    Route::post('/update/{id}', 'update')->name('update');
    Route::delete('/delete/{id}', 'destroy')->name('destroy');
    Route::get('/status/{id}', 'status')->name('status');
});
Route::controller(BoostingListController::class)->prefix('boosting')->name('boosting.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/store', 'store')->name('store');
    Route::get('/show/{id}', 'show')->name('show');
    Route::get('/edit/{id}', 'edit')->name('edit');
    Route::post('/update/{id}', 'update')->name('update');
    Route::delete('/delete/{id}', 'destroy')->name('destroy');
    Route::get('/status/{id}', 'status')->name('status');
});

Route::controller(ProductBrandController::class)->prefix('brand')->name('brand.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/store', 'store')->name('store');
    Route::get('/show/{id}', 'show')->name('show');
    Route::get('/edit/{id}', 'edit')->name('edit');
    Route::post('/update/{id}', 'update')->name('update');
    Route::delete('/delete/{id}', 'destroy')->name('destroy');
    Route::get('/status/{id}', 'status')->name('status');
});

Route::controller(ProductController::class)->prefix('product')->name('product.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/store', 'store')->name('store');
    Route::get('/show/{id}', 'show')->name('show');
    Route::get('/edit/{id}', 'edit')->name('edit');
    Route::post('/update/{id}', 'update')->name('update');
    Route::delete('/delete/{id}', 'destroy')->name('destroy');
    Route::get('/status/{id}', 'status')->name('status');
});

Route::controller(OrderController::class)->prefix('order')->name('order.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/show/{id}', 'show')->name('show');
    Route::get('/status/{id}', 'status')->name('status');
});

Route::controller(AdminRefundController::class)->prefix('admin-refund')->name('admin-refund.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/show/{id}', 'show')->name('show');
    Route::get('/status/{id}', 'status')->name('status');
});

Route::controller(BookingController::class)->prefix('booking')->name('booking.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/show/{id}', 'show')->name('show');
});

Route::controller(PostController::class)->prefix('post')->name('post.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/store', 'store')->name('store');
    Route::get('/show/{id}', 'show')->name('show');
    Route::get('/edit/{id}', 'edit')->name('edit');
    Route::post('/update/{id}', 'update')->name('update');
    Route::delete('/delete/{id}', 'destroy')->name('destroy');
    Route::get('/status/{id}', 'status')->name('status');
});

Route::controller(ImageController::class)->prefix('post/image')->name('post.image.')->group(function () {
    Route::get('/{post_id}', 'index')->name('index');
    Route::get('/delete/{id}', 'destroy')->name('destroy');
});

Route::controller(PageController::class)->prefix('page')->name('page.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/store', 'store')->name('store');
    Route::get('/edit/{id}', 'edit')->name('edit');
    Route::post('/update/{id}', 'update')->name('update');
    Route::delete('/delete/{id}', 'destroy')->name('destroy');
    Route::get('/status/{id}', 'status')->name('status');
});

Route::controller(SocialLinkController::class)->prefix('social')->name('social.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/store', 'store')->name('store');
    Route::get('/edit/{id}', 'edit')->name('edit');
    Route::post('/update/{id}', 'update')->name('update');
    Route::delete('/delete/{id}', 'destroy')->name('destroy');
    Route::get('/status/{id}', 'status')->name('status');
});

Route::controller(FaqController::class)->prefix('faq')->name('faq.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/store', 'store')->name('store');
    Route::get('/show/{id}', 'show')->name('show');
    Route::get('/edit/{id}', 'edit')->name('edit');
    Route::post('/update/{id}', 'update')->name('update');
    Route::delete('/delete/{id}', 'destroy')->name('destroy');
    Route::get('/status/{id}', 'status')->name('status');
});

Route::get('subscriber', [SubscriberController::class, 'index'])->name('subscriber.index');

Route::controller(ContactController::class)->prefix('contact')->name('contact.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/status/{id}', 'status')->name('status');
});

/*
* Transaction
*/

Route::controller(TransactionController::class)->prefix('transaction')->name('transaction.')->group(function () {
    Route::get('/{user_id?}', 'index')->name('index');
    Route::get('/show/{id}', 'show')->name('show');
});


/*
* CMS
*/

Route::prefix('cms')->name('cms.')->group(function () {

    //Home About
    Route::prefix('home/about')->name('home.about.')->controller(HomeAboutController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{id}/show', 'show')->name('show');

        Route::put('/content', 'content')->name('content');
        Route::get('/display', 'display')->name('display');
    });

    //Home Example
    Route::prefix('home/example')->name('home.example.')->controller(HomeExampleController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}/show', 'show')->name('show');
        Route::get('/{id}/edit', 'edit')->name('edit');
        Route::patch('/{id}', 'update')->name('update');
        Route::delete('/{id}', 'destroy')->name('destroy');
        Route::get('/{id}/status', 'status')->name('status');

        Route::put('/content', 'content')->name('content');
        Route::get('/display', 'display')->name('display');
    });

    //Home Intro
    Route::prefix('home/intro')->name('home.intro.')->controller(HomeIntroController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{id}/show', 'show')->name('show');

        Route::put('/content', 'content')->name('content');
        Route::get('/display', 'display')->name('display');
    });

    //Home Banner
    Route::prefix('home/banner')->name('home.banner.')->controller(HomeBannerController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{id}/show', 'show')->name('show');

        Route::put('/content', 'content')->name('content');
        Route::get('/display', 'display')->name('display');
    });

    //How It Works Hero
    Route::prefix('howitworks/hero')->name('howitworks.hero.')->controller(HeroController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{id}/show', 'show')->name('show');

        Route::put('/content', 'content')->name('content');
        Route::get('/display', 'display')->name('display');
    });

    //How It Works Simple Selling

    Route::prefix('howitworks/simple-selling')->name('howitworks.simple-selling.')->controller(SimpleSellingController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}/show', 'show')->name('show');
        Route::get('/{id}/edit', 'edit')->name('edit');
        Route::patch('/{id}', 'update')->name('update');
        Route::delete('/{id}', 'destroy')->name('destroy');
        Route::get('/{id}/status', 'status')->name('status');

        Route::put('/content', 'content')->name('content');
        Route::get('/display', 'display')->name('display');
    });

    //How It Works Safely Shop
    Route::prefix('howitworks/safely-shop')->name('howitworks.safely-shop.')->controller(SafelyShopController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}/show', 'show')->name('show');
        Route::get('/{id}/edit', 'edit')->name('edit');
        Route::patch('/{id}', 'update')->name('update');
        Route::delete('/{id}', 'destroy')->name('destroy');
        Route::get('/{id}/status', 'status')->name('status');

        Route::put('/content', 'content')->name('content');
        Route::get('/display', 'display')->name('display');
    });


    //Privacy and Terms
    Route::controller(PrivacAndTermsController::class)->prefix('privecyandterms')->name('privecyandterms.')->group(function () {
        Route::get('/terms', 'termsAndCondition')->name('terms');
        Route::get('/privacy', 'privacyPolicy')->name('privacy');
        Route::get('/why-desi-carousel', 'whyDesiCarousel')->name('why.desi.carousel');
        Route::get('/trust-and-sefty', 'trustSefty')->name('trust-and-sefty');

        Route::post('/terms-condition/update', 'termsAndConditionUpdate')->name('terms.update');
        Route::post('/privacy-policy/update', 'privacyPolicyUpdate')->name('privacy.update');
        Route::post('/why-desi-carousel/update', 'whyDesiCarouselUpdate')->name('why.desi.carousel.update');
        Route::post('/trust-and-sefty/update', 'trustAndService')->name('trust-and-sefty.update');
    });
    
});

/*
* Chating Route
*/

Route::controller(ChatController::class)->prefix('chat')->name('chat.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/list', 'list')->name('list');
    Route::post('/send/{receiver_id}', 'send')->name('send');
    Route::get('/conversation/{receiver_id}', 'conversation')->name('conversation');
    Route::get('/room/{receiver_id}', 'room');
    Route::get('/search', 'search')->name('search');
    Route::get('/seen/all/{receiver_id}', 'seenAll');
    Route::get('/seen/single/{chat_id}', 'seenSingle');
});


/*
* Users Access Route
*/

Route::resource('users', UserController::class);
Route::controller(UserController::class)->prefix('users')->name('users.')->group(function () {
    Route::get('/status/{id}', 'status')->name('status');
    Route::get('/new', 'new')->name('new.index');
    Route::get('/ajax/new/count', 'newCount')->name('ajax.new.count');
    Route::get('/card/{slug}', 'card')->name('card');
});
Route::resource('permissions', PermissionController::class);
Route::resource('roles', RoleController::class);

/*
*settings
*/

//! Route for Profile Settings
Route::controller(ProfileController::class)->group(function () {
    Route::get('setting/profile', 'index')->name('setting.profile.index');
    Route::put('setting/profile/update', 'UpdateProfile')->name('setting.profile.update');
    Route::put('setting/profile/update/Password', 'UpdatePassword')->name('setting.profile.update.Password');
    Route::post('setting/profile/update/Picture', 'UpdateProfilePicture')->name('update.profile.picture');
});

//! Route for Mail Settings
Route::controller(MailSettingController::class)->group(function () {
    Route::get('setting/mail', 'index')->name('setting.mail.index');
    Route::patch('setting/mail', 'update')->name('setting.mail.update');

    Route::post('setting/send', 'send')->name('setting.mail.send');
});

//! Route for Stripe Settings
Route::controller(StripeController::class)->prefix('setting/stripe')->name('setting.stripe.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::patch('/update', 'update')->name('update');
    Route::patch('/update-onboarding', 'updateOnboarding')->name('update-onboarding');
});

//! Route for Firebase Settings
Route::controller(FirebaseController::class)->prefix('setting/firebase')->name('setting.firebase.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::patch('/update', 'update')->name('update');
});

//! Route for Environment Settings
Route::controller(EnvController::class)->group(function () {
    Route::get('setting/env', 'index')->name('setting.env.index');
    Route::patch('setting/env', 'update')->name('setting.env.update');
});

//! Route for Firebase Settings
Route::controller(SocialController::class)->prefix('setting/social')->name('setting.social.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::patch('/update', 'update')->name('update');
});

//! Route for Stripe Settings
Route::controller(SettingController::class)->group(function () {
    Route::get('setting/general', 'index')->name('setting.general.index');
    Route::patch('setting/general', 'update')->name('setting.general.update');
});

//! Route for Logo Settings
Route::controller(LogoController::class)->group(function () {
    Route::get('setting/logo', 'index')->name('setting.logo.index');
    Route::patch('setting/logo', 'update')->name('setting.logo.update');
});

//! Route for Google Map Settings
Route::controller(GoogleMapController::class)->group(function () {
    Route::get('setting/google/map', 'index')->name('setting.google.map.index');
    Route::patch('setting/google/map', 'update')->name('setting.google.map.update');
});

//! Route for Google Map Settings
Route::controller(SignatureController::class)->group(function () {
    Route::get('setting/signature', 'index')->name('setting.signature.index');
    Route::patch('setting/signature', 'update')->name('setting.signature.update');
});

//! Route for Google Map Settings
Route::controller(CaptchaController::class)->group(function () {
    Route::get('setting/captcha', 'index')->name('setting.captcha.index');
    Route::patch('setting/captcha', 'update')->name('setting.captcha.update');
});

//Ajax settings
Route::prefix('setting/other')->name('setting.other')->group(function () {
    Route::get('/', [OtherController::class, 'index'])->name('.index');
    Route::get('/mail', [OtherController::class, 'mail'])->name('.mail');
    Route::get('/sms', [OtherController::class, 'sms'])->name('.sms');
    Route::get('/recaptcha', [OtherController::class, 'recaptcha'])->name('.recaptcha');
    Route::get('/pagination', [OtherController::class, 'pagination'])->name('.pagination');
    Route::get('/reverb', [OtherController::class, 'reverb'])->name('.reverb');
    Route::get('/debug', [OtherController::class, 'debug'])->name('.debug');
    Route::get('/access', [OtherController::class, 'access'])->name('.access');
});

//livewire
Route::prefix('livewire/crud')->name('livewire.crud')->group(function () {
    Route::get('/', [LivewireController::class, 'index'])->name('.index');
});


// Run artisan commands for optimization and cache clearing
Route::get('/optimize', function () {
    Artisan::call('optimize:clear');
    Artisan::call('config:cache');
    Redis::flushAll();
    return redirect()->back()->with('t-success', 'Message sent successfully');
})->name('optimize');

// Route::post('/boosting/webhook', [\App\Http\Controllers\Api\Frontend\Boosting\BoostingWebhookController::class, 'handleStripeWebhook'])->name('boosting.webhook');
