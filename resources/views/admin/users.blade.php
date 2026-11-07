@extends('layouts.admin')

@section('title', 'Quản lý Người dùng - Admin TechShop')
@section('page_title', 'Quản lý Người dùng')

@section('content')
<div class="admin-page-container">
    <div class="admin-page-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title h3 mb-1 fw-bold">Quản lý Người dùng</h1>
            <p class="text-muted mb-0">Quản trị danh sách khách hàng và nhân sự quản trị, khóa/mở khóa tài khoản</p>
        </div>
        <div>
            <button class="btn btn-outline-primary" id="btnReloadUsers">
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
                        <input type="text" id="userSearchInput" class="form-control border-start-0" placeholder="Tìm kiếm theo họ tên, email, số điện thoại...">
                    </div>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <select id="userRoleFilter" class="form-select">
                        <option value="">Tất cả vai trò</option>
                        <option value="customer">Khách hàng (customer)</option>
                        <option value="admin">Quản trị viên (admin)</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <select id="userStatusFilter" class="form-select">
                        <option value="">Tất cả trạng thái</option>
                        <option value="Active">Đang hoạt động (Active)</option>
                        <option value="Suspended">Đã bị khóa (Suspended)</option>
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <button class="btn btn-primary w-100" id="btnFilterUsers">
                        <i class="fas fa-filter me-1"></i> Lọc
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tableUsers">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th>Họ và tên</th>
                            <th>Email liên hệ</th>
                            <th>Số điện thoại</th>
                            <th class="text-center" style="width: 140px;">Vai trò</th>
                            <th class="text-center" style="width: 150px;">Trạng thái</th>
                            <th>Ngày tham gia</th>
                            <th class="text-end" style="width: 160px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                <span class="ms-2">Đang tải danh sách người dùng...</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center py-3" id="paginationUsers"></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="/assets/js/admin/users.js"></script>
@endpush
