@extends('layouts.app')

@section('title', 'Chi tiết sản phẩm - TechShop')

@push('styles')
<link rel="stylesheet" href="/assets/css/storefront-pages.css">
@endpush

@section('content')
<div class="product-detail-page" id="productDetailPage" data-slug="{{ $slug ?? request()->route('slug') ?? '' }}">
    <!-- Breadcrumb (ts-breadcrumb chống đè Bootstrap 5) -->
    <div class="ts-breadcrumb" id="productBreadcrumb">
        <a href="/"><i class="fa-solid fa-house"></i> Trang chủ</a>
        <span>/</span>
        <a href="/products" id="bcCategoryLink">Sản phẩm</a>
        <span>/</span>
        <strong id="bcProductName">Đang tải...</strong>
    </div>

    <!-- Loading State -->
    <div class="detail-loading-state" id="detailLoadingState">
        <div class="spinner"></div>
        <p>Đang tải thông tin sản phẩm...</p>
    </div>

    <!-- Error State -->
    <div class="detail-error-state" id="detailErrorState" style="display:none;">
        <i class="fa-solid fa-circle-exclamation error-icon"></i>
        <h3>Không tìm thấy sản phẩm</h3>
        <p>Sản phẩm này có thể đã ngừng kinh doanh hoặc đường dẫn không chính xác.</p>
        <a href="/products" class="btn btn-primary"><i class="fa-solid fa-arrow-left"></i> Quay lại danh sách sản phẩm</a>
    </div>

    <!-- Main Detail Container -->
    <div class="detail-container" id="detailContainer" style="display:none;">
        <div class="detail-main-grid">
            <!-- 1. Cột trái: Gallery Ảnh -->
            <div class="detail-gallery">
                <div class="main-image-wrapper">
                    <img id="mainProductImage" src="" alt="Ảnh sản phẩm">
                    <span class="detail-discount-badge" id="mainDiscountBadge" style="display:none;">-0%</span>
                </div>
                <div class="thumbnails-track" id="thumbnailsTrack">
                    <!-- Sẽ render động danh sách thumbnail -->
                </div>
            </div>

            <!-- 2. Cột phải: Thông tin & Chọn biến thể -->
            <div class="detail-content">
                <div class="brand-badge" id="productBrand">Thương hiệu</div>
                <h1 class="product-title" id="productTitle">Tên sản phẩm</h1>

                <div class="meta-row">
                    <div class="meta-item">SKU: <strong id="selectedSku">---</strong></div>
                    <div class="meta-divider">•</div>
                    <div class="meta-item" id="stockStatusWrapper">
                        Kho: <strong id="selectedStock" class="stock-status in-stock">Còn hàng</strong>
                    </div>
                </div>

                <!-- Box Giá -->
                <div class="price-box">
                    <div class="current-price" id="selectedPrice">0₫</div>
                    <div class="original-price" id="selectedOriginalPrice" style="display:none;">0₫</div>
                    <div class="discount-pill" id="selectedDiscountTag" style="display:none;">Tiết kiệm 0%</div>
                </div>

                <!-- Bộ chọn biến thể (Variant Selector) -->
                <div class="variant-section" id="variantSection">
                    <label class="variant-label">
                        <span>Phiên bản / Màu sắc:</span>
                        <strong id="selectedVariantName">---</strong>
                    </label>
                    <div class="variant-pills" id="variantSelector">
                        <!-- Danh sách variant pills render từ product-detail.js -->
                    </div>
                </div>

                <!-- Bộ chọn số lượng & Nút mua -->
                <div class="purchase-section">
                    <div class="quantity-group">
                        <label for="detailQty" class="quantity-label">Số lượng:</label>
                        <div class="quantity-control">
                            <button type="button" class="btn-qty" id="btnQtyMinus" aria-label="Giảm">-</button>
                            <input type="number" id="detailQty" value="1" min="1" max="1">
                            <button type="button" class="btn-qty" id="btnQtyPlus" aria-label="Tăng">+</button>
                        </div>
                    </div>

                    <div class="action-buttons-group">
                        <button type="button" class="btn-add-cart" id="btnAddToCart">
                            <i class="fa-solid fa-cart-plus"></i> Thêm vào giỏ hàng
                        </button>
                        <button type="button" class="btn-buy-now" id="btnBuyNow">
                            <i class="fa-solid fa-bolt"></i> Mua ngay
                        </button>
                    </div>
                </div>

                <!-- Cam kết mua hàng -->
                <div class="policy-box">
                    <div class="policy-item">
                        <i class="fa-solid fa-truck-fast"></i>
                        <span>Giao hàng hỏa tốc trong 2 giờ</span>
                    </div>
                    <div class="policy-item">
                        <i class="fa-solid fa-rotate-left"></i>
                        <span>Đổi trả miễn phí 30 ngày nếu lỗi</span>
                    </div>
                    <div class="policy-item">
                        <i class="fa-solid fa-shield-check"></i>
                        <span>Bảo hành 12 tháng chính hãng</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Phần dưới: Mô tả & Bảng Thông số kỹ thuật -->
        <div class="detail-bottom-section">
            <div class="bottom-grid">
                <!-- Cột mô tả -->
                <div class="description-card">
                    <div class="ts-card-header">
                        <h3><i class="fa-solid fa-circle-info"></i> Mô tả sản phẩm</h3>
                    </div>
                    <div class="ts-card-body description-content" id="productDescription">
                        Đang cập nhật nội dung...
                    </div>
                </div>

                <!-- Cột thông số kỹ thuật -->
                <div class="specs-card">
                    <div class="ts-card-header">
                        <h3><i class="fa-solid fa-microchip"></i> Thông số kỹ thuật</h3>
                    </div>
                    <div class="ts-card-body">
                        <table class="specs-table" id="specsTable">
                            <tbody id="specsTableBody">
                                <!-- Render từ product-detail.js -->
                            </tbody>
                        </table>
                        <div id="noSpecsNotice" style="display:none; color:#64748b; font-size:14px; text-align:center; padding: 20px 0;">
                            Chưa có thông số kỹ thuật chi tiết.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Khu vực: Sản phẩm cùng danh mục -->
        <section class="related-section" id="relatedSection">
            <div class="section-title">
                <h2><i class="fa-solid fa-layer-group"></i> Sản phẩm tương tự</h2>
            </div>
            <div class="product-grid" id="relatedProductsGrid">
                <!-- Render từ product-detail.js -->
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script src="/assets/js/pages/product-detail.js"></script>
@endpush
