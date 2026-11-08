@extends('layouts.app')

@section('title', 'Chi tiết đơn hàng #' . ($orderNumber ?? '') . ' - TechShop')

@push('styles')
<link rel="stylesheet" href="/assets/css/customer-pages.css?v=2.1">
@endpush

@section('content')
<div class="customer-page-wrapper py-4" id="order-detail-page" data-order-number="{{ $orderNumber }}">
    <!-- Breadcrumb chuẩn TechShop -->
    <div class="breadcrumb">
        <a href="/"><i class="fa-solid fa-house"></i> Trang chủ</a>
        <span>/</span>
        <a href="/account">Tài khoản</a>
        <span>/</span>
        <strong>Đơn hàng #{{ $orderNumber }}</strong>
    </div>

    <!-- Banner thông báo vừa đặt hàng thành công -->
    <div id="order-placed-banner" class="alert alert-success d-none mb-4 py-3 align-items-center rounded-3 border-0 shadow-sm" role="alert">
        <div class="d-flex align-items-center">
            <i class="fa-solid fa-circle-check text-success fs-2 me-3"></i>
            <div>
                <h5 class="alert-heading mb-1 fw-bold text-dark">Đặt hàng thành công!</h5>
                <p class="mb-0 small text-muted">Cảm ơn bạn đã mua sắm tại TechShop. Đơn hàng của bạn đã được ghi nhận và đang chờ nhân viên kiểm duyệt.</p>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div id="order-loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Đang tải...</span>
        </div>
        <p class="text-muted mt-2 small">Đang tải thông tin đơn hàng #{{ $orderNumber }}...</p>
    </div>

    <!-- Error State -->
    <div id="order-error" class="text-center py-5 d-none">
        <div class="text-danger mb-3">
            <i class="fa-solid fa-triangle-exclamation display-4"></i>
        </div>
        <h4 class="fw-bold text-dark mb-2" id="error-title">Không tìm thấy đơn hàng</h4>
        <p class="text-muted mb-4 small" id="error-message">Đơn hàng không tồn tại hoặc bạn không có quyền xem đơn hàng này.</p>
        <a href="/account" class="btn btn-primary px-4 py-2 rounded-3 fw-semibold">
            <i class="fa-solid fa-arrow-left me-1"></i> Xem các đơn hàng của tôi
        </a>
    </div>

    <!-- Order Content -->
    <div id="order-content" class="d-none">
        <!-- Header thông tin đơn hàng -->
        <div class="ts-card mb-4">
            <div class="ts-card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <h2 class="h4 mb-0 fw-bold text-dark">Đơn hàng #<span id="txt-order-number">{{ $orderNumber }}</span></h2>
                            <span id="badge-order-status" class="status-badge"></span>
                        </div>
                        <div class="text-muted small d-flex flex-wrap align-items-center gap-2">
                            <span>Ngày đặt: <strong class="text-dark" id="txt-created-at">--</strong></span>
                            <span>•</span>
                            <span>Phương thức: <strong class="text-dark" id="txt-payment-method">--</strong></span>
                            <span>•</span>
                            <span>Thanh toán: <span id="badge-payment-status">--</span></span>
                        </div>
                    </div>

                    <!-- Action buttons góc trên -->
                    <div class="d-flex gap-2" id="order-top-actions">
                        <button id="btn-pay-now" type="button" class="btn btn-success fw-bold px-3 py-2 rounded-3 d-none shadow-sm">
                            <i class="fa-solid fa-credit-card me-1"></i> Thanh toán ngay qua VNPay
                        </button>
                        <button id="btn-cancel-order" type="button" class="btn btn-outline-danger btn-sm px-3 py-2 rounded-3 d-none" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
                            <i class="fa-solid fa-ban me-1"></i> Hủy đơn hàng
                        </button>
                    </div>
                </div>

                <!-- Cảnh báo hết hạn thanh toán nếu có -->
                <div id="payment-expired-alert" class="alert alert-warning mt-3 mb-0 py-2 px-3 small d-none rounded-3 border-0" role="alert">
                    <i class="fa-regular fa-clock me-1 text-warning"></i>
                    Đơn hàng cần thanh toán trước: <strong id="txt-payment-expires-at"></strong>. Sau thời gian này, đơn hàng sẽ tự động bị hủy.
                </div>
            </div>
        </div>

        <!-- Thanh tiến trình đơn hàng (Stepper 5 bước) -->
        <div class="ts-card mb-4" id="order-timeline-card">
            <div class="ts-card-body p-4">
                <h6 class="fw-bold mb-4 text-dark text-uppercase small" style="letter-spacing: 0.5px;">Tiến trình đơn hàng</h6>

                <div class="order-stepper" id="order-stepper-container">
                    <div class="stepper-progress" id="timeline-progress-bar" style="width: 0%;"></div>

                    <div class="step-item active" id="step-pending">
                        <div class="step-circle"><i class="fa-solid fa-receipt"></i></div>
                        <div class="step-label">Chờ xử lý</div>
                    </div>

                    <div class="step-item" id="step-confirmed">
                        <div class="step-circle"><i class="fa-solid fa-check"></i></div>
                        <div class="step-label">Đã xác nhận</div>
                    </div>

                    <div class="step-item" id="step-processing">
                        <div class="step-circle"><i class="fa-solid fa-box-open"></i></div>
                        <div class="step-label">Đang đóng gói</div>
                    </div>

                    <div class="step-item" id="step-shipped">
                        <div class="step-circle"><i class="fa-solid fa-truck-fast"></i></div>
                        <div class="step-label">Đang giao</div>
                    </div>

                    <div class="step-item" id="step-delivered">
                        <div class="step-circle"><i class="fa-solid fa-house-circle-check"></i></div>
                        <div class="step-label">Đã giao</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Cột trái: Danh sách sản phẩm -->
            <div class="col-lg-8">
                <div class="ts-card mb-4">
                    <div class="ts-card-header">
                        <h5 class="card-title mb-0 fw-bold text-dark">Danh sách sản phẩm</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table cart-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 50%;">Sản phẩm</th>
                                    <th style="width: 20%;" class="text-center">Đơn giá</th>
                                    <th style="width: 10%;" class="text-center">Số lượng</th>
                                    <th style="width: 20%;" class="text-end">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody id="order-items-tbody">
                                <!-- Render qua JavaScript -->
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3 bg-light border-top">
                        <div class="row justify-content-end">
                            <div class="col-md-6 col-lg-5">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Tạm tính:</span>
                                    <span id="txt-subtotal" class="fw-semibold text-dark">0 ₫</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Phí vận chuyển:</span>
                                    <span class="text-success fw-medium small">0 ₫</span>
                                </div>
                                <hr class="my-2" style="border-color: #cbd5e1;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold fs-6 text-dark">Tổng cộng:</span>
                                    <span id="txt-total-amount" class="fw-bold fs-5 text-danger">0 ₫</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cột phải: Thông tin giao hàng & người nhận -->
            <div class="col-lg-4">
                <div class="ts-card mb-4">
                    <div class="ts-card-header">
                        <h5 class="card-title mb-0 fw-bold text-dark">Địa chỉ nhận hàng</h5>
                    </div>
                    <div class="ts-card-body p-3">
                        <div class="mb-3">
                            <div class="text-muted small mb-1">Người nhận</div>
                            <div id="txt-receiver-name" class="fw-bold text-dark fs-6">--</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small mb-1">Số điện thoại</div>
                            <div id="txt-receiver-phone" class="fw-medium text-dark">--</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small mb-1">Địa chỉ giao hàng</div>
                            <div id="txt-receiver-address" class="text-break text-dark small">--</div>
                        </div>
                        <div class="mb-0" id="block-order-note">
                            <div class="text-muted small mb-1">Ghi chú đơn hàng</div>
                            <div id="txt-receiver-note" class="fst-italic text-muted small p-2 bg-light rounded-2 border">--</div>
                        </div>
                    </div>
                </div>

                <div class="ts-card mb-4">
                    <div class="ts-card-header">
                        <h5 class="card-title mb-0 fw-bold text-dark">Hỗ trợ đơn hàng</h5>
                    </div>
                    <div class="ts-card-body p-3">
                        <p class="small text-muted mb-3">Nếu bạn có thắc mắc hoặc cần hỗ trợ thay đổi thông tin đơn hàng, vui lòng liên hệ tổng đài TechShop.</p>
                        <a href="tel:19001234" class="btn btn-outline-primary btn-sm w-100 py-2 rounded-3 fw-semibold">
                            <i class="fa-solid fa-phone me-1"></i> Hotline: 1900 1234
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Hủy đơn hàng -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-dark" id="cancelOrderModalLabel">
                    <i class="fa-solid fa-triangle-exclamation text-danger me-2"></i> Xác nhận hủy đơn hàng
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">Bạn có chắc chắn muốn hủy đơn hàng này? Thao tác này không thể hoàn tác.</p>
                <div class="mb-3">
                    <label for="cancel-reason" class="form-label fw-semibold small text-dark">Lý do hủy đơn <span class="text-danger">*</span></label>
                    <textarea class="form-control ts-form-control" id="cancel-reason" rows="3" placeholder="Ví dụ: Đổi ý không mua nữa, muốn thay đổi địa chỉ nhận..." required maxlength="255"></textarea>
                    <div class="invalid-feedback">Vui lòng cung cấp lý do hủy đơn.</div>
                </div>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">Đóng</button>
                <button type="button" id="btn-confirm-cancel" class="btn btn-danger px-4 fw-bold">
                    Xác nhận hủy
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="/assets/js/pages/order-detail.js"></script>
@endpush
