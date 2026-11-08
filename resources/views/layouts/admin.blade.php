<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard - TechShop')</title>

    <!-- FontAwesome 6.5.1 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Bootstrap Icons 1.11.3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Google Font: Be Vietnam Pro -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.3 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <!-- Admin Dedicated Stylesheet -->
    <link rel="stylesheet" href="/assets/css/admin.css?v=2.1">

    @stack('styles')
</head>
<body>

<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="admin-brand">
            <i class="fa-solid fa-bolt"></i> TechShop Admin
        </div>
        <nav>
            <a href="/admin" class="{{ request()->is('admin') ? 'active' : '' }}">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>
            <a href="/admin/categories" class="{{ request()->is('admin/categories*') ? 'active' : '' }}">
                <i class="fa-solid fa-layer-group"></i> Danh mục
            </a>
            <a href="/admin/products" class="{{ request()->is('admin/products*') ? 'active' : '' }}">
                <i class="fa-solid fa-box"></i> Sản phẩm
            </a>
            <a href="/admin/inventory" class="{{ request()->is('admin/inventory*') ? 'active' : '' }}">
                <i class="fa-solid fa-warehouse"></i> Kho hàng
            </a>
            <a href="/admin/orders" class="{{ request()->is('admin/orders*') ? 'active' : '' }}">
                <i class="fa-solid fa-receipt"></i> Đơn hàng
            </a>
            <a href="/admin/payments" class="{{ request()->is('admin/payments*') ? 'active' : '' }}">
                <i class="fa-solid fa-credit-card"></i> Thanh toán
            </a>
            <a href="/admin/users" class="{{ request()->is('admin/users*') ? 'active' : '' }}">
                <i class="fa-solid fa-users"></i> Người dùng
            </a>

            <div style="margin: 16px 14px; border-top: 1px solid rgba(255,255,255,0.08);"></div>

            <a href="/" target="_blank">
                <i class="fa-solid fa-store"></i> Xem cửa hàng
            </a>
            <a href="javascript:void(0)" id="adminLogoutBtn" class="text-danger">
                <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
            </a>
        </nav>
    </aside>

    <!-- Admin Content Area -->
    <div class="admin-content">
        <!-- Top bar -->
        <header class="admin-topbar">
            <div>
                <h2>@yield('page_title', 'Hệ thống quản trị')</h2>
            </div>
            <div class="admin-user-pill">
                <div class="admin-user-avatar" id="adminAvatarInitial">A</div>
                <div>
                    <strong id="adminUserName" style="font-size: 14px;">Quản trị viên</strong>
                    <div><span class="admin-role-badge" id="adminUserRole">Admin</span></div>
                </div>
            </div>
        </header>

        <!-- Main View Content -->
        <main>
            @yield('content')
        </main>
    </div>
</div>

<!-- Global Toast Container (ts-toast không xung đột với Bootstrap 5) -->
<div id="tsToastContainer" class="ts-toast-container"></div>

<!-- Bootstrap 5.3.3 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Core JS -->
<script src="/assets/js/core/utils.js"></script>
<script src="/assets/js/core/api.js"></script>

<!-- Services -->
<script src="/assets/js/services/authService.js"></script>
<script src="/assets/js/services/catalogService.js"></script>
<script src="/assets/js/services/cartService.js"></script>
<script src="/assets/js/services/orderService.js"></script>
<script src="/assets/js/services/paymentService.js"></script>
<script src="/assets/js/services/adminService.js"></script>

<!-- Auth Protection -->
<script src="/assets/js/core/auth.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', async function() {
        try {
            // Kiểm tra quyền Admin, redirect về /login nếu không đủ quyền
            const user = await window.AppAuth.requireAdmin();
            if (user) {
                const nameEl = document.getElementById('adminUserName');
                const roleEl = document.getElementById('adminUserRole');
                const avatarEl = document.getElementById('adminAvatarInitial');

                const displayName = user.fullname || user.full_name || user.email || 'Admin';
                if (nameEl) nameEl.textContent = displayName;
                if (roleEl && user.role) roleEl.textContent = typeof user.role === 'object' ? (user.role.name || user.role.code) : user.role;
                if (avatarEl) avatarEl.textContent = displayName.charAt(0).toUpperCase();
            }
        } catch (e) {
            console.error('Lỗi khởi tạo Admin:', e);
        }

        // Bắt sự kiện đăng xuất
        const logoutBtn = document.getElementById('adminLogoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', async function(e) {
                e.preventDefault();
                if (confirm('Bạn có chắc chắn muốn đăng xuất khỏi trang quản trị?')) {
                    try {
                        await window.AuthService.logout();
                    } catch (err) {
                        console.error('Logout error:', err);
                    }
                    window.location.href = '/login';
                }
            });
        }
    });
</script>

@stack('scripts')
</body>
</html>
