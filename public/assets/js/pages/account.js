/**
 * TechShop - Customer Account Page JavaScript
 * Quản lý trang tài khoản: lịch sử đơn hàng, cập nhật hồ sơ, đổi mật khẩu, đăng xuất
 */

document.addEventListener('DOMContentLoaded', async function () {
    const formatPrice = (v) => {
        if (window.Utils && typeof window.Utils.formatPrice === 'function') {
            return window.Utils.formatPrice(v);
        }
        return Number(v || 0).toLocaleString('vi-VN') + ' ₫';
    };

    const formatDate = (str) => {
        if (window.Utils && typeof window.Utils.formatDate === 'function') {
            return window.Utils.formatDate(str);
        }
        if (!str) return '--';
        try {
            const d = new Date(str);
            return d.toLocaleString('vi-VN', {
                year: 'numeric', month: '2-digit', day: '2-digit',
                hour: '2-digit', minute: '2-digit'
            });
        } catch (e) {
            return str;
        }
    };

    const escapeHtml = (s) => {
        if (window.Utils && typeof window.Utils.escapeHtml === 'function') {
            return window.Utils.escapeHtml(s);
        }
        return String(s || '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    };

    const showToast = (msg, type = 'info') => {
        if (window.Utils && typeof window.Utils.showToast === 'function') {
            window.Utils.showToast(msg, type);
        } else {
            alert(msg);
        }
    };

    const api = {
        async get(url) {
            if (window.ApiClient && typeof window.ApiClient.get === 'function') {
                return window.ApiClient.get(url);
            }
            const res = await fetch(url, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                const err = new Error(data?.error?.message || 'Yêu cầu không thành công.');
                err.status = res.status;
                err.code = data?.error?.code;
                err.details = data?.error?.details;
                throw err;
            }
            return data;
        },
        async post(url, body) {
            if (window.ApiClient && typeof window.ApiClient.post === 'function') {
                return window.ApiClient.post(url, body);
            }
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                credentials: 'same-origin',
                body: JSON.stringify(body)
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                const err = new Error(data?.error?.message || 'Yêu cầu không thành công.');
                err.status = res.status;
                err.code = data?.error?.code;
                err.details = data?.error?.details;
                throw err;
            }
            return data;
        },
        async patch(url, body) {
            if (window.ApiClient && typeof window.ApiClient.patch === 'function') {
                return window.ApiClient.patch(url, body);
            }
            const res = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                credentials: 'same-origin',
                body: JSON.stringify(body)
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                const err = new Error(data?.error?.message || 'Yêu cầu không thành công.');
                err.status = res.status;
                err.code = data?.error?.code;
                err.details = data?.error?.details;
                throw err;
            }
            return data;
        }
    };

    const elements = {
        loading: document.getElementById('account-loading'),
        content: document.getElementById('account-content'),
        avatarText: document.getElementById('user-avatar-text'),
        displayName: document.getElementById('user-display-name'),
        displayEmail: document.getElementById('user-display-email'),
        displayRole: document.getElementById('user-display-role'),
        btnLogout: document.getElementById('btn-logout'),
        ordersList: document.getElementById('orders-list'),
        ordersLoading: document.getElementById('orders-loading'),
        ordersEmpty: document.getElementById('orders-empty'),
        ordersPagination: document.getElementById('orders-pagination'),
        paginationUl: document.getElementById('pagination-ul'),
        filterBtns: document.querySelectorAll('.order-filter-pill, .order-filter-btn'),
        profileForm: document.getElementById('profile-form'),
        profFullname: document.getElementById('prof-fullname'),
        profEmail: document.getElementById('prof-email'),
        profPhone: document.getElementById('prof-phone'),
        profAddress: document.getElementById('prof-address'),
        btnSaveProfile: document.getElementById('btn-save-profile'),
        passwordForm: document.getElementById('password-form'),
        pwdCurrent: document.getElementById('pwd-current'),
        pwdNew: document.getElementById('pwd-new'),
        pwdConfirm: document.getElementById('pwd-confirm'),
        btnSavePassword: document.getElementById('btn-save-password')
    };

    let currentUser = null;
    let currentFilterStatus = '';
    let currentPage = 1;

    /**
     * Khởi tạo trang Account
     */
    async function initAccount() {
        try {
            const authRes = await api.get('/api/auth/me');
            const user = authRes?.data || authRes;

            if (!user || (!user.id && !user.email)) {
                throw new Error('Chưa đăng nhập');
            }

            currentUser = user;
            renderUserInfo(currentUser);

            elements.loading.classList.add('d-none');
            elements.content.classList.remove('d-none');

            // Xử lý hash URL để mở đúng tab nếu có
            const hash = window.location.hash.replace('#', '') || 'orders';
            switchTab(hash);

            // Tải danh sách đơn hàng ban đầu
            await loadOrders();
        } catch (error) {
            console.error('Lỗi khởi tạo tài khoản:', error);
            window.location.href = '/login?redirect=' + encodeURIComponent('/account');
        }
    }

    /**
     * Render thông tin người dùng vào sidebar và form
     */
    function renderUserInfo(user) {
        elements.displayName.textContent = user.fullname || 'Người dùng';
        elements.displayEmail.textContent = user.email || '';
        elements.avatarText.textContent = (user.fullname || 'U').charAt(0).toUpperCase();

        if (user.role === 'admin') {
            elements.displayRole.textContent = 'Quản trị viên';
            elements.displayRole.className = 'badge bg-danger mt-2';
        } else {
            elements.displayRole.textContent = 'Khách hàng';
            elements.displayRole.className = 'badge bg-secondary mt-2';
        }

        // Form profile
        elements.profFullname.value = user.fullname || '';
        elements.profEmail.value = user.email || '';
        elements.profPhone.value = user.phone || '';
        elements.profAddress.value = user.address || '';
    }

    /**
     * Chuyển tab giữa: orders, profile, password
     */
    function switchTab(tabName) {
        document.querySelectorAll('.tab-nav-link').forEach(link => {
            if (link.getAttribute('data-tab') === tabName) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });

        document.querySelectorAll('.tab-pane-content').forEach(pane => {
            pane.classList.add('d-none');
        });

        const targetPane = document.getElementById(`pane-${tabName}`);
        if (targetPane) {
            targetPane.classList.remove('d-none');
        }
    }

    // Gắn sự kiện chuyển tab
    document.querySelectorAll('.tab-nav-link').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const tabName = this.getAttribute('data-tab');
            window.location.hash = tabName;
            switchTab(tabName);
        });
    });

    /**
     * Tải danh sách đơn hàng của người dùng
     */
    async function loadOrders(page = 1) {
        currentPage = page;
        elements.ordersLoading.classList.remove('d-none');
        elements.ordersEmpty.classList.add('d-none');
        elements.ordersList.innerHTML = '';
        elements.ordersPagination.classList.add('d-none');

        try {
            let url = `/api/orders?page=${page}&page_size=5`;
            if (currentFilterStatus) {
                url += `&status=${encodeURIComponent(currentFilterStatus)}`;
            }

            const res = await api.get(url);
            const items = res?.data || [];
            const meta = res?.meta || {};

            if (items.length === 0) {
                elements.ordersEmpty.classList.remove('d-none');
            } else {
                renderOrdersList(items);
                if (meta.total_pages > 1) {
                    renderPagination(meta.page, meta.total_pages);
                }
            }
        } catch (error) {
            console.error('Lỗi tải danh sách đơn hàng:', error);
            if (error?.status === 401) {
                window.location.href = '/login?redirect=' + encodeURIComponent('/account');
                return;
            }
            const errMsg = error?.message || error?.error?.message || 'Lỗi kết nối khi tải danh sách đơn hàng.';
            showToast(errMsg, 'error');
        } finally {
            elements.ordersLoading.classList.add('d-none');
        }
    }

    /**
     * Render danh sách card đơn hàng
     */
    function renderOrdersList(orders) {
        const badgeMap = {
            'Pending': { label: 'Chờ xử lý', class: 'bg-warning text-dark' },
            'Confirmed': { label: 'Đã xác nhận', class: 'bg-info text-dark' },
            'Processing': { label: 'Đang đóng gói', class: 'bg-primary' },
            'Shipped': { label: 'Đang giao', class: 'bg-info text-dark' },
            'Delivered': { label: 'Đã giao', class: 'bg-success' },
            'Cancelled': { label: 'Đã hủy', class: 'bg-danger' }
        };

        orders.forEach(order => {
            const card = document.createElement('div');
            card.className = 'ts-card mb-3';

            const statusConfig = badgeMap[order.status] || { label: order.status, class: 'bg-secondary' };
            const paymentStatusBadge = order.payment_status === 'Paid'
                ? '<span class="badge bg-success ms-1">Đã thanh toán</span>'
                : '<span class="badge bg-light text-muted border ms-1">Chưa thanh toán</span>';

            card.innerHTML = `
                <div class="ts-card-header py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <span class="fw-bold text-dark">#${escapeHtml(order.order_number)}</span>
                        <span class="text-muted small ms-2">• ${formatDate(order.created_at)}</span>
                    </div>
                    <div>
                        <span class="badge ${statusConfig.class}">${statusConfig.label}</span>
                        ${paymentStatusBadge}
                    </div>
                </div>
                <div class="ts-card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Phương thức: <strong class="text-dark">${escapeHtml(order.payment_method || 'COD')}</strong></div>
                            <div class="text-muted small mt-1">Người nhận: ${escapeHtml(order.shipping_address?.fullname || order.receiver_name || '--')}</div>
                        </div>
                        <div class="text-end">
                            <div class="text-muted small">Tổng tiền:</div>
                            <div class="fw-bold text-danger fs-5">${formatPrice(order.total_amount)}</div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white py-2 text-end border-top">
                    <a href="/orders/${encodeURIComponent(order.order_number)}" class="btn btn-outline-primary btn-sm px-3 fw-medium rounded-pill">
                        Xem chi tiết <i class="fa-solid fa-chevron-right ms-1"></i>
                    </a>
                </div>
            `;

            elements.ordersList.appendChild(card);
        });
    }

    /**
     * Render phân trang
     */
    function renderPagination(currentPage, totalPages) {
        elements.paginationUl.innerHTML = '';
        elements.ordersPagination.classList.remove('d-none');

        // Nút Prev
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${currentPage <= 1 ? 'disabled' : ''}`;
        prevLi.innerHTML = `<a class="page-link" href="#" aria-label="Previous">&laquo;</a>`;
        if (currentPage > 1) {
            prevLi.addEventListener('click', (e) => {
                e.preventDefault();
                loadOrders(currentPage - 1);
            });
        }
        elements.paginationUl.appendChild(prevLi);

        // Các trang số
        for (let i = 1; i <= totalPages; i++) {
            const pageLi = document.createElement('li');
            pageLi.className = `page-item ${i === currentPage ? 'active' : ''}`;
            pageLi.innerHTML = `<a class="page-link" href="#">${i}</a>`;
            pageLi.addEventListener('click', (e) => {
                e.preventDefault();
                loadOrders(i);
            });
            elements.paginationUl.appendChild(pageLi);
        }

        // Nút Next
        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${currentPage >= totalPages ? 'disabled' : ''}`;
        nextLi.innerHTML = `<a class="page-link" href="#" aria-label="Next">&raquo;</a>`;
        if (currentPage < totalPages) {
            nextLi.addEventListener('click', (e) => {
                e.preventDefault();
                loadOrders(currentPage + 1);
            });
        }
        elements.paginationUl.appendChild(nextLi);
    }

    // Sự kiện click filter trạng thái đơn hàng
    elements.filterBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            elements.filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            currentFilterStatus = this.getAttribute('data-status') || '';
            loadOrders(1);
        });
    });

    /**
     * Cập nhật thông tin cá nhân
     */
    elements.profileForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const fullname = elements.profFullname.value.trim();
        const phone = elements.profPhone.value.trim();
        const address = elements.profAddress.value.trim();

        if (!fullname) {
            elements.profFullname.classList.add('is-invalid');
            return;
        }
        elements.profFullname.classList.remove('is-invalid');

        elements.btnSaveProfile.disabled = true;
        elements.btnSaveProfile.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Đang lưu...';

        try {
            const res = await api.patch('/api/profile', {
                fullname,
                phone: phone || null,
                address: address || null
            });
            const updatedData = res?.data || res;

            showToast('Cập nhật thông tin cá nhân thành công!', 'success');
            if (updatedData) {
                currentUser = { ...currentUser, ...updatedData };
                renderUserInfo(currentUser);
            }
        } catch (error) {
            console.error('Lỗi cập nhật profile:', error);
            let errMsg = error?.message || error?.error?.message || 'Không thể cập nhật thông tin.';
            if (error?.details && typeof error.details === 'object') {
                const detailMsgs = Object.values(error.details).flat().join(' ');
                if (detailMsgs) errMsg = detailMsgs;
            }
            showToast(errMsg, 'error');
        } finally {
            elements.btnSaveProfile.disabled = false;
            elements.btnSaveProfile.textContent = 'Lưu thay đổi';
        }
    });

    /**
     * Cập nhật mật khẩu
     */
    elements.passwordForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        elements.pwdCurrent.classList.remove('is-invalid');
        elements.pwdNew.classList.remove('is-invalid');
        elements.pwdConfirm.classList.remove('is-invalid');

        const currentPassword = elements.pwdCurrent.value;
        const newPassword = elements.pwdNew.value;
        const confirmPassword = elements.pwdConfirm.value;

        let isValid = true;
        if (!currentPassword) {
            elements.pwdCurrent.classList.add('is-invalid');
            isValid = false;
        }
        if (!newPassword || newPassword.length < 8) {
            elements.pwdNew.classList.add('is-invalid');
            isValid = false;
        }
        if (newPassword !== confirmPassword) {
            elements.pwdConfirm.classList.add('is-invalid');
            isValid = false;
        }

        if (!isValid) return;

        elements.btnSavePassword.disabled = true;
        elements.btnSavePassword.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Đang xử lý...';

        try {
            await api.post('/api/auth/change-password', {
                current_password: currentPassword,
                new_password: newPassword,
                new_password_confirmation: confirmPassword
            });

            showToast('Đổi mật khẩu thành công!', 'success');
            elements.passwordForm.reset();
        } catch (error) {
            console.error('Lỗi đổi mật khẩu:', error);
            let errMsg = error?.message || error?.error?.message || 'Không thể đổi mật khẩu. Vui lòng kiểm tra lại mật khẩu hiện tại.';
            if (error?.details && typeof error.details === 'object') {
                const detailMsgs = Object.values(error.details).flat().join(' ');
                if (detailMsgs) errMsg = detailMsgs;
            }
            showToast(errMsg, 'error');
        } finally {
            elements.btnSavePassword.disabled = false;
            elements.btnSavePassword.textContent = 'Cập nhật mật khẩu';
        }
    });

    /**
     * Xử lý Đăng xuất
     */
    elements.btnLogout.addEventListener('click', async function () {
        if (!confirm('Bạn có chắc chắn muốn đăng xuất khỏi hệ thống?')) {
            return;
        }

        try {
            await api.post('/api/auth/logout', {});
        } catch (e) {
            // bỏ qua lỗi nếu có
        }

        showToast('Đã đăng xuất thành công.', 'info');
        setTimeout(() => {
            window.location.href = '/';
        }, 300);
    });

    // Khởi chạy
    initAccount();
});
