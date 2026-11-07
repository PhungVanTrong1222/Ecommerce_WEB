<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Base path: /api
| Mỗi module có route file riêng, được include bên dưới.
|--------------------------------------------------------------------------
*/

// Identity & Auth routes
require __DIR__ . '/api/auth.php';

// Catalog routes (products, categories)
require __DIR__ . '/api/catalog.php';

// Cart routes
require __DIR__ . '/api/cart.php';

// Order routes
require __DIR__ . '/api/order.php';

// Payment routes
require __DIR__ . '/api/payment.php';

// Admin routes
require __DIR__ . '/api/admin.php';
