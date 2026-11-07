@extends('layouts.app')

@section('title', 'Đăng nhập tài khoản - TechShop')

@push('styles')
<link rel="stylesheet" href="/assets/css/customer-pages.css">
@endpush

@section('content')
<div class="customer-page-wrapper py-5">
    <div class="auth-container">
        <div class="auth-card">
            <div class="text-center mb-4">
                <a href="/" class="auth-logo">Tech<span>Shop</span></a>
                <h2 class="h4 fw-bold mb-1 text-dark">Đăng nhập tài khoản</h2>
                <p class="text-muted small">Chào mừng bạn quay trở lại với TechShop</p>
            </div>

            <!-- Alert lỗi nếu có -->
            <div id="auth-alert" class="alert alert-danger d-none py-2 px-3 small rounded-3 border-0" role="alert"></div>

            <form id="login-form" novalidate>
                <div class="mb-3">
                    <label for="login-email" class="form-label fw-semibold small text-dark">Địa chỉ Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control ts-form-control" id="login-email" name="email" placeholder="name@example.com" required autocomplete="email">
                    <div class="invalid-feedback">Vui lòng nhập địa chỉ email hợp lệ.</div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="login-password" class="form-label fw-semibold small text-dark mb-0">Mật khẩu <span class="text-danger">*</span></label>
                    </div>
                    <div class="input-group">
                        <input type="password" class="form-control ts-form-control" id="login-password" name="password" placeholder="Nhập mật khẩu của bạn" required autocomplete="current-password">
                        <button class="btn btn-outline-secondary btn-toggle-pwd border-start-0" type="button" data-target="login-password" title="Ẩn/Hiện mật khẩu" style="border-color: #cbd5e1; border-top-right-radius: 10px; border-bottom-right-radius: 10px;">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback">Vui lòng nhập mật khẩu.</div>
                </div>

                <button id="btn-login-submit" type="submit" class="btn btn-auth-submit w-100 mt-2">
                    <i class="fa-solid fa-arrow-right-to-bracket me-2"></i> Đăng nhập
                </button>
            </form>

            <div class="text-center mt-4 pt-3 border-top" style="border-color: #f1f5f9;">
                <span class="text-muted small">Chưa có tài khoản?</span>
                <a href="/register" class="fw-bold text-primary text-decoration-none ms-1 small">Đăng ký ngay</a>
            </div>

            <!-- Gợi ý tài khoản demo -->
            <div class="demo-accounts-box">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">TÀI KHOẢN MẪU DÙNG THỬ</span>
                    <span class="badge bg-primary-subtle text-primary" style="font-size: 10px;">Click 1 chạm để điền</span>
                </div>
                <div class="d-grid gap-2">
                    <div class="demo-account-pill btn-fill-demo" data-email="customer@example.com" data-pwd="customer123">
                        <div>
                            <div class="fw-bold text-dark small">Khách hàng: customer@example.com</div>
                            <div class="text-muted" style="font-size: 11px;">Mật khẩu: customer123</div>
                        </div>
                        <span class="badge bg-light text-primary border">Chọn</span>
                    </div>
                    <div class="demo-account-pill btn-fill-demo" data-email="admin@techshop.vn" data-pwd="admin123">
                        <div>
                            <div class="fw-bold text-dark small">Admin: admin@techshop.vn</div>
                            <div class="text-muted" style="font-size: 11px;">Mật khẩu: admin123</div>
                        </div>
                        <span class="badge bg-light text-dark border">Chọn</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="/assets/js/pages/auth.js"></script>
@endpush
