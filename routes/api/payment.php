<?php

use App\Modules\Payment\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Payment Routes
| Module: Payment
|--------------------------------------------------------------------------
*/

Route::prefix('payments')->group(function () {
    Route::post('/vnpay/create', [PaymentController::class, 'createVnPay'])->middleware(['auth:sanctum', 'active']);
    Route::get('/vnpay/return', [PaymentController::class, 'vnpayReturn']);
    Route::match(['get', 'post'], '/vnpay/ipn', [PaymentController::class, 'vnpayIpn']);
});
