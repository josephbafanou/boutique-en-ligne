<?php

use App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

// --- Public -----------------------------------------------------------
Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::get('categories', [CategoryController::class, 'index']);
Route::get('products', [ProductController::class, 'index']);
Route::get('products/{product}', [ProductController::class, 'show']);

// --- Client connecté --------------------------------------------------
Route::middleware('auth:sanctum')->group(function () {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::get('cart', [CartController::class, 'index']);
    Route::post('cart/items', [CartController::class, 'store']);
    Route::patch('cart/items/{cartItem}', [CartController::class, 'update']);
    Route::delete('cart/items/{cartItem}', [CartController::class, 'destroy']);

    Route::get('orders', [OrderController::class, 'index']);
    Route::post('orders', [OrderController::class, 'store']);
    Route::get('orders/{order}', [OrderController::class, 'show']);
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel']);

    // --- Back-office --------------------------------------------------
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::post('products', [Admin\ProductController::class, 'store']);
        Route::put('products/{product}', [Admin\ProductController::class, 'update']);
        Route::delete('products/{product}', [Admin\ProductController::class, 'destroy']);

        Route::get('orders', [Admin\OrderController::class, 'index']);
        Route::patch('orders/{order}/status', [Admin\OrderController::class, 'updateStatus']);

        Route::get('reports/sales', [Admin\ReportController::class, 'sales']);
    });
});
