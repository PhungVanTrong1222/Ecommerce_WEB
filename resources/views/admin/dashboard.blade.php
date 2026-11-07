@extends('layouts.admin')

@section('title', 'Dashboard - Tổng quan hệ thống')
@section('page_title', 'Tổng quan hệ thống')

@section('content')
<div class="admin-page-container">
    <div class="admin-page-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title h3 mb-1 fw-bold">Tổng quan hệ thống</h1>
            <p class="text-muted mb-0">Báo cáo hoạt động kinh doanh và chỉ số vận hành quan trọng</p>
        </div>
        <div>
            <button id="btnRefreshDashboard" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-sync-alt me-1"></i> Làm mới
            </button>
        </div>
    </div>

    <!-- 4 KPI Cards -->
    <div class="row mb-4" id="kpiContainer">
        <!-- Doanh thu -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card stat-card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon bg-success-subtle text-success rounded-circle p-3 me-3">
                        <i class="fas fa-wallet fa-2x"></i>
                    </div>
                    <div class="stat-info flex-grow-1">
                        <span class="text-muted text-uppercase fw-bold small">Doanh thu thực thu</span>
                        <h3 class="mb-0 fw-bold mt-1 text-success" id="kpiRevenue">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </h3>
                        <small class="text-muted">Từ đơn hàng Paid</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Đơn hàng -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card stat-card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon bg-primary-subtle text-primary rounded-circle p-3 me-3">
                        <i class="fas fa-shopping-cart fa-2x"></i>
                    </div>
                    <div class="stat-info flex-grow-1">
                        <span class="text-muted text-uppercase fw-bold small">Tổng đơn hàng</span>
                        <h3 class="mb-0 fw-bold mt-1 text-primary" id="kpiOrders">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </h3>
                        <small class="text-muted">Đơn hàng trong hệ thống</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sản phẩm -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card stat-card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon bg-info-subtle text-info rounded-circle p-3 me-3">
                        <i class="fas fa-box-open fa-2x"></i>
                    </div>
                    <div class="stat-info flex-grow-1">
                        <span class="text-muted text-uppercase fw-bold small">Tổng sản phẩm</span>
                        <h3 class="mb-0 fw-bold mt-1 text-info" id="kpiProducts">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </h3>
                        <small class="text-muted">Đang quản lý</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Khách hàng -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card stat-card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon bg-warning-subtle text-warning rounded-circle p-3 me-3">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                    <div class="stat-info flex-grow-1">
                        <span class="text-muted text-uppercase fw-bold small">Khách hàng</span>
                        <h3 class="mb-0 fw-bold mt-1 text-warning" id="kpiUsers">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </h3>
                        <small class="text-muted">Tài khoản thành viên</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2 Tables Row -->
    <div class="row">
        <!-- Đơn hàng gần đây -->
        <div class="col-lg-7 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="fas fa-receipt text-primary me-2"></i>Đơn hàng gần đây
                    </h5>
                    <a href="/admin/orders" class="btn btn-sm btn-link text-primary fw-bold p-0 text-decoration-none">
                        Xem tất cả <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tableRecentOrders">
                            <thead class="table-light">
                                <tr>
                                    <th>Mã đơn</th>
                                    <th>Khách hàng</th>
                                    <th>Tổng tiền</th>
                                    <th>Phương thức</th>
                                    <th>Trạng thái</th>
                                    <th>Thời gian</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                        <span class="ms-2">Đang tải dữ liệu đơn hàng...</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cảnh báo tồn kho thấp -->
        <div class="col-lg-5 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-danger">
                        <i class="fas fa-exclamation-triangle text-danger me-2"></i>Cảnh báo Tồn kho thấp (&le; 5)
                    </h5>
                    <a href="/admin/inventory?low_stock=1" class="btn btn-sm btn-link text-danger fw-bold p-0 text-decoration-none">
                        Quản lý kho <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tableLowStock">
                            <thead class="table-light">
                                <tr>
                                    <th>Mã SKU</th>
                                    <th>Sản phẩm / Biến thể</th>
                                    <th class="text-center">Tồn kho</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">
                                        <div class="spinner-border spinner-border-sm text-danger" role="status"></div>
                                        <span class="ms-2">Đang kiểm tra kho hàng...</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="/assets/js/admin/dashboard.js"></script>
@endpush
