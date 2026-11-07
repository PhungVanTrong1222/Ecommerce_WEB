@extends('layouts.admin')

@section('title', 'Quản lý Sản phẩm - Admin TechShop')
@section('page_title', 'Quản lý Sản phẩm & Biến thể')

@section('content')
<div class="admin-page-container">
    <div class="admin-page-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title h3 mb-1 fw-bold">Quản lý Sản phẩm</h1>
            <p class="text-muted mb-0">Quản lý danh mục sản phẩm, biến thể, giá bán và hình ảnh</p>
        </div>
        <div>
            <button class="btn btn-primary" id="btnOpenCreateProductModal">
                <i class="fas fa-plus me-1"></i> Tạo sản phẩm mới
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
                        <input type="text" id="productSearchInput" class="form-control border-start-0" placeholder="Tìm theo tên sản phẩm, slug, thương hiệu...">
                    </div>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <select id="productCategoryFilter" class="form-select">
                        <option value="">Tất cả danh mục</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <select id="productStatusFilter" class="form-select">
                        <option value="">Tất cả trạng thái</option>
                        <option value="Active">Đang bán (Active)</option>
                        <option value="Inactive">Ngừng kinh doanh (Inactive)</option>
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <button class="btn btn-outline-secondary w-100" id="btnFilterProducts">
                        <i class="fas fa-filter me-1"></i> Lọc
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tableProducts">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 80px;" class="text-center">Ảnh</th>
                            <th>Tên sản phẩm</th>
                            <th>Danh mục / Hiệu</th>
                            <th class="text-center" style="width: 100px;">Biến thể</th>
                            <th class="text-center" style="width: 110px;">Tổng tồn</th>
                            <th class="text-center" style="width: 130px;">Trạng thái</th>
                            <th class="text-end" style="width: 140px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="productTableBody">
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                <span class="ms-2">Đang tải danh sách sản phẩm...</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center py-3" id="paginationProducts">
            <!-- Render pagination bằng JS -->
        </div>
    </div>
</div>

<!-- Modal 1: Tạo Sản Phẩm Mới -->
<div class="modal fade" id="modalCreateProduct" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-plus-circle me-1"></i> Thêm Sản phẩm mới
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" id="btnCloseCreateProductModal"></button>
            </div>
            <form id="formCreateProduct">
                <div class="modal-body p-4">
                    <h6 class="text-primary fw-bold mb-3 border-bottom pb-2">1. Thông tin chung sản phẩm</h6>
                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label class="form-label fw-semibold">Danh mục <span class="text-danger">*</span></label>
                            <select class="form-select" id="createProductCategory" required>
                                <option value="">-- Chọn danh mục --</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="form-label fw-semibold">Thương hiệu / Hãng</label>
                            <input type="text" class="form-control" id="createProductBrand" placeholder="VD: Apple, Samsung, Asus...">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8 form-group mb-3">
                            <label class="form-label fw-semibold">Tên sản phẩm <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="createProductName" required placeholder="VD: iPhone 15 Pro Max, Laptop Dell XPS...">
                        </div>
                        <div class="col-md-4 form-group mb-3">
                            <label class="form-label fw-semibold">Trạng thái</label>
                            <select class="form-select" id="createProductStatus">
                                <option value="Active">Đang bán (Active)</option>
                                <option value="Inactive">Tạm ẩn (Inactive)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label fw-semibold">Mô tả sản phẩm</label>
                        <textarea class="form-control" id="createProductDesc" rows="3" placeholder="Giới thiệu điểm nổi bật của sản phẩm..."></textarea>
                    </div>

                    <!-- Thông số kỹ thuật động -->
                    <div class="form-group mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold mb-0">Thông số kỹ thuật (Specifications)</label>
                            <button type="button" class="btn btn-sm btn-outline-info" id="btnAddSpecRow">
                                <i class="fas fa-plus me-1"></i> Thêm thông số
                            </button>
                        </div>
                        <div id="createProductSpecsContainer" class="border rounded p-2 bg-light">
                            <!-- Rows động do JS thêm -->
                        </div>
                    </div>

                    <h6 class="text-primary fw-bold mb-3 border-bottom pb-2">
                        2. Danh sách Biến thể ban đầu (ít nhất 1 biến thể)
                    </h6>
                    <div class="table-responsive mb-2">
                        <table class="table table-bordered table-sm mb-1 bg-white align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Mã SKU <span class="text-danger">*</span></th>
                                    <th>Tên biến thể <span class="text-danger">*</span></th>
                                    <th>Giá niêm yết (đ) <span class="text-danger">*</span></th>
                                    <th>Giá khuyến mãi (đ)</th>
                                    <th>Tồn ban đầu</th>
                                    <th style="width: 40px;"></th>
                                </tr>
                            </thead>
                            <tbody id="createVariantsTableBody">
                                <!-- Hàng biến thể ban đầu -->
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddVariantRow">
                        <i class="fas fa-plus me-1"></i> Thêm dòng biến thể
                    </button>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="btnCancelCreateProductModal">Hủy</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitCreateProduct">
                        <i class="fas fa-save me-1"></i> Tạo sản phẩm
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Chi tiết & Chỉnh sửa Sản phẩm (Tabbed) -->
<div class="modal fade" id="modalEditProduct" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="editProductModalTitle">
                    <i class="fas fa-cog me-1"></i> Chi tiết & Cập nhật Sản phẩm
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" id="btnCloseEditProductModal"></button>
            </div>
            <div class="modal-body p-0">
                <!-- Nav tabs -->
                <ul class="nav nav-tabs px-3 pt-3 bg-light border-bottom" id="editProductTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active fw-bold" id="tabGeneralLink" data-bs-toggle="tab" href="#tabGeneral" role="tab">
                            <i class="fas fa-info-circle me-1"></i> 1. Thông tin chung
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-bold" id="tabVariantsLink" data-bs-toggle="tab" href="#tabVariants" role="tab">
                            <i class="fas fa-cubes me-1"></i> 2. Quản lý Biến thể (<span id="editVariantsCount">0</span>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-bold" id="tabImagesLink" data-bs-toggle="tab" href="#tabImages" role="tab">
                            <i class="fas fa-images me-1"></i> 3. Quản lý Hình ảnh (<span id="editImagesCount">0</span>)
                        </a>
                    </li>
                </ul>

                <!-- Tab panes -->
                <div class="tab-content p-4" id="editProductTabContent">
                    <!-- Tab 1: General Info -->
                    <div class="tab-pane fade show active" id="tabGeneral" role="tabpanel">
                        <form id="formUpdateGeneralProduct">
                            <input type="hidden" id="editProductId">
                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label class="form-label fw-semibold">Danh mục</label>
                                    <select class="form-select" id="editProductCategory" required></select>
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label class="form-label fw-semibold">Thương hiệu</label>
                                    <input type="text" class="form-control" id="editProductBrand">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-8 form-group mb-3">
                                    <label class="form-label fw-semibold">Tên sản phẩm <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="editProductName" required>
                                </div>
                                <div class="col-md-4 form-group mb-3">
                                    <label class="form-label fw-semibold">Trạng thái</label>
                                    <select class="form-select" id="editProductStatus">
                                        <option value="Active">Đang bán (Active)</option>
                                        <option value="Inactive">Tạm ẩn (Inactive)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-label fw-semibold">Mô tả sản phẩm</label>
                                <textarea class="form-control" id="editProductDesc" rows="4"></textarea>
                            </div>
                            <div class="form-group mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label fw-semibold mb-0">Thông số kỹ thuật</label>
                                    <button type="button" class="btn btn-sm btn-outline-info" id="btnEditAddSpecRow">
                                        <i class="fas fa-plus me-1"></i> Thêm thông số
                                    </button>
                                </div>
                                <div id="editProductSpecsContainer" class="border rounded p-2 bg-light"></div>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary" id="btnSubmitUpdateGeneral">
                                    <i class="fas fa-save me-1"></i> Lưu thông tin chung
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Tab 2: Variants Management -->
                    <div class="tab-pane fade" id="tabVariants" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-dark">Danh sách các biến thể của sản phẩm</h6>
                            <button type="button" class="btn btn-sm btn-success" id="btnToggleAddNewVariant">
                                <i class="fas fa-plus me-1"></i> Thêm biến thể mới
                            </button>
                        </div>

                        <!-- Form thêm biến thể mới (toggle) -->
                        <div id="cardAddNewVariant" class="card border border-success mb-4 d-none bg-light">
                            <div class="card-body py-3">
                                <h6 class="fw-bold text-success mb-3">Thêm biến thể mới cho sản phẩm này</h6>
                                <form id="formAddNewVariant">
                                    <div class="row">
                                        <div class="col-md-3 form-group mb-2">
                                            <label class="form-label small fw-bold">Mã SKU <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control form-control-sm" id="newVarSku" required placeholder="VD: IP15-256-BLK">
                                        </div>
                                        <div class="col-md-3 form-group mb-2">
                                            <label class="form-label small fw-bold">Tên biến thể <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control form-control-sm" id="newVarName" required placeholder="VD: 256GB Đen">
                                        </div>
                                        <div class="col-md-2 form-group mb-2">
                                            <label class="form-label small fw-bold">Giá niêm yết (đ) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control form-control-sm" id="newVarPrice" required min="0" step="1000">
                                        </div>
                                        <div class="col-md-2 form-group mb-2">
                                            <label class="form-label small fw-bold">Giá KM (đ)</label>
                                            <input type="number" class="form-control form-control-sm" id="newVarSalePrice" min="0" step="1000">
                                        </div>
                                        <div class="col-md-2 form-group mb-2">
                                            <label class="form-label small fw-bold">Tồn kho ban đầu</label>
                                            <input type="number" class="form-control form-control-sm" id="newVarQty" min="0" value="0">
                                        </div>
                                    </div>
                                    <div class="text-end mt-2">
                                        <button type="button" class="btn btn-sm btn-secondary me-1" id="btnCancelAddNewVariant">Hủy</button>
                                        <button type="submit" class="btn btn-sm btn-success" id="btnSubmitNewVariant">
                                            <i class="fas fa-check me-1"></i> Lưu biến thể
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Table Variants -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Mã SKU</th>
                                        <th>Tên biến thể</th>
                                        <th>Giá bán</th>
                                        <th>Giá khuyến mãi</th>
                                        <th class="text-center">Tồn kho</th>
                                        <th class="text-center">Trạng thái</th>
                                        <th class="text-end" style="width: 120px;">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody id="editVariantsTableBody"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 3: Images Management -->
                    <div class="tab-pane fade" id="tabImages" role="tabpanel">
                        <div class="card border-0 bg-light p-3 mb-4">
                            <h6 class="fw-bold mb-3 text-dark">Tải lên hình ảnh mới cho sản phẩm</h6>
                            <form id="formUploadProductImage" enctype="multipart/form-data">
                                <div class="row align-items-center">
                                    <div class="col-md-6 form-group mb-2">
                                        <input type="file" class="form-control" id="uploadImageFile" accept="image/*" required>
                                    </div>
                                    <div class="col-md-3 form-group mb-2">
                                        <div class="form-check form-switch pt-1">
                                            <input type="checkbox" class="form-check-input" id="uploadImageIsPrimary">
                                            <label class="form-check-label fw-bold" for="uploadImageIsPrimary">Đặt làm ảnh chính</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 form-group mb-2 text-end">
                                        <button type="submit" class="btn btn-primary w-100" id="btnSubmitUploadImage">
                                            <i class="fas fa-upload me-1"></i> Tải ảnh lên
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <h6 class="fw-bold mb-3 text-dark">Hình ảnh hiện có</h6>
                        <div class="row" id="productImagesGallery"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="btnBottomCloseEditProduct">Đóng cửa sổ</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="/assets/js/admin/products.js"></script>
@endpush
