<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

// Auth (Sanctum cookie-based for SPA)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);
Route::post('/logout',   [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);

    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);

    // Orders
    Route::get('/orders', [OrderController::class, 'index']);              // open orders for orderbook
    Route::post('/orders', [OrderController::class, 'store']);             // create limit order
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']); // cancel order

    Route::get('/my-orders', [OrderController::class, 'userOrders']);      // user’s own orders
});
