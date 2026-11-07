@extends('layouts.admin')

@section('title', 'Quản lý Danh mục - Admin TechShop')
@section('page_title', 'Quản lý Danh mục')

@section('content')
<div class="admin-page-container">
    <div class="admin-page-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title h3 mb-1 fw-bold">Quản lý Danh mục</h1>
            <p class="text-muted mb-0">Quản lý cây danh mục sản phẩm, trạng thái hiển thị và mô tả</p>
        </div>
        <div>
            <button class="btn btn-primary" id="btnOpenCreateModal">
                <i class="fas fa-plus me-1"></i> Thêm danh mục mới
            </button>
        </div>
    </div>

    <!-- Filter & Search Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row align-items-center">
                <div class="col-md-6 mb-2 mb-md-0">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="categorySearchInput" class="form-control border-start-0" placeholder="Tìm theo tên hoặc slug danh mục...">
                    </div>
                </div>
                <div class="col-md-4 mb-2 mb-md-0">
                    <select id="categoryStatusFilter" class="form-select">
                        <option value="">Tất cả trạng thái</option>
                        <option value="Active">Đang hoạt động (Active)</option>
                        <option value="Inactive">Tạm ẩn (Inactive)</option>
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <button class="btn btn-outline-secondary w-100" id="btnReloadCategories">
                        <i class="fas fa-sync-alt me-1"></i> Tải lại
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Table Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tableCategories">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th>Tên danh mục</th>
                            <th>Slug</th>
                            <th>Mô tả</th>
                            <th class="text-center" style="width: 120px;">Số SP</th>
                            <th class="text-center" style="width: 150px;">Trạng thái</th>
                            <th class="text-end" style="width: 200px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="categoryTableBody">
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                <span class="ms-2">Đang tải danh mục...</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Thêm / Sửa Danh mục -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="categoryModalTitle">Thêm danh mục mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="btnCloseCategoryModal"></button>
            </div>
            <form id="categoryForm">
                <div class="modal-body p-4">
                    <input type="hidden" id="categoryId">
                    
                    <div class="form-group mb-3">
                        <label for="categoryName" class="form-label fw-semibold">Tên danh mục <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="categoryName" required maxlength="100" placeholder="VD: Điện thoại, Laptop...">
                    </div>

                    <div class="form-group mb-3">
                        <label for="categoryDescription" class="form-label fw-semibold">Mô tả danh mục</label>
                        <textarea class="form-control" id="categoryDescription" rows="3" maxlength="500" placeholder="Mô tả ngắn gọn về danh mục..."></textarea>
                    </div>

                    <div class="form-group mb-0">
                        <label for="categoryStatus" class="form-label fw-semibold">Trạng thái hiển thị</label>
                        <select class="form-select" id="categoryStatus">
                            <option value="Active">Hoạt động (Active)</option>
                            <option value="Inactive">Tạm ẩn (Inactive)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="btnCancelCategoryModal">Đóng</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveCategory">
                        <i class="fas fa-save me-1"></i> Lưu thông tin
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="/assets/js/admin/categories.js"></script>
@endpush
