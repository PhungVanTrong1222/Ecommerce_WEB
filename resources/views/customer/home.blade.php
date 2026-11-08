@extends('layouts.app')

@section('title', 'TechShop - Thế giới công nghệ chính hãng, điện thoại, laptop, phụ kiện')

@push('styles')
<link rel="stylesheet" href="/assets/css/storefront-pages.css?v=2.1">
@endpush

@section('content')
<div class="home-page">
    <!-- 1. Hero Banners: Slider 3 ảnh + 2 Side Banners -->
    <section class="hero-banners">
        <div class="hero-slider" id="heroSlider">
            <div class="slides" id="heroSlides">
                <div class="slide">
                    <img src="/assets/images/banner_iphone.png" alt="iPhone 15 Pro Max" onerror="this.src='https://images.unsplash.com/photo-1695048133142-1a20484d2569?w=1200&q=80'">
                    <div class="slide-overlay"></div>
                    <div class="slide-content">
                        <span class="slide-badge">Siêu phẩm 2026</span>
                        <h2>iPhone 15 Pro Max</h2>
                        <p>Khung Titan chuẩn hàng không vũ trụ. Chip Apple A17 Pro đỉnh cao hiệu năng đồ họa.</p>
                        <a href="/products?category=dien-thoai" class="btn btn-primary btn-lg">Khám phá ngay <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </div>
                <div class="slide">
                    <img src="/assets/images/banner_macbook.png" alt="MacBook Air M3" onerror="this.src='https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=1200&q=80'">
                    <div class="slide-overlay"></div>
                    <div class="slide-content">
                        <span class="slide-badge">Mỏng nhẹ ấn tượng</span>
                        <h2>MacBook Air M3</h2>
                        <p>Hiệu năng vượt bậc với chip M3 tân tiến. Thời lượng pin bền bỉ tới 18 giờ làm việc.</p>
                        <a href="/products?category=laptop" class="btn btn-primary btn-lg">Mua ngay <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </div>
                <div class="slide">
                    <img src="/assets/images/banner_sale.png" alt="Đại tiệc công nghệ" onerror="this.src='https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=1200&q=80'">
                    <div class="slide-overlay"></div>
                    <div class="slide-content">
                        <span class="slide-badge">Flash Sale đặc biệt</span>
                        <h2>Lễ hội giảm giá sốc</h2>
                        <p>Ưu đãi đến 30% cho hàng loạt điện thoại, tablet, tai nghe và phụ kiện công nghệ.</p>
                        <a href="/products?sort=price_asc" class="btn btn-primary btn-lg">Xem khuyến mãi <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <button class="slider-arrow prev" id="sliderPrevBtn" aria-label="Slide trước"><i class="fa-solid fa-chevron-left"></i></button>
            <button class="slider-arrow next" id="sliderNextBtn" aria-label="Slide tiếp theo"><i class="fa-solid fa-chevron-right"></i></button>
            <div class="slider-dots" id="sliderDots">
                <button class="dot active" data-index="0" aria-label="Slide 1"></button>
                <button class="dot" data-index="1" aria-label="Slide 2"></button>
                <button class="dot" data-index="2" aria-label="Slide 3"></button>
            </div>
        </div>

        <div class="hero-side-banners">
            <a href="/products?category=phu-kien" class="side-banner side-banner-1">
                <div class="side-banner-content">
                    <span class="side-banner-tag">Giảm đến 25%</span>
                    <h4>Phụ kiện công nghệ</h4>
                    <p class="side-banner-desc">Chuột, phím, tai nghe chất lượng cao</p>
                    <span class="side-banner-link">Mua ngay →</span>
                </div>
                <div class="side-banner-icon"><i class="fa-solid fa-headphones"></i></div>
            </a>
            <a href="/products?category=laptop" class="side-banner side-banner-2">
                <div class="side-banner-content">
                    <span class="side-banner-tag">Hàng mới về</span>
                    <h4>Laptop doanh nhân & Gaming</h4>
                    <p class="side-banner-desc">Dell XPS, ROG Zephyrus cấu hình khủng</p>
                    <span class="side-banner-link">Khám phá →</span>
                </div>
                <div class="side-banner-icon"><i class="fa-solid fa-laptop"></i></div>
            </a>
        </div>
    </section>

    <!-- 2. Danh mục sản phẩm (Categories Grid) -->
    <section class="categories-section">
        <div class="section-title">
            <h2><i class="fa-solid fa-shapes"></i> Danh mục sản phẩm</h2>
            <a href="/products">Xem tất cả <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <div class="category-grid" id="categoryGrid">
            <!-- Nạp động từ home.js -->
            <div class="category-card skeleton-card"><div class="skeleton-icon" style="width:52px;height:52px;border-radius:50%;margin-bottom:12px;"></div><span class="skeleton-text" style="width:70%;"></span></div>
            <div class="category-card skeleton-card"><div class="skeleton-icon" style="width:52px;height:52px;border-radius:50%;margin-bottom:12px;"></div><span class="skeleton-text" style="width:70%;"></span></div>
            <div class="category-card skeleton-card"><div class="skeleton-icon" style="width:52px;height:52px;border-radius:50%;margin-bottom:12px;"></div><span class="skeleton-text" style="width:70%;"></span></div>
            <div class="category-card skeleton-card"><div class="skeleton-icon" style="width:52px;height:52px;border-radius:50%;margin-bottom:12px;"></div><span class="skeleton-text" style="width:70%;"></span></div>
            <div class="category-card skeleton-card"><div class="skeleton-icon" style="width:52px;height:52px;border-radius:50%;margin-bottom:12px;"></div><span class="skeleton-text" style="width:70%;"></span></div>
            <div class="category-card skeleton-card"><div class="skeleton-icon" style="width:52px;height:52px;border-radius:50%;margin-bottom:12px;"></div><span class="skeleton-text" style="width:70%;"></span></div>
        </div>
    </section>

    <!-- 3. Khu vực: Giảm giá sốc -->
    <section class="products-section" id="flashSaleSection">
        <div class="section-title">
            <h2><i class="fa-solid fa-fire" style="color: #e11d48;"></i> Giảm giá sốc</h2>
            <a href="/products?sort=price_asc">Xem tất cả <i class="fa-solid fa-chevron-right"></i></a>
        </div>
        <div class="product-grid" id="flashSaleGrid">
            <!-- Render động từ home.js -->
        </div>
    </section>

    <!-- 4. Khu vực: Sản phẩm mới nhất -->
    <section class="products-section" id="newProductsSection">
        <div class="section-title">
            <h2><i class="fa-solid fa-star" style="color: #f59e0b;"></i> Sản phẩm mới nhất</h2>
            <a href="/products?sort=newest">Xem tất cả <i class="fa-solid fa-chevron-right"></i></a>
        </div>
        <div class="product-grid" id="newProductsGrid">
            <!-- Render động từ home.js -->
        </div>
    </section>

    <!-- 5. Khu vực: Sản phẩm cao cấp nổi bật -->
    <section class="products-section" id="premiumProductsSection">
        <div class="section-title">
            <h2><i class="fa-solid fa-crown" style="color: #8b5cf6;"></i> Sản phẩm cao cấp nổi bật</h2>
            <a href="/products?sort=price_desc">Xem tất cả <i class="fa-solid fa-chevron-right"></i></a>
        </div>
        <div class="product-grid" id="premiumProductsGrid">
            <!-- Render động từ home.js -->
        </div>
    </section>

    <!-- 6. Features Bar -->
    <section class="features-bar">
        <div class="feature-item">
            <div class="feature-icon"><i class="fa-solid fa-store"></i></div>
            <div class="feature-text">
                <h4>Nhận tại cửa hàng</h4>
                <p>Miễn phí, nhanh chóng trên toàn quốc</p>
            </div>
        </div>
        <div class="feature-item">
            <div class="feature-icon"><i class="fa-solid fa-truck-fast"></i></div>
            <div class="feature-text">
                <h4>Giao hàng miễn phí</h4>
                <p>Cho đơn hàng từ 500.000₫ trở lên</p>
            </div>
        </div>
        <div class="feature-item">
            <div class="feature-icon"><i class="fa-solid fa-shield-halved"></i></div>
            <div class="feature-text">
                <h4>Bảo hành chính hãng</h4>
                <p>1 đổi 1 trong 30 ngày nếu lỗi</p>
            </div>
        </div>
        <div class="feature-item">
            <div class="feature-icon"><i class="fa-solid fa-headset"></i></div>
            <div class="feature-text">
                <h4>Hỗ trợ 24/7</h4>
                <p>Tư vấn kỹ thuật tận tình, chuyên nghiệp</p>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script src="/assets/js/pages/home.js"></script>
@endpush
