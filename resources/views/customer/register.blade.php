@extends('layouts.app')

@section('title', 'Đăng ký tài khoản - TechShop')

@push('styles')
<link rel="stylesheet" href="/assets/css/customer-pages.css?v=2.1">
@endpush

@section('content')
<div class="customer-page-wrapper py-5">
    <div class="auth-container">
        <div class="auth-card">
            <div class="text-center mb-4">
                <a href="/" class="auth-logo">Tech<span>Shop</span></a>
                <h2 class="h4 fw-bold mb-1 text-dark">Tạo tài khoản mới</h2>
                <p class="text-muted small">Trở thành thành viên TechShop để nhận nhiều ưu đãi</p>
            </div>

            <!-- Alert lỗi nếu có -->
            <div id="auth-alert" class="alert alert-danger d-none py-2 px-3 small rounded-3 border-0" role="alert"></div>

            <form id="register-form" novalidate>
                <div class="mb-3">
                    <label for="reg-fullname" class="form-label fw-semibold small text-dark">Họ và tên <span class="text-danger">*</span></label>
                    <input type="text" class="form-control ts-form-control" id="reg-fullname" name="fullname" placeholder="Nguyễn Văn A" required maxlength="150" autocomplete="name">
                    <div class="invalid-feedback">Vui lòng nhập họ và tên của bạn.</div>
                </div>

                <div class="mb-3">
                    <label for="reg-email" class="form-label fw-semibold small text-dark">Địa chỉ Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control ts-form-control" id="reg-email" name="email" placeholder="name@example.com" required maxlength="320" autocomplete="email">
                    <div class="invalid-feedback">Vui lòng nhập địa chỉ email hợp lệ.</div>
                </div>

                <div class="mb-3">
                    <label for="reg-phone" class="form-label fw-semibold small text-dark">Số điện thoại (tùy chọn)</label>
                    <input type="tel" class="form-control ts-form-control" id="reg-phone" name="phone" placeholder="0912345678" maxlength="20" autocomplete="tel">
                    <div class="invalid-feedback">Số điện thoại không hợp lệ.</div>
                </div>

                <div class="mb-3">
                    <label for="reg-password" class="form-label fw-semibold small text-dark">Mật khẩu <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" class="form-control ts-form-control" id="reg-password" name="password" placeholder="Tối thiểu 8 ký tự" required minlength="8" autocomplete="new-password">
                        <button class="btn btn-outline-secondary btn-toggle-pwd border-start-0" type="button" data-target="reg-password" title="Ẩn/Hiện mật khẩu" style="border-color: #cbd5e1; border-top-right-radius: 10px; border-bottom-right-radius: 10px;">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                    <div class="form-text small" style="font-size: 11px;">Mật khẩu phải chứa ít nhất 8 ký tự.</div>
                    <div class="invalid-feedback">Mật khẩu tối thiểu 8 ký tự.</div>
                </div>

                <div class="mb-4">
                    <label for="reg-password-confirmation" class="form-label fw-semibold small text-dark">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" class="form-control ts-form-control" id="reg-password-confirmation" name="password_confirmation" placeholder="Nhập lại mật khẩu" required autocomplete="new-password">
                        <button class="btn btn-outline-secondary btn-toggle-pwd border-start-0" type="button" data-target="reg-password-confirmation" title="Ẩn/Hiện mật khẩu" style="border-color: #cbd5e1; border-top-right-radius: 10px; border-bottom-right-radius: 10px;">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback">Xác nhận mật khẩu không khớp.</div>
                </div>

                <button id="btn-register-submit" type="submit" class="btn btn-auth-submit w-100">
                    <i class="fa-solid fa-user-plus me-2"></i> Đăng ký tài khoản
                </button>
            </form>

            <div class="text-center mt-4 pt-3 border-top" style="border-color: #f1f5f9;">
                <span class="text-muted small">Đã có tài khoản?</span>
                <a href="/login" class="fw-bold text-primary text-decoration-none ms-1 small">Đăng nhập tại đây</a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="/assets/js/pages/auth.js"></script>
@endpush
