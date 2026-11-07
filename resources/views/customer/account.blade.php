@extends('layouts.app')

@section('title', 'Tài khoản của tôi - TechShop')

@push('styles')
<link rel="stylesheet" href="/assets/css/customer-pages.css">
@endpush

@section('content')
<div class="customer-page-wrapper py-4">
    <!-- Breadcrumb chuẩn TechShop -->
    <div class="breadcrumb">
        <a href="/"><i class="fa-solid fa-house"></i> Trang chủ</a>
        <span>/</span>
        <strong>Tài khoản</strong>
    </div>

    <!-- Loading toàn trang -->
    <div id="account-loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Đang tải...</span>
        </div>
        <p class="text-muted mt-2 small">Đang tải thông tin tài khoản...</p>
    </div>

    <!-- Main Content -->
    <div id="account-content" class="row g-4 d-none">
        <!-- Sidebar bên trái -->
        <div class="col-lg-3">
            <div class="account-sidebar shadow-sm mb-4">
                <div class="text-center p-4 bg-white border-bottom">
                    <div class="account-avatar">
                        <span id="user-avatar-text">U</span>
                    </div>
                    <h5 id="user-display-name" class="fw-bold mb-1 text-dark fs-6">--</h5>
                    <p id="user-display-email" class="text-muted small mb-1 text-truncate">--</p>
                    <span id="user-display-role" class="badge bg-secondary-subtle text-secondary border">Khách hàng</span>
                </div>
                <div class="py-2 bg-white">
                    <a href="#orders" class="account-nav-item active tab-nav-link" data-tab="orders">
                        <i class="fa-solid fa-bag-shopping"></i> Đơn hàng của tôi
                    </a>
                    <a href="#profile" class="account-nav-item tab-nav-link" data-tab="profile">
                        <i class="fa-solid fa-user"></i> Thông tin cá nhân
                    </a>
                    <a href="#password" class="account-nav-item tab-nav-link" data-tab="password">
                        <i class="fa-solid fa-key"></i> Đổi mật khẩu
                    </a>
                    <div class="border-top my-2" style="border-color: #f1f5f9;"></div>
                    <button type="button" id="btn-logout" class="account-nav-item text-danger border-0 w-100 bg-transparent text-start">
                        <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
                    </button>
                </div>
            </div>
        </div>

        <!-- Panel nội dung bên phải -->
        <div class="col-lg-9">
            <!-- Tab 1: Đơn hàng của tôi -->
            <div id="pane-orders" class="tab-pane-content">
                <div class="ts-card mb-4">
                    <div class="ts-card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 fw-bold text-dark">Lịch sử đơn hàng</h5>
                    </div>
                    <div class="ts-card-body p-4">
                        <!-- Bộ lọc trạng thái đơn hàng (Pill Tabs) -->
                        <div class="order-filter-pills" id="order-status-filters">
                            <button type="button" class="order-filter-pill active" data-status="">Tất cả</button>
                            <button type="button" class="order-filter-pill" data-status="Pending">Chờ xử lý</button>
                            <button type="button" class="order-filter-pill" data-status="Confirmed">Đã xác nhận</button>
                            <button type="button" class="order-filter-pill" data-status="Processing">Đang đóng gói</button>
                            <button type="button" class="order-filter-pill" data-status="Shipped">Đang giao</button>
                            <button type="button" class="order-filter-pill" data-status="Delivered">Đã giao</button>
                            <button type="button" class="order-filter-pill" data-status="Cancelled">Đã hủy</button>
                        </div>

                        <!-- Loading Orders -->
                        <div id="orders-loading" class="text-center py-4 d-none">
                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            <span class="ms-2 text-muted small">Đang tải danh sách đơn hàng...</span>
                        </div>

                        <!-- Empty Orders -->
                        <div id="orders-empty" class="text-center py-5 d-none">
                            <i class="fa-solid fa-box-open text-muted display-4 d-block mb-3" style="opacity: 0.4;"></i>
                            <h6 class="fw-bold text-dark">Không tìm thấy đơn hàng nào</h6>
                            <p class="text-muted small mb-3">Bạn chưa có đơn hàng nào thuộc trạng thái này.</p>
                            <a href="/" class="btn btn-outline-primary btn-sm px-4 rounded-pill">
                                <i class="fa-solid fa-cart-shopping me-1"></i> Mua sắm ngay
                            </a>
                        </div>

                        <!-- Orders List Container -->
                        <div id="orders-list" class="d-flex flex-column gap-3">
                            <!-- Render bằng JavaScript -->
                        </div>

                        <!-- Phân trang -->
                        <nav id="orders-pagination" class="mt-4 d-none" aria-label="Page navigation">
                            <ul class="pagination justify-content-center mb-0" id="pagination-ul"></ul>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Thông tin cá nhân -->
            <div id="pane-profile" class="tab-pane-content d-none">
                <div class="ts-card mb-4">
                    <div class="ts-card-header">
                        <h5 class="card-title mb-0 fw-bold text-dark">Thông tin cá nhân</h5>
                    </div>
                    <div class="ts-card-body p-4">
                        <form id="profile-form" novalidate style="max-width: 640px;">
                            <div class="mb-3">
                                <label for="prof-fullname" class="form-label fw-semibold small text-dark">Họ và tên <span class="text-danger">*</span></label>
                                <input type="text" class="form-control ts-form-control" id="prof-fullname" name="fullname" required maxlength="150">
                                <div class="invalid-feedback">Họ và tên không được để trống.</div>
                            </div>

                            <div class="mb-3">
                                <label for="prof-email" class="form-label fw-semibold small text-dark">Địa chỉ Email</label>
                                <input type="email" class="form-control ts-form-control bg-light" id="prof-email" name="email" readonly disabled>
                                <div class="form-text small" style="font-size: 11px;">Địa chỉ email dùng để đăng nhập và không thể thay đổi.</div>
                            </div>

                            <div class="mb-3">
                                <label for="prof-phone" class="form-label fw-semibold small text-dark">Số điện thoại</label>
                                <input type="tel" class="form-control ts-form-control" id="prof-phone" name="phone" placeholder="0912345678" maxlength="20">
                            </div>

                            <div class="mb-4">
                                <label for="prof-address" class="form-label fw-semibold small text-dark">Địa chỉ nhận hàng mặc định</label>
                                <textarea class="form-control ts-form-control" id="prof-address" name="address" rows="3" placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành phố" maxlength="500"></textarea>
                            </div>

                            <button type="submit" id="btn-save-profile" class="btn btn-primary px-4 py-2 fw-semibold rounded-3 shadow-sm">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Lưu thay đổi
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Đổi mật khẩu -->
            <div id="pane-password" class="tab-pane-content d-none">
                <div class="ts-card mb-4">
                    <div class="ts-card-header">
                        <h5 class="card-title mb-0 fw-bold text-dark">Đổi mật khẩu</h5>
                    </div>
                    <div class="ts-card-body p-4">
                        <form id="password-form" novalidate style="max-width: 480px;">
                            <div class="mb-3">
                                <label for="pwd-current" class="form-label fw-semibold small text-dark">Mật khẩu hiện tại <span class="text-danger">*</span></label>
                                <input type="password" class="form-control ts-form-control" id="pwd-current" name="current_password" required autocomplete="current-password">
                                <div class="invalid-feedback">Vui lòng nhập mật khẩu hiện tại.</div>
                            </div>

                            <div class="mb-3">
                                <label for="pwd-new" class="form-label fw-semibold small text-dark">Mật khẩu mới <span class="text-danger">*</span></label>
                                <input type="password" class="form-control ts-form-control" id="pwd-new" name="new_password" required minlength="8" autocomplete="new-password">
                                <div class="form-text small" style="font-size: 11px;">Mật khẩu mới phải có tối thiểu 8 ký tự.</div>
                                <div class="invalid-feedback">Mật khẩu mới phải có ít nhất 8 ký tự.</div>
                            </div>

                            <div class="mb-4">
                                <label for="pwd-confirm" class="form-label fw-semibold small text-dark">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                                <input type="password" class="form-control ts-form-control" id="pwd-confirm" name="new_password_confirmation" required autocomplete="new-password">
                                <div class="invalid-feedback">Xác nhận mật khẩu mới không khớp.</div>
                            </div>

                            <button type="submit" id="btn-save-password" class="btn btn-primary px-4 py-2 fw-semibold rounded-3 shadow-sm">
                                <i class="fa-solid fa-key me-1"></i> Cập nhật mật khẩu
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="/assets/js/pages/account.js"></script>
@endpush
