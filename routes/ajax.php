<?php

use App\Http\Controllers\Web\Ajax\ImageController;
use App\Http\Controllers\Web\Ajax\SubcategoryController;
use App\Http\Controllers\Web\Backend\GalleryController;
use Illuminate\Support\Facades\Route;

Route::get('/subcategory/{category_id}', [SubcategoryController::class, 'index'])->name('subcategory');

Route::middleware(['auth'])->controller(ImageController::class)->prefix('image')->name('image.')->group(function () {
    Route::get('/{post_id}', 'index')->name('index');
    Route::get('/delete/{id}', 'destroy')->name('destroy');
});

Route::middleware(['auth'])->controller(GalleryController::class)->prefix('gallery')->name('gallery.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/all', 'list')->name('all');
    Route::post('/store', 'store')->name('store');
    Route::get('/destroy/{name}', 'destroy')->name('destroy');
});