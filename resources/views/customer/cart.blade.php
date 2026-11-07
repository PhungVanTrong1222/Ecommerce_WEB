@extends('layouts.app')

@section('title', 'Giỏ hàng của bạn - TechShop')

@push('styles')
<link rel="stylesheet" href="/assets/css/customer-pages.css">
@endpush

@section('content')
<div class="customer-page-wrapper py-4">
    <!-- Breadcrumb chuẩn TechShop -->
    <div class="breadcrumb">
        <a href="/"><i class="fa-solid fa-house"></i> Trang chủ</a>
        <span>/</span>
        <strong>Giỏ hàng</strong>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold text-dark">Giỏ hàng của bạn</h1>
            <p class="text-muted small mb-0">Quản lý và điều chỉnh các sản phẩm trước khi thanh toán</p>
        </div>
        <span class="badge bg-light text-dark border fs-6 px-3 py-2" id="cart-item-count-text"></span>
    </div>

    <!-- Alert cảnh báo nếu có sản phẩm không khả dụng -->
    <div id="unavailable-alert" class="alert alert-warning border-0 shadow-sm d-none align-items-center mb-4 rounded-3" role="alert">
        <i class="fa-solid fa-triangle-exclamation text-warning fs-4 me-3"></i>
        <div>
            <strong class="d-block text-dark">Một số sản phẩm trong giỏ hàng hiện không khả dụng!</strong>
            <span class="small text-muted">Vui lòng điều chỉnh số lượng hoặc xóa sản phẩm bị hết hàng / ngừng kinh doanh để tiếp tục đặt hàng.</span>
        </div>
    </div>

    <!-- Loading State -->
    <div id="cart-loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Đang tải...</span>
        </div>
        <p class="text-muted mt-2 small">Đang tải dữ liệu giỏ hàng...</p>
    </div>

    <!-- Empty State -->
    <div id="cart-empty" class="text-center py-5 d-none">
        <div class="mb-4">
            <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                <i class="fa-solid fa-bag-shopping text-muted fs-1" style="opacity: 0.5;"></i>
            </div>
        </div>
        <h4 class="fw-bold text-dark mb-2">Giỏ hàng của bạn đang trống</h4>
        <p class="text-muted mb-4 small" style="max-width: 420px; margin: 0 auto;">Hãy khám phá hàng ngàn sản phẩm công nghệ chính hãng với giá ưu đãi tại TechShop ngay hôm nay!</p>
        <a href="/" class="btn btn-primary px-4 py-2 fw-semibold rounded-pill shadow-sm">
            <i class="fa-solid fa-arrow-left me-2"></i> Khám phá sản phẩm
        </a>
    </div>

    <!-- Cart Content -->
    <div id="cart-content" class="row g-4 d-none">
        <!-- Danh sách sản phẩm -->
        <div class="col-lg-8">
            <div class="ts-card mb-3">
                <div class="table-responsive">
                    <table class="table cart-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 48%;">Sản phẩm</th>
                                <th style="width: 18%;">Đơn giá</th>
                                <th style="width: 16%;" class="text-center">Số lượng</th>
                                <th style="width: 13%;" class="text-end">Thành tiền</th>
                                <th style="width: 5%;" class="text-center"></th>
                            </tr>
                        </thead>
                        <tbody id="cart-items-tbody">
                            <!-- Render bằng JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <a href="/" class="btn btn-outline-secondary btn-sm px-3 rounded-3">
                    <i class="fa-solid fa-arrow-left me-1"></i> Tiếp tục mua sắm
                </a>
                <button id="btn-clear-cart" type="button" class="btn btn-outline-danger btn-sm px-3 rounded-3">
                    <i class="fa-regular fa-trash-can me-1"></i> Xóa toàn bộ giỏ hàng
                </button>
            </div>
        </div>

        <!-- Tóm tắt đơn hàng -->
        <div class="col-lg-4">
            <div class="order-summary-card p-4 sticky-top" style="top: 90px;">
                <h5 class="fw-bold text-dark pb-3 mb-3 border-bottom">Tóm tắt đơn hàng</h5>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Tổng số lượng</span>
                    <span id="summary-total-qty" class="fw-semibold text-dark">0</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Tạm tính</span>
                    <span id="summary-subtotal" class="fw-semibold text-dark">0 ₫</span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted">Phí vận chuyển</span>
                    <span class="text-success fw-medium">Miễn phí toàn quốc</span>
                </div>

                <hr class="my-3" style="border-color: #e2e8f0;">

                <div class="d-flex justify-content-between align-items-baseline mb-4">
                    <span class="fs-6 fw-bold text-dark">Tổng thanh toán</span>
                    <span id="summary-total-amount" class="fs-4 fw-bold text-danger">0 ₫</span>
                </div>

                <div id="checkout-warning-text" class="alert alert-danger py-2 px-3 small mb-3 d-none rounded-3">
                    <i class="fa-solid fa-circle-exclamation me-1"></i> Vui lòng xóa hoặc điều chỉnh sản phẩm không khả dụng.
                </div>

                <button id="btn-checkout" type="button" class="btn btn-checkout-primary w-100">
                    <i class="fa-solid fa-shield-check me-2"></i> Tiến hành đặt hàng
                </button>

                <div class="mt-3 text-center">
                    <small class="text-muted d-inline-flex align-items-center gap-1">
                        <i class="fa-solid fa-lock text-success"></i> Bảo mật thanh toán an toàn 100%
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="/assets/js/pages/cart.js"></script>
@endpush
