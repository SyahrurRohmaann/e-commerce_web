<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ProductDetailController;
use App\Http\Controllers\Api\HeroBannerController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/catalog', [CatalogController::class, 'index']);
Route::get('/catalog/{id}', [ProductDetailController::class, 'show']);
Route::get('/hero-banners', [HeroBannerController::class, 'index']);

Route::post('/webhook/xendit', [\App\Http\Controllers\WebhookController::class, 'handle']);
Route::post('/checkout', [\App\Http\Controllers\CheckoutController::class, 'store']);
Route::get('/transactions/guest/{id}', [TransactionController::class, 'showGuest']);
Route::get('/transactions/track', [TransactionController::class, 'trackGuest']);

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
    });
});
