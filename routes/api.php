<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Middleware\JwtAuthMiddleware;

Route::prefix('v1')->group(function () {
    Route::get('health', HealthController::class);

    Route::post('auth/login', [AuthController::class, 'login']);

    Route::middleware([JwtAuthMiddleware::class])->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);

        // products
        Route::get('products', [ProductController::class, 'index']);
        Route::post('products', [ProductController::class, 'store']);

        // customers
        Route::get('customers', [CustomerController::class, 'index']);
        Route::get('customers/{customer}/orders', [CustomerController::class, 'orders']);

        // orders
        Route::get('orders', [OrderController::class, 'index']);
        Route::post('checkout', [OrderController::class, 'checkout']);
        Route::get('orders/{order}', [OrderController::class, 'show']);
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel']);
        Route::post('orders/{order}/resync-wms', [OrderController::class, 'resyncWms']);

        // reports
        Route::get('reports/sales.csv', [ReportController::class, 'salesCsv']);
    });
});
