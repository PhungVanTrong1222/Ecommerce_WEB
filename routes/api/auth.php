<?php

use App\Modules\Identity\Controllers\AuthController;
use App\Modules\Identity\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication & Identity Routes
| Module: Identity
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('guest');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware(['guest', 'throttle:5,1']);

    Route::post('/logout', [AuthController::class, 'logout'])
        ->middleware('auth:sanctum');

    Route::get('/me', [AuthController::class, 'me'])
        ->middleware(['auth:sanctum', 'EnsureUserActive']);

    Route::post('/change-password', [AuthController::class, 'changePassword'])
        ->middleware(['auth:sanctum', 'EnsureUserActive']);
});

Route::patch('/profile', [ProfileController::class, 'update'])
    ->middleware(['auth:sanctum', 'EnsureUserActive']);
