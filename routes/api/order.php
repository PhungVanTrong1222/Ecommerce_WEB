<?php

use App\Modules\Order\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Order Routes
| Module: Order
|--------------------------------------------------------------------------
*/

Route::prefix('orders')->middleware(['auth:sanctum', 'active'])->group(function () {
    Route::post('/checkout', [OrderController::class, 'checkout']);
    Route::get('/', [OrderController::class, 'index']);
    Route::get('/{orderNumber}', [OrderController::class, 'show']);
    Route::post('/{orderNumber}/cancel', [OrderController::class, 'cancel']);
});
