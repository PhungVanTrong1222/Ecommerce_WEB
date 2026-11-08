<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'TechShop - Siêu thị điện thoại & laptop chính hãng')</title>

    <!-- FontAwesome 6.5.1 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Bootstrap Icons 1.11.3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Google Font: Be Vietnam Pro -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.3 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <!-- Storefront Design System CSS -->
    <link rel="stylesheet" href="/assets/css/style.css?v=2.2">

    @stack('styles')
</head>
<body>

<!-- Header -->
<header class="header">
    <div class="container header-main">
        <a href="/" class="logo">
            <i class="fa-solid fa-bolt"></i> Tech<span>Shop</span>
        </a>

        <!-- Form tìm kiếm chuẩn có nút bấm submit -->
        <form class="search-bar" action="/products" method="GET">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input type="text" name="q" placeholder="Tìm kiếm điện thoại, laptop, phụ kiện..." value="{{ request('q') }}" autocomplete="off">
            <button type="submit" class="search-submit-btn" aria-label="Tìm kiếm">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
        </form>

        <div class="header-actions">
            <!-- Nút Giỏ hàng sang trọng -->
            <a href="/cart" class="action-cart-btn" title="Xem giỏ hàng">
                <span class="cart-icon-wrap">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span id="headerCartBadge" class="badge-cart-count" style="display: none;">0</span>
                </span>
                <span class="d-none d-sm-inline">Giỏ hàng</span>
            </a>

            <!-- Khu vực User do window.AppAuth render -->
            <div id="headerUserArea" class="header-user-wrapper"></div>
        </div>
    </div>

    <!-- Navigation Menu Danh mục -->
    <div class="header-nav">
        <div class="container">
            <ul id="headerCategoryNav" class="nav-list">
                <li class="active"><a href="/"><i class="fa-solid fa-house me-1"></i> Trang chủ</a></li>
                <li><a href="/products">Tất cả sản phẩm</a></li>
                <!-- Danh mục sẽ được tải động qua CatalogService -->
            </ul>
        </div>
    </div>
</header>

<!-- Main content -->
<main class="main-content py-4">
    <div class="container">
        @yield('content')
    </div>
</main>

<!-- Toast container cho thông báo toàn cục (ts-toast không xung đột với Bootstrap 5) -->
<div id="tsToastContainer" class="ts-toast-container"></div>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <h3><i class="fa-solid fa-bolt text-warning"></i> TechShop</h3>
                <p>Siêu thị điện thoại, laptop & phụ kiện chính hãng uy tín hàng đầu Việt Nam. Cam kết hàng chính hãng 100%, bảo hành chuẩn hãng, giá cạnh tranh nhất thị trường.</p>
                <div class="footer-socials">
                    <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" aria-label="Youtube"><i class="fa-brands fa-youtube"></i></a>
                    <a href="#" aria-label="TikTok"><i class="fa-brands fa-tiktok"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                </div>
            </div>
            <div class="footer-col">
                <h3>Hỗ trợ khách hàng</h3>
                <ul>
                    <li><a href="#">Hướng dẫn mua hàng online</a></li>
                    <li><a href="#">Chính sách đổi trả & hoàn tiền</a></li>
                    <li><a href="#">Chính sách bảo hành</a></li>
                    <li><a href="#">Phương thức thanh toán VNPay / COD</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>Về TechShop</h3>
                <ul>
                    <li><a href="#">Giới thiệu công ty</a></li>
                    <li><a href="#">Tuyển dụng</a></li>
                    <li><a href="#">Chính sách bảo mật thông tin</a></li>
                    <li><a href="#">Điều khoản sử dụng dịch vụ</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>Thông tin liên hệ</h3>
                <ul>
                    <li><i class="fa-solid fa-location-dot me-2 text-primary"></i> 123 Nguyễn Huệ, Quận 1, TP.HCM</li>
                    <li><i class="fa-solid fa-phone me-2 text-primary"></i> 1900 1234 (8:00 - 21:00)</li>
                    <li><i class="fa-solid fa-envelope me-2 text-primary"></i> support@techshop.vn</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 TechShop. Nền tảng thương mại điện tử thiết bị công nghệ hàng đầu.</p>
        </div>
    </div>
</footer>

<!-- Bootstrap 5.3.3 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Core JavaScript -->
<script src="/assets/js/core/utils.js"></script>
<script src="/assets/js/core/api.js"></script>

<!-- Domain Services -->
<script src="/assets/js/services/authService.js"></script>
<script src="/assets/js/services/catalogService.js"></script>
<script src="/assets/js/services/cartService.js"></script>
<script src="/assets/js/services/orderService.js"></script>
<script src="/assets/js/services/paymentService.js"></script>

<!-- App Auth & Header Init -->
<script src="/assets/js/core/auth.js"></script>

@stack('scripts')
</body>
</html>
