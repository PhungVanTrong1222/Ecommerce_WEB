<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (MPA View Rendering)
|--------------------------------------------------------------------------
|
| Các tuyến đường giao diện cho Customer và Admin.
| Dữ liệu động và nghiệp vụ sẽ được xử lý qua REST API bằng Vanilla JS.
|
*/

// ==========================================
// 1. CỬA HÀNG & KHÁCH HÀNG (CUSTOMER ROUTES)
// ==========================================

// Trang chủ
Route::get('/', function () {
    return view('customer.home');
})->name('home');

// Danh sách sản phẩm, lọc và tìm kiếm
Route::get('/products', function () {
    return view('customer.products');
})->name('products.index');

// Chi tiết sản phẩm theo slug
Route::get('/products/{slug}', function (string $slug) {
    return view('customer.product-detail', ['slug' => $slug]);
})->name('products.show');

// Giỏ hàng
Route::get('/cart', function () {
    return view('customer.cart');
})->name('cart');

// Đặt hàng / Thanh toán
Route::get('/checkout', function () {
    return view('customer.checkout');
})->name('checkout');

// Kết quả thanh toán VNPay trả về (phải đặt trước /orders/{orderNumber})
Route::get('/orders/return', function () {
    return view('customer.order-return');
})->name('orders.return');

// Chi tiết đơn hàng theo mã đơn
Route::get('/orders/{orderNumber}', function (string $orderNumber) {
    return view('customer.order-detail', ['orderNumber' => $orderNumber]);
})->name('orders.show');

// Đăng nhập
Route::get('/login', function () {
    return view('customer.login');
})->name('login');

// Đăng ký tài khoản
Route::get('/register', function () {
    return view('customer.register');
})->name('register');

// Tài khoản cá nhân & lịch sử đơn hàng
Route::get('/account', function () {
    return view('customer.account');
})->name('account');


// ==========================================
// 2. QUẢN TRỊ VIÊN (ADMIN ROUTES)
// ==========================================

Route::prefix('admin')->name('admin.')->group(function () {
    // Tổng quan Dashboard
    Route::get('/', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    // Quản lý Danh mục
    Route::get('/categories', function () {
        return view('admin.categories');
    })->name('categories');

    // Quản lý Sản phẩm, Biến thể & Hình ảnh
    Route::get('/products', function () {
        return view('admin.products');
    })->name('products');

    // Quản lý Kho hàng & Điều chỉnh tồn kho
    Route::get('/inventory', function () {
        return view('admin.inventory');
    })->name('inventory');

    // Quản lý Đơn hàng
    Route::get('/orders', function () {
        return view('admin.orders');
    })->name('orders');

    // Quản lý Giao dịch thanh toán
    Route::get('/payments', function () {
        return view('admin.payments');
    })->name('payments');

    // Quản lý Người dùng & Phân quyền
    Route::get('/users', function () {
        return view('admin.users');
    })->name('users');
});
