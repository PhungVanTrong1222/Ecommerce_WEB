@extends('layouts.admin')

@section('title', 'Quản lý Thanh toán - Admin TechShop')
@section('page_title', 'Quản lý Thanh toán')

@section('content')
<div class="admin-page-container">
    <div class="admin-page-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title h3 mb-1 fw-bold">Quản lý Thanh toán</h1>
            <p class="text-muted mb-0">Theo dõi toàn bộ dòng tiền, giao dịch cổng VNPay và xác nhận thu tiền COD</p>
        </div>
        <div>
            <button class="btn btn-outline-primary" id="btnReloadPayments">
                <i class="fas fa-sync-alt me-1"></i> Làm mới danh sách
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row align-items-center">
                <div class="col-md-4 mb-2 mb-md-0">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="paymentSearchInput" class="form-control border-start-0" placeholder="Tìm theo mã đơn hàng (ORD-...)...">
                    </div>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <select id="paymentMethodFilter" class="form-select">
                        <option value="">Tất cả phương thức</option>
                        <option value="COD">Tiền mặt khi nhận hàng (COD)</option>
                        <option value="VNPay">Cổng thanh toán VNPay</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <select id="paymentStatusFilter" class="form-select">
                        <option value="">Tất cả trạng thái TT</option>
                        <option value="Paid">Đã thanh toán (Paid)</option>
                        <option value="Pending">Chờ thanh toán (Pending)</option>
                        <option value="Processing">Đang xử lý (Processing)</option>
                        <option value="Failed">Thanh toán thất bại (Failed)</option>
                        <option value="Cancelled">Đã hủy (Cancelled)</option>
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <button class="btn btn-primary w-100" id="btnFilterPayments">
                        <i class="fas fa-filter me-1"></i> Lọc
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablePayments">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 150px;">Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Phương thức</th>
                            <th class="text-end">Số tiền</th>
                            <th class="text-center" style="width: 140px;">Trạng thái TT</th>
                            <th class="text-center" style="width: 140px;">Trạng thái đơn</th>
                            <th>Thời gian thanh toán</th>
                            <th class="text-end" style="width: 180px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody id="paymentsTableBody">
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                <span class="ms-2">Đang tải danh sách thanh toán...</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center py-3" id="paginationPayments"></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="/assets/js/admin/payments.js"></script>
@endpush
