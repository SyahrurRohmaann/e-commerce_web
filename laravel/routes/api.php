<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ProductDetailController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/catalog', [CatalogController::class, 'index']);
Route::get('/catalog/{id}', [ProductDetailController::class, 'show']);

Route::post('/webhook/xendit', [\App\Http\Controllers\WebhookController::class, 'handle']);
Route::post('/checkout', [\App\Http\Controllers\CheckoutController::class, 'store']);
Route::get('/transactions/guest/{id}', [TransactionController::class, 'showGuest']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware(['isAdmin'])->prefix('admin')->group(function () {
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('products', ProductController::class);
        Route::get('transactions', [TransactionController::class, 'index']);
        Route::get('transactions/{transaction}', [TransactionController::class, 'show']);
        Route::put('transactions/{transaction}/status', [TransactionController::class, 'updateStatus']);
    });
});
