<?php

use App\Modules\Cart\Controllers\CartController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Cart Routes
| Module: Cart
|--------------------------------------------------------------------------
*/

Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'show']);
    Route::post('/items', [CartController::class, 'addItem']);
    Route::patch('/items/{itemId}', [CartController::class, 'updateItem'])->whereNumber('itemId');
    Route::delete('/items/{itemId}', [CartController::class, 'removeItem'])->whereNumber('itemId');
    Route::delete('/', [CartController::class, 'clear']);
});
