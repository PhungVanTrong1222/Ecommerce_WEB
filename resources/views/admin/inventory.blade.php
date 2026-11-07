@extends('layouts.admin')

@section('title', 'Quản lý Tồn kho - Admin TechShop')
@section('page_title', 'Quản lý Tồn kho')

@section('content')
<div class="admin-page-container">
    <div class="admin-page-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title h3 mb-1 fw-bold">Quản lý Tồn kho</h1>
            <p class="text-muted mb-0">Theo dõi tồn kho từng biến thể sản phẩm và điều chỉnh xuất / nhập kho</p>
        </div>
        <div>
            <button class="btn btn-outline-primary" id="btnReloadInventory">
                <i class="fas fa-sync-alt me-1"></i> Làm mới
            </button>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row align-items-center">
                <div class="col-md-5 mb-2 mb-md-0">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="invSearchInput" class="form-control border-start-0" placeholder="Tìm kiếm theo mã SKU, tên sản phẩm, biến thể...">
                    </div>
                </div>
                <div class="col-md-4 mb-2 mb-md-0">
                    <div class="form-check pt-1">
                        <input type="checkbox" class="form-check-input" id="invLowStockCheckbox">
                        <label class="form-check-label fw-bold text-danger" for="invLowStockCheckbox">
                            <i class="fas fa-exclamation-triangle me-1"></i> Chỉ hiển thị sắp hết hàng (&le; 5 sản phẩm)
                        </label>
                    </div>
                </div>
                <div class="col-md-3 text-end">
                    <button class="btn btn-primary w-100" id="btnFilterInventory">
                        <i class="fas fa-filter me-1"></i> Lọc dữ liệu
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Inventory Table Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tableInventory">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 140px;">Mã SKU</th>
                            <th>Tên sản phẩm</th>
                            <th>Biến thể</th>
                            <th class="text-end">Giá bán</th>
                            <th class="text-center" style="width: 120px;">Tồn kho</th>
                            <th>Cập nhật lần cuối</th>
                            <th class="text-end" style="width: 150px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="inventoryTableBody">
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                <span class="ms-2">Đang tải dữ liệu tồn kho...</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center py-3" id="paginationInventory">
            <!-- Render pagination bằng JS -->
        </div>
    </div>
</div>

<!-- Modal Điều chỉnh Tồn kho -->
<div class="modal fade" id="modalAdjustInventory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-sliders-h me-1"></i> Điều chỉnh Tồn kho
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" id="btnCloseAdjustModal"></button>
            </div>
            <form id="formAdjustInventory">
                <div class="modal-body p-4">
                    <input type="hidden" id="adjustVariantId">

                    <!-- Info Box -->
                    <div class="bg-light p-3 rounded mb-3 border">
                        <div class="mb-1"><strong>Sản phẩm:</strong> <span id="adjustProductName" class="text-dark"></span></div>
                        <div class="mb-1"><strong>Biến thể:</strong> <span id="adjustVariantName" class="text-muted"></span></div>
                        <div class="mb-1"><strong>Mã SKU:</strong> <code id="adjustSku" class="text-dark"></code></div>
                        <div class="mt-2">
                            <strong>Số lượng tồn hiện tại:</strong> 
                            <span id="adjustCurrentQty" class="badge bg-secondary px-2 py-1 fs-6 ms-1">0</span>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label fw-semibold">Loại điều chỉnh <span class="text-danger">*</span></label>
                        <div class="d-flex">
                            <div class="form-check me-4">
                                <input type="radio" id="adjustTypeAdd" name="adjustType" class="form-check-input" value="add" checked>
                                <label class="form-check-label text-success fw-bold" for="adjustTypeAdd">
                                    <i class="fas fa-plus-circle me-1"></i> Nhập thêm vào kho (+)
                                </label>
                            </div>
                            <div class="form-check">
                                <input type="radio" id="adjustTypeSubtract" name="adjustType" class="form-check-input" value="subtract">
                                <label class="form-check-label text-danger fw-bold" for="adjustTypeSubtract">
                                    <i class="fas fa-minus-circle me-1"></i> Xuất / Giảm kho (-)
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="adjustAmount" class="form-label fw-semibold">Số lượng thay đổi <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="adjustAmount" required min="1" value="1" placeholder="Nhập số lượng nguyên dương">
                    </div>

                    <div class="alert alert-info py-2 px-3 mb-3 d-flex justify-content-between align-items-center">
                        <span class="small">Tồn kho dự kiến sau điều chỉnh:</span>
                        <strong class="h5 mb-0 text-primary" id="adjustExpectedQty">0</strong>
                    </div>

                    <div class="form-group mb-0">
                        <label for="adjustReason" class="form-label fw-semibold">Lý do điều chỉnh kho <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="adjustReason" required rows="2" maxlength="255" placeholder="VD: Nhập hàng đợt mới từ nhà phân phối, xuất mẫu thử, bù kiểm kê..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="btnCancelAdjustModal">Hủy</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitAdjust">
                        <i class="fas fa-check me-1"></i> Xác nhận điều chỉnh
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="/assets/js/admin/inventory.js"></script>
@endpush
