/**
 * AppAuth - Global Session & Header Controller
 */
(function () {
    let currentUser = null;
    let isInitialized = false;
    let initPromise = null;

    window.AppAuth = {
        /**
         * Lấy user hiện tại đã cache trong session
         */
        getUser: function () {
            return currentUser;
        },

        /**
         * Kiểm tra user đã đăng nhập chưa
         */
        isAuthenticated: function () {
            return !!currentUser;
        },

        /**
         * Kiểm tra user có phải quản trị viên (role admin)
         */
        isAdmin: function () {
            if (!currentUser) return false;
            if (currentUser.role === 'admin' || currentUser.role === 'super_admin') return true;
            if (typeof currentUser.role === 'object' && (currentUser.role?.code === 'admin' || currentUser.role?.code === 'super_admin')) return true;
            return false;
        },

        /**
         * Kiểm tra user có mã quyền cụ thể không
         */
        hasPermission: function (permissionCode) {
            if (!currentUser || !Array.isArray(currentUser.permissions)) {
                return false;
            }
            return currentUser.permissions.includes(permissionCode);
        },

        /**
         * Khởi tạo Auth: tải thông tin me(), cập nhật UI Header & Cart Badge
         */
        init: function () {
            if (initPromise) {
                return initPromise;
            }

            initPromise = (async () => {
                await Promise.allSettled([
                    (async () => {
                        try {
                            currentUser = await window.AuthService.me();
                        } catch (e) {
                            currentUser = null;
                        }
                        this.renderHeaderUser();
                    })(),
                    this.refreshCartBadge(),
                    this.renderCategoryNav()
                ]);

                isInitialized = true;

                // Lắng nghe sự kiện giỏ hàng cập nhật
                window.addEventListener('cart:updated', (e) => {
                    const cart = e.detail;
                    const qty = cart?.total_quantity ?? cart?.data?.total_quantity ?? 0;
                    this.updateCartBadge(qty);
                });

                return currentUser;
            })();

            return initPromise;
        },

        /**
         * Cập nhật giao diện khu vực người dùng trên Header (#headerUserArea)
         */
        renderHeaderUser: function () {
            const userArea = document.getElementById('headerUserArea');
            if (!userArea) return;

            if (this.isAuthenticated()) {
                const rawName = currentUser.fullname || currentUser.full_name || currentUser.email || 'Tài khoản';
                const name = window.Utils.escapeHtml(rawName);
                const initial = window.Utils.escapeHtml(rawName.charAt(0).toUpperCase());
                const isAdmin = this.isAdmin();

                userArea.innerHTML = `
                    <div class="user-menu-dropdown position-relative d-inline-block">
                        <button type="button" id="btnUserMenu" class="header-user-pill" aria-expanded="false">
                            <span class="user-avatar-circle">${initial}</span>
                            <span class="user-name-text">${name}</span>
                            <i class="fa-solid fa-chevron-down caret-icon"></i>
                        </button>
                        <div id="userDropdownContent" class="user-dropdown-menu">
                            <div class="user-dropdown-header">
                                <div class="fw-semibold text-truncate">${name}</div>
                                <div class="text-muted small text-truncate">${window.Utils.escapeHtml(currentUser.email || '')}</div>
                            </div>
                            <a href="/account" class="dropdown-item-link">
                                <i class="fa-solid fa-id-card"></i> Tài khoản của tôi
                            </a>
                            <a href="/account?tab=orders" class="dropdown-item-link">
                                <i class="fa-solid fa-box-archive"></i> Đơn mua của tôi
                            </a>
                            ${isAdmin ? `
                            <a href="/admin" class="dropdown-item-link admin-entry-link">
                                <i class="fa-solid fa-gauge-high"></i> Trang Quản trị
                            </a>
                            ` : ''}
                            <div class="dropdown-divider-line"></div>
                            <button type="button" id="btnLogout" class="dropdown-item-link text-danger border-0 bg-transparent w-100 text-start">
                                <i class="fa-solid fa-arrow-right-from-bracket"></i> Đăng xuất
                            </button>
                        </div>
                    </div>
                `;

                const btn = document.getElementById('btnUserMenu');
                const dropdown = document.getElementById('userDropdownContent');
                if (btn && dropdown) {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        dropdown.classList.toggle('show');
                    });

                    document.addEventListener('click', () => {
                        dropdown.classList.remove('show');
                    });
                }

                const btnLogout = document.getElementById('btnLogout');
                if (btnLogout) {
                    btnLogout.addEventListener('click', async () => {
                        try {
                            await window.AuthService.logout();
                            window.Utils.showToast('Đã đăng xuất thành công', 'success');
                            setTimeout(() => {
                                window.location.href = '/login';
                            }, 500);
                        } catch (err) {
                            window.location.href = '/login';
                        }
                    });
                }
            } else {
                userArea.innerHTML = `
                    <div class="header-auth-actions d-flex align-items-center gap-2">
                        <a href="/login" class="header-auth-btn header-login-btn">
                            <i class="fa-regular fa-user"></i>
                            <span>Đăng nhập</span>
                        </a>
                        <a href="/register" class="header-auth-btn header-register-btn">
                            <span>Đăng ký</span>
                        </a>
                    </div>
                `;
            }
        },

        /**
         * Cập nhật số lượng hiển thị trên Badge giỏ hàng (#headerCartBadge)
         */
        updateCartBadge: function (count) {
            const badge = document.getElementById('headerCartBadge');
            if (!badge) return;

            const qty = parseInt(count, 10) || 0;
            if (qty > 0) {
                badge.textContent = qty > 99 ? '99+' : qty;
                badge.style.display = 'inline-flex';
            } else {
                badge.textContent = '0';
                badge.style.display = 'none';
            }
        },

        /**
         * Lấy giỏ hàng và làm mới Badge
         */
        refreshCartBadge: async function () {
            try {
                const cart = await window.CartService.getCart();
                const qty = cart?.total_quantity ?? cart?.data?.total_quantity ?? 0;
                this.updateCartBadge(qty);
            } catch (e) {
                this.updateCartBadge(0);
            }
        },

        /**
         * Nạp danh mục động vào menu header (#headerCategoryNav) nếu tồn tại
         */
        renderCategoryNav: async function () {
            const nav = document.getElementById('headerCategoryNav');
            if (!nav) return;

            try {
                const res = await window.CatalogService.getCategories();
                const categories = Array.isArray(res) ? res : (res?.data || []);

                const currentPath = window.location.pathname;
                const currentSlug = window.Utils.getQueryParam('category');
                const onSaleParam = window.Utils.getQueryParam('on_sale');

                let html = `
                    <li class="${currentPath === '/' ? 'active' : ''}">
                        <a href="/"><i class="fa-solid fa-house me-1"></i> Trang chủ</a>
                    </li>
                    <li class="${currentPath === '/products' && !currentSlug && !onSaleParam ? 'active' : ''}">
                        <a href="/products">Tất cả sản phẩm</a>
                    </li>
                `;

                if (Array.isArray(categories) && categories.length > 0) {
                    categories.forEach(cat => {
                        const isActive = currentSlug === cat.slug;
                        html += `
                            <li class="${isActive ? 'active' : ''}">
                                <a href="/products?category=${encodeURIComponent(cat.slug)}">${window.Utils.escapeHtml(cat.name)}</a>
                            </li>
                        `;
                    });
                }

                html += `
                    <li class="ms-auto">
                        <a href="/products?on_sale=1" class="sale-link ${onSaleParam ? 'active' : ''}">
                            <i class="fa-solid fa-fire text-danger me-1"></i> Giảm giá sốc
                        </a>
                    </li>
                `;

                nav.innerHTML = html;
            } catch (err) {
                console.warn('Không thể nạp danh mục header:', err);
            }
        },

        /**
         * Yêu cầu người dùng phải đăng nhập trước khi xem trang
         * @param {string} redirectUrl Trang chuyển hướng khi chưa login
         */
        requireAuth: async function (redirectUrl = '/login') {
            await this.init();
            if (!this.isAuthenticated()) {
                const current = encodeURIComponent(window.location.pathname + window.location.search);
                window.location.href = `${redirectUrl}?redirect=${current}`;
                return false;
            }
            return true;
        },

        /**
         * Yêu cầu quyền quản trị viên (Admin)
         * @param {string} redirectUrl Trang chuyển hướng khi không có quyền
         */
        requireAdmin: async function (redirectUrl = '/') {
            await this.init();
            if (!this.isAuthenticated()) {
                const current = encodeURIComponent(window.location.pathname + window.location.search);
                window.location.href = `/login?redirect=${current}`;
                return false;
            }
            if (!this.isAdmin()) {
                window.Utils.showToast('Bạn không có quyền truy cập khu vực Quản trị', 'error');
                setTimeout(() => {
                    window.location.href = redirectUrl;
                }, 1000);
                return false;
            }
            return true;
        }
    };

    // Tự động khởi tạo khi DOM sẵn sàng
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => window.AppAuth.init());
    } else {
        window.AppAuth.init();
    }
})();
