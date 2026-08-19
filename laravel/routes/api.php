<?php

use App\Http\Controllers\Api\AnnouncementBarController;
use App\Http\Controllers\Api\HeroBannerController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductDetailController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth.register');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth.login');

Route::get('/catalog', [CatalogController::class, 'index'])->middleware('throttle:catalog');
Route::get('/catalog/{id}', [ProductDetailController::class, 'show'])->middleware('throttle:catalog');
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/hero-banners', [HeroBannerController::class, 'index']);
Route::get('/announcements', [AnnouncementBarController::class, 'index']);

Route::post('/webhook/xendit', [WebhookController::class, 'handle'])->middleware('throttle:webhook.xendit');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:checkout');
Route::get('/transactions/guest/{id}', [TransactionController::class, 'showGuest'])->middleware('throttle:guest-tracking');
Route::get('/transactions/track', [TransactionController::class, 'trackGuest'])->middleware('throttle:guest-tracking');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::get('/profile/transactions', [TransactionController::class, 'userTransactions']);
    Route::get('/transactions/{transaction}', [TransactionController::class, 'showUser']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware(['isAdmin'])->prefix('admin')->group(function () {
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('products', ProductController::class);
        Route::get('transactions', [TransactionController::class, 'index']);
        Route::get('transactions/{transaction}', [TransactionController::class, 'show']);
        Route::put('transactions/{transaction}/status', [TransactionController::class, 'updateStatus']);

        Route::get('hero-banners', [HeroBannerController::class, 'adminIndex']);
        Route::post('hero-banners', [HeroBannerController::class, 'store']);
        Route::get('hero-banners/{id}', [HeroBannerController::class, 'show']);
        Route::put('hero-banners/{id}', [HeroBannerController::class, 'update']);
        Route::delete('hero-banners/{id}', [HeroBannerController::class, 'destroy']);

        Route::get('announcements', [AnnouncementBarController::class, 'adminIndex']);
        Route::post('announcements', [AnnouncementBarController::class, 'store']);
        Route::put('announcements/{id}', [AnnouncementBarController::class, 'update']);
        Route::delete('announcements/{id}', [AnnouncementBarController::class, 'destroy']);
    });
});
