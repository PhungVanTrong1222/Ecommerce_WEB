<?php

use App\Modules\Catalog\Controllers\Admin\AdminCategoryController;
use App\Modules\Catalog\Controllers\Admin\AdminInventoryController;
use App\Modules\Catalog\Controllers\Admin\AdminProductController;
use App\Modules\Catalog\Controllers\Admin\AdminVariantController;
use App\Modules\Identity\Controllers\Admin\AdminUserController;
use App\Modules\Order\Controllers\Admin\AdminOrderController;
use App\Modules\Payment\Controllers\Admin\AdminPaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
| Yêu cầu auth:sanctum + active + permission admin.*
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->middleware(['auth:sanctum', 'active'])->group(function () {
    // 1. Categories Management
    Route::middleware(['permission:admin.categories'])->group(function () {
        Route::get('/categories', [AdminCategoryController::class, 'index']);
        Route::post('/categories', [AdminCategoryController::class, 'store']);
        Route::patch('/categories/{id}', [AdminCategoryController::class, 'update']);
    });

    // 2. Products Management
    Route::middleware(['permission:admin.products'])->group(function () {
        Route::get('/products', [AdminProductController::class, 'index']);
        Route::get('/products/{id}', [AdminProductController::class, 'show']);
        Route::post('/products', [AdminProductController::class, 'store']);
        Route::patch('/products/{id}', [AdminProductController::class, 'update']);
        Route::post('/products/{id}/variants', [AdminVariantController::class, 'store']);
        Route::patch('/variants/{id}', [AdminVariantController::class, 'update']);
        Route::post('/products/{id}/images', [AdminProductController::class, 'uploadImage']);
        Route::delete('/product-images/{id}', [AdminProductController::class, 'deleteImage']);
    });

    // 3. Inventory Management
    Route::middleware(['permission:admin.inventory'])->group(function () {
        Route::get('/inventory', [AdminInventoryController::class, 'index']);
        Route::post('/inventory/{variantId}/adjust', [AdminInventoryController::class, 'adjust']);
    });

    // 4. Orders Management
    Route::middleware(['permission:admin.orders'])->group(function () {
        Route::get('/orders', [AdminOrderController::class, 'index']);
        Route::get('/orders/{orderNumber}', [AdminOrderController::class, 'show']);
        Route::patch('/orders/{orderNumber}/status', [AdminOrderController::class, 'updateStatus']);
    });

    // 5. Payments Management
    Route::middleware(['permission:admin.payments'])->group(function () {
        Route::get('/payments', [AdminPaymentController::class, 'index']);
        Route::post('/payments/{orderNumber}/confirm-cod', [AdminPaymentController::class, 'confirmCod']);
    });

    // 6. Users Management
    Route::middleware(['permission:admin.users'])->group(function () {
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::patch('/users/{id}/status', [AdminUserController::class, 'updateStatus']);
    });
});
