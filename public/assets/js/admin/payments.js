/**
 * Admin Payments Management JavaScript
 */
document.addEventListener('DOMContentLoaded', function () {
    let currentPage = 1;

    const searchInput = document.getElementById('paymentSearchInput');
    const methodFilter = document.getElementById('paymentMethodFilter');
    const statusFilter = document.getElementById('paymentStatusFilter');
    const btnFilter = document.getElementById('btnFilterPayments');
    const btnReload = document.getElementById('btnReloadPayments');
    const tableBody = document.getElementById('paymentsTableBody');
    const paginationContainer = document.getElementById('paginationPayments');

    function getPaymentStatusBadge(status) {
        const map = {
            'Paid': '<span class="badge bg-success px-2 py-1"><i class="fas fa-check-circle me-1"></i>Đã thanh toán</span>',
            'Pending': '<span class="badge bg-warning text-dark px-2 py-1"><i class="fas fa-clock me-1"></i>Chờ thanh toán</span>',
            'Processing': '<span class="badge bg-info px-2 py-1"><i class="fas fa-spinner fa-spin me-1"></i>Đang xử lý</span>',
            'Failed': '<span class="badge bg-danger px-2 py-1"><i class="fas fa-times-circle me-1"></i>Thất bại</span>',
            'Cancelled': '<span class="badge bg-secondary px-2 py-1"><i class="fas fa-ban me-1"></i>Đã hủy</span>',
        };
        return map[status] || `<span class="badge bg-light text-dark border px-2 py-1">${Utils.escapeHtml(status || 'N/A')}</span>`;
    }

    function getOrderStatusBadge(status) {
        const map = {
            'Pending': '<span class="badge bg-warning text-dark px-2 py-1">Pending</span>',
            'Confirmed': '<span class="badge bg-info px-2 py-1">Confirmed</span>',
            'Processing': '<span class="badge bg-primary px-2 py-1">Processing</span>',
            'Shipped': '<span class="badge bg-secondary px-2 py-1">Shipped</span>',
            'Delivered': '<span class="badge bg-success px-2 py-1">Delivered</span>',
            'Cancelled': '<span class="badge bg-danger px-2 py-1">Cancelled</span>',
        };
        return map[status] || `<span class="badge bg-light text-dark border px-2 py-1">${Utils.escapeHtml(status || 'N/A')}</span>`;
    }

    async function loadPayments(page = 1) {
        currentPage = page;
        if (tableBody) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <span class="ml-2">Đang tải danh sách thanh toán...</span>
                    </td>
                </tr>`;
        }

        const params = new URLSearchParams({
            page: currentPage,
            page_size: 15
        });

        const q = (searchInput?.value || '').trim();
        if (q) params.append('q', q);

        const method = methodFilter?.value;
        if (method) params.append('method', method);

        const status = statusFilter?.value;
        if (status) params.append('status', status);

        try {
            const res = await ApiClient.get('/api/admin/payments?' + params.toString());
            if (res && res.success) {
                renderPaymentsTable(res.data || []);
                renderPagination(res.meta || {});
            } else {
                tableBody.innerHTML = `
                    <tr><td colspan="8" class="text-center py-4 text-danger">Không thể tải dữ liệu thanh toán.</td></tr>`;
            }
        } catch (error) {
            console.error('Lỗi khi tải payments:', error);
            tableBody.innerHTML = `
                <tr><td colspan="8" class="text-center py-4 text-danger">Đã có lỗi xảy ra khi kết nối máy chủ.</td></tr>`;
        }
    }

    function renderPaymentsTable(payments) {
        if (!tableBody) return;

        if (payments.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        Không tìm thấy giao dịch thanh toán nào phù hợp.
                    </td>
                </tr>`;
            return;
        }

        tableBody.innerHTML = payments.map(p => {
            const isShippedOrDelivered = ['Shipped', 'Delivered'].includes(p.order_status);
            const canConfirmCod = p.method === 'COD' && p.status === 'Pending' && isShippedOrDelivered;
            const isWaitingDelivery = p.method === 'COD' && p.status === 'Pending' && !['Shipped', 'Delivered', 'Cancelled'].includes(p.order_status);

            let actionHtml = '';
            if (canConfirmCod) {
                actionHtml = `
                    <button class="btn btn-sm btn-success btn-confirm-cod" data-number="${p.order_number}">
                        <i class="fas fa-check-circle mr-1"></i> Thu tiền COD
                    </button>
                `;
            } else if (isWaitingDelivery) {
                actionHtml = `
                    <span class="text-muted small" title="Cần chuyển đơn sang Shipped hoặc Delivered trước">
                        <i class="fas fa-truck mr-1"></i> Chờ giao hàng
                    </span>
                `;
            } else {
                actionHtml = `<span class="text-muted small"><i class="fas fa-lock me-1"></i>Không có thao tác</span>`;
            }

            return `
                <tr>
                    <td>
                        <strong class="text-primary fs-6">${Utils.escapeHtml(p.order_number)}</strong>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark">${Utils.escapeHtml(p.user_fullname || 'Khách vãng lai')}</div>
                        <small class="text-muted">${Utils.escapeHtml(p.user_email || '')}</small>
                    </td>
                    <td>
                        <span class="badge ${p.method === 'VNPay' ? 'bg-primary' : 'bg-light text-dark border'} fw-bold">
                            ${Utils.escapeHtml(p.method)}
                        </span>
                    </td>
                    <td class="text-end fw-bold text-dark fs-6">
                        ${Utils.formatPrice(p.amount)}
                    </td>
                    <td class="text-center">
                        ${getPaymentStatusBadge(p.status)}
                    </td>
                    <td class="text-center">
                        ${getOrderStatusBadge(p.order_status)}
                    </td>
                    <td class="small">
                        ${p.paid_at ? `<span class="text-success fw-bold">${Utils.formatDate(p.paid_at)}</span>` : '<span class="text-muted">Chưa thanh toán</span>'}
                    </td>
                    <td class="text-end">
                        ${actionHtml}
                    </td>
                </tr>
            `;
        }).join('');

        tableBody.querySelectorAll('.btn-confirm-cod').forEach(btn => {
            btn.addEventListener('click', function () {
                const orderNum = this.getAttribute('data-number');
                confirmCodPayment(orderNum, this);
            });
        });
    }

    async function confirmCodPayment(orderNumber, btnEl) {
        if (!confirm(`Xác nhận đã nhận đủ tiền mặt COD cho đơn hàng "${orderNumber}"?`)) {
            return;
        }

        btnEl.disabled = true;
        const oldHtml = btnEl.innerHTML;
        btnEl.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span> Đang xử lý...';

        try {
            const res = await ApiClient.post(`/api/admin/payments/${orderNumber}/confirm-cod`);
            if (res && res.success) {
                Utils.showToast(`Xác nhận thanh toán COD đơn ${orderNumber} thành công!`, 'success');
                loadPayments(currentPage);
            } else {
                Utils.showToast(res?.error?.message || 'Không thể xác nhận thanh toán COD', 'error');
                btnEl.disabled = false;
                btnEl.innerHTML = oldHtml;
            }
        } catch (error) {
            Utils.showToast(error.message || 'Lỗi khi xác nhận thanh toán', 'error');
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
            paginationContainer.innerHTML = `<span class="text-muted small">Hiển thị tổng số <strong>${total}</strong> giao dịch thanh toán</span>`;
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
                Hiển thị trang <strong>${current}</strong> / <strong>${totalPages}</strong> (Tổng <strong>${total}</strong> giao dịch)
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
                    loadPayments(p);
                }
            });
        });
    }

    btnFilter?.addEventListener('click', () => loadPayments(1));
    btnReload?.addEventListener('click', () => loadPayments(currentPage));
    searchInput?.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') loadPayments(1);
    });
    methodFilter?.addEventListener('change', () => loadPayments(1));
    statusFilter?.addEventListener('change', () => loadPayments(1));

    loadPayments(1);
});
