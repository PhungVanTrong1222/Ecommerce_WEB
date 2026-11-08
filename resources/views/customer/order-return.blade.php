@extends('layouts.app')

@section('title', 'Kết quả thanh toán VNPay - TechShop')

@push('styles')
<link rel="stylesheet" href="/assets/css/customer-pages.css?v=2.2">
@endpush

@section('content')
<div class="customer-page-wrapper py-4">
    <!-- Breadcrumb chuẩn TechShop -->
    <div class="breadcrumb mb-4">
        <a href="/"><i class="fa-solid fa-house"></i> Trang chủ</a>
        <span>/</span>
        <strong>Kết quả thanh toán</strong>
    </div>

    <!-- Loading State -->
    <div id="vnpay-loading" class="text-center py-5">
        <div class="spinner-border text-primary" style="width: 3.5rem; height: 3.5rem;" role="status">
            <span class="visually-hidden">Đang xử lý...</span>
        </div>
        <h4 class="mt-4 fw-bold text-dark">Đang xác thực giao dịch với VNPay...</h4>
        <p class="text-muted small">Vui lòng không tải lại hoặc tắt trang web trong giây lát.</p>
    </div>

    <!-- Result Card -->
    <div id="vnpay-result" class="d-none">
        <div class="return-card">
            <!-- Header Banner -->
            <div id="result-header" class="text-white text-center py-5 px-4">
                <div id="result-icon-container" class="return-icon-wrapper">
                    <!-- Icon thành công hoặc thất bại render bằng JS -->
                </div>
                <h3 id="result-title" class="fw-bold mb-2"></h3>
                <p id="result-subtitle" class="mb-0 opacity-75 small"></p>
            </div>

            <!-- Body Details -->
            <div class="p-4 p-md-5">
                <div class="bg-light rounded-4 p-3 mb-4 border" style="border-color: #f1f5f9;">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted small">Mã đơn hàng</span>
                        <span id="res-order-number" class="fw-bold text-dark"></span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom align-items-center">
                        <span class="text-muted small">Số tiền thanh toán</span>
                        <span id="res-amount" class="fw-bold text-danger fs-5"></span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted small">Cổng thanh toán</span>
                        <span class="fw-semibold text-primary"><i class="fa-solid fa-building-columns me-1"></i> VNPay</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom" id="row-transaction-no">
                        <span class="text-muted small">Mã giao dịch VNPay</span>
                        <span id="res-transaction-no" class="fw-medium font-monospace small"></span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom align-items-center">
                        <span class="text-muted small">Trạng thái</span>
                        <span id="res-payment-status"></span>
                    </div>
                    <div class="d-flex justify-content-between py-2 align-items-center">
                        <span class="text-muted small">Thông điệp kết quả</span>
                        <span id="res-message" class="text-end fw-medium small text-dark" style="max-width: 60%;"></span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-grid gap-2">
                    <a id="btn-view-order" href="#" class="btn btn-primary py-3 fw-bold rounded-3 shadow-sm">
                        <i class="fa-solid fa-file-invoice me-2"></i> Xem chi tiết đơn hàng
                    </a>
                    <a href="/" class="btn btn-outline-secondary py-2 fw-medium rounded-3">
                        <i class="fa-solid fa-house me-1"></i> Tiếp tục mua sắm
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="/assets/js/pages/order-return.js"></script>
@endpush
