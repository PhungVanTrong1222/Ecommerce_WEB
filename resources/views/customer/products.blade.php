@extends('layouts.app')

@section('title', 'Tất cả sản phẩm - TechShop')

@push('styles')
<link rel="stylesheet" href="/assets/css/storefront-pages.css?v=2.1">
@endpush

@section('content')
<div class="products-page">
    <!-- Breadcrumb (ts-breadcrumb chống đè Bootstrap 5) -->
    <div class="ts-breadcrumb">
        <a href="/"><i class="fa-solid fa-house"></i> Trang chủ</a>
        <span>/</span>
        <strong>Sản phẩm</strong>
        <span id="breadcrumbCategory" style="display:none;"><span>/</span> <strong id="breadcrumbCategoryName"></strong></span>
    </div>

    <div class="catalog-layout">
        <!-- 1. Bộ lọc bên trái (Sidebar Filter) -->
        <aside class="catalog-sidebar" id="catalogSidebar">
            <div class="filter-header">
                <h3><i class="fa-solid fa-filter"></i> Bộ lọc tìm kiếm</h3>
                <button type="button" class="btn-clear-filter" id="clearFiltersBtn" title="Đặt lại bộ lọc">
                    <i class="fa-solid fa-rotate-left"></i> Xóa lọc
                </button>
            </div>

            <!-- Tìm kiếm từ khóa -->
            <div class="filter-group">
                <label class="filter-label"><i class="fa-solid fa-magnifying-glass"></i> Từ khóa</label>
                <div class="filter-search-box">
                    <input type="text" id="filterKeyword" placeholder="Nhập tên sản phẩm..." autocomplete="off">
                    <button type="button" id="btnSearchSubmit" aria-label="Tìm kiếm"><i class="fa-solid fa-arrow-right"></i></button>
                </div>
            </div>

            <!-- Danh mục -->
            <div class="filter-group">
                <label class="filter-label"><i class="fa-solid fa-layer-group"></i> Danh mục</label>
                <div class="filter-radio-list" id="categoryFilterList">
                    <label class="filter-radio-item active">
                        <input type="radio" name="categoryFilter" value="" checked>
                        <span>Tất cả danh mục</span>
                    </label>
                    <!-- Danh mục sẽ được nạp động từ Catalog API -->
                </div>
            </div>

            <!-- Thương hiệu -->
            <div class="filter-group">
                <label class="filter-label"><i class="fa-solid fa-tag"></i> Thương hiệu</label>
                <div class="filter-chips" id="brandFilterList">
                    <button type="button" class="filter-chip active" data-brand="">Tất cả</button>
                    <button type="button" class="filter-chip" data-brand="Apple">Apple</button>
                    <button type="button" class="filter-chip" data-brand="Samsung">Samsung</button>
                    <button type="button" class="filter-chip" data-brand="Xiaomi">Xiaomi</button>
                    <button type="button" class="filter-chip" data-brand="ASUS">ASUS</button>
                    <button type="button" class="filter-chip" data-brand="Dell">Dell</button>
                    <button type="button" class="filter-chip" data-brand="Sony">Sony</button>
                    <button type="button" class="filter-chip" data-brand="Logitech">Logitech</button>
                </div>
            </div>

            <!-- Khoảng giá -->
            <div class="filter-group">
                <label class="filter-label"><i class="fa-solid fa-dong-sign"></i> Khoảng giá</label>
                <div class="price-inputs">
                    <input type="number" id="minPriceInput" placeholder="Từ (₫)" min="0" step="500000">
                    <span class="price-separator">-</span>
                    <input type="number" id="maxPriceInput" placeholder="Đến (₫)" min="0" step="500000">
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm btn-block w-100" id="applyPriceBtn" style="margin-top: 10px;">
                    Áp dụng giá
                </button>

                <!-- Quick price pills -->
                <div class="quick-price-pills">
                    <button type="button" class="price-pill" data-min="" data-max="5000000">Dưới 5 triệu</button>
                    <button type="button" class="price-pill" data-min="5000000" data-max="15000000">5 - 15 triệu</button>
                    <button type="button" class="price-pill" data-min="15000000" data-max="30000000">15 - 30 triệu</button>
                    <button type="button" class="price-pill" data-min="30000000" data-max="">Trên 30 triệu</button>
                </div>
            </div>

            <!-- Lọc ưu đãi -->
            <div class="filter-group">
                <label class="filter-checkbox-item">
                    <input type="checkbox" id="filterOnSale">
                    <span><i class="fa-solid fa-fire" style="color: #e11d48;"></i> Chỉ hiện hàng giảm giá sốc</span>
                </label>
            </div>
        </aside>

        <!-- 2. Danh sách sản phẩm bên phải (Main Content) -->
        <main class="catalog-main">
            <!-- Toolbar: Results Count, Active Chips, Sort Dropdown -->
            <div class="catalog-toolbar">
                <div class="toolbar-left d-flex align-items-center">
                    <button type="button" class="btn-toggle-filter-mobile" id="toggleMobileFilterBtn">
                        <i class="fa-solid fa-sliders"></i> Bộ lọc
                    </button>
                    <span class="results-count" id="resultsCount">Đang tải sản phẩm...</span>
                </div>
                <div class="toolbar-right">
                    <label for="sortSelect" class="sort-label">Sắp xếp:</label>
                    <select id="sortSelect" class="sort-select">
                        <option value="newest" selected>Mới nhất</option>
                        <option value="price_asc">Giá: Thấp đến cao</option>
                        <option value="price_desc">Giá: Cao đến thấp</option>
                        <option value="name_asc">Tên: A - Z</option>
                    </select>
                </div>
            </div>

            <!-- Active filter chips -->
            <div class="active-filters-bar" id="activeFiltersBar" style="display:none;">
                <span class="active-filters-label">Đang lọc:</span>
                <div class="active-chips-list" id="activeChipsList"></div>
            </div>

            <!-- Product Grid -->
            <div class="product-grid" id="productsGrid">
                <!-- Render động từ products.js -->
            </div>

            <!-- Empty State -->
            <div class="catalog-empty-state" id="emptyState" style="display:none;">
                <div class="empty-icon"><i class="fa-solid fa-box-open"></i></div>
                <h3>Không tìm thấy sản phẩm phù hợp</h3>
                <p>Hãy thử tìm kiếm với từ khóa khác hoặc xóa bớt tiêu chí lọc hiện tại.</p>
                <button type="button" class="btn btn-primary" id="btnResetEmptyFilters">
                    <i class="fa-solid fa-rotate-left"></i> Xóa tất cả bộ lọc
                </button>
            </div>

            <!-- Pagination Bar -->
            <div class="pagination-bar" id="paginationBar">
                <!-- Nút phân trang render từ products.js -->
            </div>
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script src="/assets/js/pages/products.js"></script>
@endpush
