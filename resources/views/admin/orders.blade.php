@extends('layouts.admin')

@section('title', 'Quản lý Đơn hàng - Admin TechShop')
@section('page_title', 'Quản lý Đơn hàng')

@section('content')
<div class="admin-page-container">
    <div class="admin-page-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title h3 mb-1 fw-bold">Quản lý Đơn hàng</h1>
            <p class="text-muted mb-0">Theo dõi, duyệt và xử lý trạng thái giao hàng cho toàn bộ đơn hàng</p>
        </div>
        <div>
            <button class="btn btn-outline-primary" id="btnReloadOrders">
                <i class="fas fa-sync-alt me-1"></i> Tải lại danh sách
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
                        <input type="text" id="orderSearchInput" class="form-control border-start-0" placeholder="Mã đơn (ORD-...), tên, email khách...">
                    </div>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <select id="orderStatusFilter" class="form-select">
                        <option value="">Tất cả trạng thái đơn</option>
                        <option value="Pending">Chờ xác nhận (Pending)</option>
                        <option value="Confirmed">Đã xác nhận (Confirmed)</option>
                        <option value="Processing">Đang xử lý (Processing)</option>
                        <option value="Shipped">Đang giao hàng (Shipped)</option>
                        <option value="Delivered">Đã giao thành công (Delivered)</option>
                        <option value="Cancelled">Đã hủy (Cancelled)</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <select id="orderPaymentMethodFilter" class="form-select">
                        <option value="">Tất cả phương thức TT</option>
                        <option value="COD">Thanh toán khi nhận hàng (COD)</option>
                        <option value="VNPay">Cổng VNPay (ATM / QR / Thẻ)</option>
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <button class="btn btn-primary w-100" id="btnFilterOrders">
                        <i class="fas fa-filter me-1"></i> Lọc
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tableOrders">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 150px;">Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Tổng tiền</th>
                            <th>Phương thức</th>
                            <th class="text-center" style="width: 140px;">Thanh toán</th>
                            <th class="text-center" style="width: 150px;">Trạng thái đơn</th>
                            <th>Ngày đặt</th>
                            <th class="text-end" style="width: 130px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody id="ordersTableBody">
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                <span class="ms-2">Đang tải danh sách đơn hàng...</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center py-3" id="paginationOrders"></div>
    </div>
</div>

<!-- Modal Chi tiết & Chuyển trạng thái Đơn hàng -->
<div class="modal fade" id="modalOrderDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="orderModalTitle">
                    <i class="fas fa-receipt me-1"></i> Chi tiết Đơn hàng
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" id="btnCloseOrderModal"></button>
            </div>
            <div class="modal-body p-4" id="orderModalBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 text-muted">Đang tải chi tiết đơn hàng...</div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 d-flex justify-content-between" id="orderModalFooter">
                <div id="orderStatusActionButtons">
                    <!-- Các nút chuyển trạng thái theo State Machine do JS sinh ra -->
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="btnBottomCloseOrder">Đóng</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="/assets/js/admin/orders.js"></script>
@endpush
