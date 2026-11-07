/**
 * Admin Users Management JavaScript
 */
document.addEventListener('DOMContentLoaded', function () {
    let currentPage = 1;
    let currentAdminId = null;

    const searchInput = document.getElementById('userSearchInput');
    const roleFilter = document.getElementById('userRoleFilter');
    const statusFilter = document.getElementById('userStatusFilter');
    const btnFilter = document.getElementById('btnFilterUsers');
    const btnReload = document.getElementById('btnReloadUsers');
    const tableBody = document.getElementById('usersTableBody');
    const paginationContainer = document.getElementById('paginationUsers');

    async function loadCurrentAdminInfo() {
        try {
            const res = await ApiClient.get('/api/auth/me');
            if (res && res.success && res.data) {
                currentAdminId = String(res.data.id);
            }
        } catch (e) {
            console.warn('Chưa lấy được ID tài khoản admin hiện tại:', e);
        }
    }

    function getUserRoleBadge(role) {
        if (role === 'admin' || role === 'Admin') {
            return '<span class="badge bg-danger px-2 py-1"><i class="fas fa-user-shield me-1"></i>Quản trị viên</span>';
        }
        return '<span class="badge bg-info px-2 py-1"><i class="fas fa-user me-1"></i>Khách hàng</span>';
    }

    function getUserStatusBadge(status) {
        if (status === 'Active') {
            return '<span class="badge bg-success px-2 py-1"><i class="fas fa-check-circle me-1"></i>Hoạt động</span>';
        } else if (status === 'Suspended') {
            return '<span class="badge bg-danger px-2 py-1"><i class="fas fa-lock me-1"></i>Bị khóa</span>';
        }
        return `<span class="badge bg-secondary px-2 py-1">${Utils.escapeHtml(status || 'N/A')}</span>`;
    }

    async function loadUsers(page = 1) {
        currentPage = page;
        if (tableBody) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <span class="ml-2">Đang tải danh sách người dùng...</span>
                    </td>
                </tr>`;
        }

        const params = new URLSearchParams({
            page: currentPage,
            page_size: 15
        });

        const q = (searchInput?.value || '').trim();
        if (q) params.append('q', q);

        const role = roleFilter?.value;
        if (role) params.append('role', role);

        const status = statusFilter?.value;
        if (status) params.append('status', status);

        try {
            const res = await ApiClient.get('/api/admin/users?' + params.toString());
            if (res && res.success) {
                renderUsersTable(res.data || []);
                renderPagination(res.meta || {});
            } else {
                tableBody.innerHTML = `
                    <tr><td colspan="8" class="text-center py-4 text-danger">Không thể tải dữ liệu người dùng.</td></tr>`;
            }
        } catch (error) {
            console.error('Lỗi khi tải users:', error);
            tableBody.innerHTML = `
                <tr><td colspan="8" class="text-center py-4 text-danger">Đã có lỗi xảy ra khi kết nối máy chủ.</td></tr>`;
        }
    }

    function renderUsersTable(users) {
        if (!tableBody) return;

        if (users.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        Không tìm thấy người dùng nào phù hợp.
                    </td>
                </tr>`;
            return;
        }

        tableBody.innerHTML = users.map(u => {
            const isCurrentAdmin = currentAdminId && String(u.id) === String(currentAdminId);
            const isActive = u.status === 'Active';

            let actionHtml = '';
            if (isCurrentAdmin) {
                actionHtml = `<span class="badge bg-light text-dark border px-2 py-1"><i class="fas fa-user-check me-1"></i>Tài khoản của bạn</span>`;
            } else if (isActive) {
                actionHtml = `
                    <button class="btn btn-sm btn-outline-danger btn-toggle-user" data-id="${u.id}" data-action="Suspended" data-name="${Utils.escapeHtml(u.fullname)}">
                        <i class="fas fa-lock me-1"></i> Khóa tài khoản
                    </button>
                `;
            } else {
                actionHtml = `
                    <button class="btn btn-sm btn-outline-success btn-toggle-user" data-id="${u.id}" data-action="Active" data-name="${Utils.escapeHtml(u.fullname)}">
                        <i class="fas fa-unlock me-1"></i> Mở khóa
                    </button>
                `;
            }

            return `
                <tr>
                    <td><span class="text-muted fw-bold">#${Utils.escapeHtml(u.id)}</span></td>
                    <td>
                        <strong class="text-dark fs-6">${Utils.escapeHtml(u.fullname)}</strong>
                    </td>
                    <td>
                        <span class="text-dark fw-medium">${Utils.escapeHtml(u.email)}</span>
                    </td>
                    <td>
                        <span class="text-muted">${Utils.escapeHtml(u.phone || 'Chưa cập nhật')}</span>
                    </td>
                    <td class="text-center">
                        ${getUserRoleBadge(u.role_name || u.role)}
                    </td>
                    <td class="text-center">
                        ${getUserStatusBadge(u.status)}
                    </td>
                    <td class="text-muted small">
                        ${Utils.formatDate(u.created_at)}
                    </td>
                    <td class="text-end">
                        ${actionHtml}
                    </td>
                </tr>
            `;
        }).join('');

        tableBody.querySelectorAll('.btn-toggle-user').forEach(btn => {
            btn.addEventListener('click', function () {
                const userId = this.getAttribute('data-id');
                const targetStatus = this.getAttribute('data-action');
                const userName = this.getAttribute('data-name');
                toggleUserStatus(userId, targetStatus, userName, this);
            });
        });
    }

    async function toggleUserStatus(userId, targetStatus, userName, btnEl) {
        const actionText = targetStatus === 'Suspended' ? 'KHÓA' : 'MỞ KHÓA';
        const confirmMsg = targetStatus === 'Suspended'
            ? `Bạn có chắc chắn muốn KHÓA tài khoản của "${userName}"? Người dùng này sẽ không thể đăng nhập vào hệ thống.`
            : `Bạn có chắc chắn muốn MỞ KHÓA cho tài khoản của "${userName}"?`;

        if (!confirm(confirmMsg)) {
            return;
        }

        btnEl.disabled = true;
        const oldHtml = btnEl.innerHTML;
        btnEl.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span> Đang xử lý...';

        try {
            const res = await ApiClient.patch(`/api/admin/users/${userId}/status`, {
                status: targetStatus
            });

            if (res && res.success) {
                Utils.showToast(`Đã ${actionText.toLowerCase()} tài khoản thành công!`, 'success');
                loadUsers(currentPage);
            } else {
                Utils.showToast(res?.error?.message || `Không thể ${actionText.toLowerCase()} tài khoản`, 'error');
                btnEl.disabled = false;
                btnEl.innerHTML = oldHtml;
            }
        } catch (error) {
            Utils.showToast(error.message || 'Lỗi khi cập nhật trạng thái người dùng', 'error');
            btnEl.disabled = false;
            btnEl.innerHTML = oldHtml;
        }
    }

    function renderPagination(meta) {
        if (!paginationContainer) return;
        const total = meta.total || 0;
        const totalPages = meta.total_pages || Math.ceil(total / (meta.page_size || 15)) || 1;
        const current = meta.page || 1;

        if (totalPages <= 1) {
            paginationContainer.innerHTML = `<span class="text-muted small">Hiển thị tổng số <strong>${total}</strong> người dùng</span>`;
            return;
        }

        let pagesHtml = '';
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= current - 2 && i <= current + 2)) {
                pagesHtml += `
                    <button class="btn btn-sm ${i === current ? 'btn-primary font-weight-bold' : 'btn-outline-secondary'} mx-1 btn-page" data-page="${i}">
                        ${i}
                    </button>
                `;
            } else if (i === current - 3 || i === current + 3) {
                pagesHtml += '<span class="mx-1 text-muted">...</span>';
            }
        }

        paginationContainer.innerHTML = `
            <span class="text-muted small">
                Hiển thị trang <strong>${current}</strong> / <strong>${totalPages}</strong> (Tổng <strong>${total}</strong> người dùng)
            </span>
            <div>
                <button class="btn btn-sm btn-outline-secondary mr-1 btn-page" data-page="${current - 1}" ${current <= 1 ? 'disabled' : ''}>
                    &laquo; Trước
                </button>
                ${pagesHtml}
                <button class="btn btn-sm btn-outline-secondary ml-1 btn-page" data-page="${current + 1}" ${current >= totalPages ? 'disabled' : ''}>
                    Sau &raquo;
                </button>
            </div>
        `;

        paginationContainer.querySelectorAll('.btn-page').forEach(btn => {
            btn.addEventListener('click', function () {
                const p = parseInt(this.getAttribute('data-page'));
                if (p >= 1 && p <= totalPages && p !== current) {
                    loadUsers(p);
                }
            });
        });
    }

    btnFilter?.addEventListener('click', () => loadUsers(1));
    btnReload?.addEventListener('click', () => loadUsers(currentPage));
    searchInput?.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') loadUsers(1);
    });
    roleFilter?.addEventListener('change', () => loadUsers(1));
    statusFilter?.addEventListener('change', () => loadUsers(1));

    // Khởi tạo
    loadCurrentAdminInfo().then(() => {
        loadUsers(1);
    });
});
