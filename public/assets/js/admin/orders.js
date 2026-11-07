/**
 * Admin Orders Management JavaScript
 */
document.addEventListener('DOMContentLoaded', function () {
    let currentPage = 1;
    let currentDetailOrder = null;

    const searchInput = document.getElementById('orderSearchInput');
    const statusFilter = document.getElementById('orderStatusFilter');
    const paymentMethodFilter = document.getElementById('orderPaymentMethodFilter');
    const btnFilter = document.getElementById('btnFilterOrders');
    const btnReload = document.getElementById('btnReloadOrders');
    const tableBody = document.getElementById('ordersTableBody');
    const paginationContainer = document.getElementById('paginationOrders');

    const modalOrderDetail = document.getElementById('modalOrderDetail');
    const orderModalTitle = document.getElementById('orderModalTitle');
    const orderModalBody = document.getElementById('orderModalBody');
    const orderStatusActionButtons = document.getElementById('orderStatusActionButtons');

    function showModal(modalEl) {
        if (!modalEl) return;
        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else {
            modalEl.classList.add('show');
            modalEl.style.display = 'block';
            document.body.classList.add('modal-open');
        }
    }

    function hideModal(modalEl) {
        if (!modalEl) return;
        if (window.bootstrap && window.bootstrap.Modal) {
            const instance = window.bootstrap.Modal.getInstance(modalEl);
            if (instance) {
                instance.hide();
                return;
            }
        }
        modalEl.classList.remove('show');
        modalEl.style.display = 'none';
        document.body.classList.remove('modal-open');
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    }

    document.getElementById('btnCloseOrderModal')?.addEventListener('click', () => hideModal(modalOrderDetail));
    document.getElementById('btnBottomCloseOrder')?.addEventListener('click', () => hideModal(modalOrderDetail));

    function getOrderStatusBadge(status) {
        const map = {
            'Pending': '<span class="badge bg-warning text-dark px-2 py-1"><i class="fas fa-clock me-1"></i>Chờ xác nhận</span>',
            'Confirmed': '<span class="badge bg-info px-2 py-1"><i class="fas fa-check me-1"></i>Đã xác nhận</span>',
            'Processing': '<span class="badge bg-primary px-2 py-1"><i class="fas fa-box me-1"></i>Đang xử lý</span>',
            'Shipped': '<span class="badge bg-secondary px-2 py-1"><i class="fas fa-truck me-1"></i>Đang giao hàng</span>',
            'Delivered': '<span class="badge bg-success px-2 py-1"><i class="fas fa-check-circle me-1"></i>Đã giao hàng</span>',
            'Cancelled': '<span class="badge bg-danger px-2 py-1"><i class="fas fa-times-circle me-1"></i>Đã hủy</span>',
        };
        return map[status] || `<span class="badge bg-light text-dark border px-2 py-1">${Utils.escapeHtml(status || 'N/A')}</span>`;
    }

    function getPaymentStatusBadge(status) {
        const map = {
            'Paid': '<span class="badge bg-success px-2 py-1">Đã thanh toán</span>',
            'Pending': '<span class="badge bg-warning text-dark px-2 py-1">Chờ thanh toán</span>',
            'Processing': '<span class="badge bg-info px-2 py-1">Đang xử lý</span>',
            'Failed': '<span class="badge bg-danger px-2 py-1">Thanh toán lỗi</span>',
            'Cancelled': '<span class="badge bg-secondary px-2 py-1">Đã hủy TT</span>',
        };
        return map[status] || `<span class="badge bg-light text-dark border">${Utils.escapeHtml(status || 'N/A')}</span>`;
    }

    async function loadOrders(page = 1) {
        currentPage = page;
        if (tableBody) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <span class="ml-2">Đang tải danh sách đơn hàng...</span>
                    </td>
                </tr>`;
        }

        const params = new URLSearchParams({
            page: currentPage,
            page_size: 15
        });

        const q = (searchInput?.value || '').trim();
        if (q) params.append('q', q);

        const status = statusFilter?.value;
        if (status) params.append('status', status);

        const method = paymentMethodFilter?.value;
        if (method) params.append('payment_method', method);

        try {
            const res = await ApiClient.get('/api/admin/orders?' + params.toString());
            if (res && res.success) {
                renderOrdersTable(res.data || []);
                renderPagination(res.meta || {});
            } else {
                tableBody.innerHTML = `
                    <tr><td colspan="8" class="text-center py-4 text-danger">Không thể tải dữ liệu đơn hàng.</td></tr>`;
            }
        } catch (error) {
            console.error('Lỗi khi tải orders:', error);
            tableBody.innerHTML = `
                <tr><td colspan="8" class="text-center py-4 text-danger">Đã có lỗi xảy ra khi kết nối máy chủ.</td></tr>`;
        }
    }

    function renderOrdersTable(orders) {
        if (!tableBody) return;

        if (orders.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        Không tìm thấy đơn hàng nào phù hợp với bộ lọc.
                    </td>
                </tr>`;
            return;
        }

        tableBody.innerHTML = orders.map(o => `
            <tr>
                <td>
                    <strong class="text-primary fs-6">${Utils.escapeHtml(o.order_number)}</strong>
                </td>
                <td>
                    <div class="fw-semibold text-dark">${Utils.escapeHtml(o.user_fullname || 'Khách vãng lai')}</div>
                    <small class="text-muted">${Utils.escapeHtml(o.user_email || '')}</small>
                </td>
                <td class="fw-bold text-dark fs-6">
                    ${Utils.formatPrice(o.total_amount)}
                </td>
                <td>
                    <span class="badge bg-light text-dark border fw-bold">${Utils.escapeHtml(o.payment_method)}</span>
                </td>
                <td class="text-center">
                    ${getPaymentStatusBadge(o.payment_status)}
                </td>
                <td class="text-center">
                    ${getOrderStatusBadge(o.order_status || o.status)}
                </td>
                <td class="text-muted small">
                    ${Utils.formatDate(o.created_at)}
                </td>
                <td class="text-end">
                    <button class="btn btn-sm btn-primary btn-view-order" data-number="${o.order_number}">
                        <i class="fas fa-eye me-1"></i> Xử lý
                    </button>
                </td>
            </tr>
        `).join('');

        tableBody.querySelectorAll('.btn-view-order').forEach(btn => {
            btn.addEventListener('click', function () {
                const orderNum = this.getAttribute('data-number');
                openOrderDetailModal(orderNum);
            });
        });
    }

    function renderPagination(meta) {
        if (!paginationContainer) return;
        const total = meta.total || 0;
        const totalPages = meta.total_pages || Math.ceil(total / (meta.page_size || 15)) || 1;
        const current = meta.page || 1;

        if (totalPages <= 1) {
            paginationContainer.innerHTML = `<span class="text-muted small">Hiển thị tổng số <strong>${total}</strong> đơn hàng</span>`;
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
                Hiển thị trang <strong>${current}</strong> / <strong>${totalPages}</strong> (Tổng <strong>${total}</strong> đơn)
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
                    loadOrders(p);
                }
            });
        });
    }

    // ==========================================
    // XỬ LÝ MODAL CHI TIẾT ĐƠN HÀNG
    // ==========================================
    async function openOrderDetailModal(orderNumber) {
        if (orderModalTitle) {
            orderModalTitle.innerHTML = `<i class="fas fa-receipt mr-1"></i> Chi tiết Đơn hàng: <span class="text-warning">${Utils.escapeHtml(orderNumber)}</span>`;
        }
        if (orderModalBody) {
            orderModalBody.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 text-muted">Đang tải chi tiết đơn hàng...</div>
                </div>`;
        }
        if (orderStatusActionButtons) {
            orderStatusActionButtons.innerHTML = '';
        }

        showModal(modalOrderDetail);

        try {
            const res = await ApiClient.get(`/api/admin/orders/${orderNumber}`);
            if (!res || !res.success) {
                orderModalBody.innerHTML = `<div class="alert alert-danger">${Utils.escapeHtml(res?.error?.message || 'Không thể lấy thông tin đơn hàng')}</div>`;
                return;
            }

            currentDetailOrder = res.data;
            renderOrderDetail(currentDetailOrder);
            renderStateMachineButtons(currentDetailOrder);
        } catch (error) {
            console.error(error);
            orderModalBody.innerHTML = `<div class="alert alert-danger">Đã có lỗi xảy ra khi tải chi tiết đơn hàng.</div>`;
        }
    }

    function renderOrderDetail(order) {
        const addr = order.shipping_address || {};
        const payment = order.payment || {};
        const items = order.items || [];
        const user = order.user || {};

        let attemptsHtml = '';
        if (payment.attempts && payment.attempts.length > 0) {
            attemptsHtml = `
                <div class="mt-2">
                    <span class="small font-weight-bold text-muted">Lịch sử giao dịch cổng (${payment.attempts.length} lần):</span>
                    <ul class="list-unstyled small mb-0 mt-1 pl-2 border-left">
                        ${payment.attempts.map(a => `
                            <li>
                                <code>${Utils.escapeHtml(a.transaction_reference)}</code>: 
                                <span class="font-weight-bold">${a.status}</span> 
                                (${Utils.formatPrice(a.amount)}) 
                                <span class="text-muted">${Utils.formatDate(a.created_at)}</span>
                            </li>
                        `).join('')}
                    </ul>
                </div>
            `;
        }

        orderModalBody.innerHTML = `
            <div class="row mb-4">
                <!-- Thông tin khách hàng & Giao hàng -->
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="card h-100 border">
                        <div class="card-header bg-light py-2 font-weight-bold text-dark">
                            <i class="fas fa-map-marker-alt text-danger mr-1"></i> Người nhận & Địa chỉ giao hàng
                        </div>
                        <div class="card-body py-3">
                            <div class="mb-1"><strong>Họ tên nhận:</strong> ${Utils.escapeHtml(addr.fullname || user.fullname || 'N/A')}</div>
                            <div class="mb-1"><strong>Số điện thoại:</strong> ${Utils.escapeHtml(addr.phone || user.phone || 'N/A')}</div>
                            <div class="mb-2"><strong>Địa chỉ giao:</strong> ${Utils.escapeHtml(addr.address || 'N/A')}</div>
                            <div class="border-top pt-2 mt-2 small text-muted">
                                <div><strong>Tài khoản đặt:</strong> ${Utils.escapeHtml(user.fullname || 'Khách vãng lai')} (${Utils.escapeHtml(user.email || 'N/A')})</div>
                                <div><strong>Ngày đặt:</strong> ${Utils.formatDate(order.created_at)}</div>
                                <div><strong>Ghi chú đơn:</strong> <span class="text-dark font-italic">${Utils.escapeHtml(order.note || 'Không có')}</span></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Thông tin Thanh toán -->
                <div class="col-md-6">
                    <div class="card h-100 border">
                        <div class="card-header bg-light py-2 font-weight-bold text-dark">
                            <i class="fas fa-credit-card text-primary mr-1"></i> Thông tin Thanh toán
                        </div>
                        <div class="card-body py-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Phương thức:</span>
                                <strong class="badge badge-light border font-size-14">${Utils.escapeHtml(order.payment_method)}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Trạng thái thanh toán:</span>
                                <div>${getPaymentStatusBadge(payment.status)}</div>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Trạng thái đơn hàng:</span>
                                <div>${getOrderStatusBadge(order.status)}</div>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Thời gian thanh toán:</span>
                                <span class="text-dark">${payment.paid_at ? Utils.formatDate(payment.paid_at) : '<em class="text-muted">Chưa thanh toán</em>'}</span>
                            </div>
                            ${order.payment_expires_at ? `
                                <div class="d-flex justify-content-between mb-2 small text-muted">
                                    <span>Hết hạn thanh toán VNPay:</span>
                                    <span>${Utils.formatDate(order.payment_expires_at)}</span>
                                </div>
                            ` : ''}
                            ${attemptsHtml}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Danh sách mặt hàng -->
            <h6 class="font-weight-bold text-dark mb-2">
                <i class="fas fa-shopping-basket mr-1"></i> Mặt hàng trong đơn (${items.length} món)
            </h6>
            <div class="table-responsive border rounded mb-3">
                <table class="table table-sm table-hover align-middle mb-0 bg-white">
                    <thead class="thead-light">
                        <tr>
                            <th>Mã SKU</th>
                            <th>Tên sản phẩm</th>
                            <th>Biến thể</th>
                            <th class="text-right">Đơn giá</th>
                            <th class="text-center" style="width: 80px;">SL</th>
                            <th class="text-right">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${items.map(item => `
                            <tr>
                                <td><code>${Utils.escapeHtml(item.sku)}</code></td>
                                <td><strong>${Utils.escapeHtml(item.product_name)}</strong></td>
                                <td><span class="text-muted">${Utils.escapeHtml(item.variant_name || '')}</span></td>
                                <td class="text-right">${Utils.formatPrice(item.unit_price)}</td>
                                <td class="text-center font-weight-bold">${item.quantity}</td>
                                <td class="text-right font-weight-bold text-dark">${Utils.formatPrice(item.subtotal)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                    <tfoot class="bg-light font-weight-bold">
                        <tr>
                            <td colspan="5" class="text-right">Tạm tính tiền hàng:</td>
                            <td class="text-right">${Utils.formatPrice(order.subtotal)}</td>
                        </tr>
                        <tr>
                            <td colspan="5" class="text-right">Phí vận chuyển:</td>
                            <td class="text-right">${Utils.formatPrice(order.shipping_fee)}</td>
                        </tr>
                        <tr class="h5 text-primary">
                            <td colspan="5" class="text-right font-weight-bold">TỔNG CỘNG THANH TOÁN:</td>
                            <td class="text-right font-weight-bold text-danger">${Utils.formatPrice(order.total_amount)}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        `;
    }

    // State machine buttons
    function renderStateMachineButtons(order) {
        if (!orderStatusActionButtons) return;
        const status = order.status;
        let buttonsHtml = '';

        if (status === 'Pending') {
            buttonsHtml = `
                <button type="button" class="btn btn-success me-2 btn-change-status" data-status="Confirmed">
                    <i class="fas fa-check me-1"></i> Xác nhận đơn hàng (Confirmed)
                </button>
                <button type="button" class="btn btn-danger btn-change-status" data-status="Cancelled">
                    <i class="fas fa-times me-1"></i> Hủy đơn (Cancelled)
                </button>
            `;
        } else if (status === 'Confirmed') {
            buttonsHtml = `
                <button type="button" class="btn btn-primary me-2 btn-change-status" data-status="Processing">
                    <i class="fas fa-box-open me-1"></i> Đưa vào đóng gói & xử lý (Processing)
                </button>
                <button type="button" class="btn btn-danger btn-change-status" data-status="Cancelled">
                    <i class="fas fa-times me-1"></i> Hủy đơn (Cancelled)
                </button>
            `;
        } else if (status === 'Processing') {
            buttonsHtml = `
                <button type="button" class="btn btn-info me-2 btn-change-status" data-status="Shipped">
                    <i class="fas fa-truck me-1"></i> Giao cho đơn vị vận chuyển (Shipped)
                </button>
                <button type="button" class="btn btn-danger btn-change-status" data-status="Cancelled">
                    <i class="fas fa-times me-1"></i> Hủy đơn (Cancelled)
                </button>
            `;
        } else if (status === 'Shipped') {
            buttonsHtml = `
                <button type="button" class="btn btn-success btn-change-status" data-status="Delivered">
                    <i class="fas fa-check-double me-1"></i> Đánh dấu Đã giao thành công (Delivered)
                </button>
            `;
        } else if (status === 'Delivered') {
            buttonsHtml = `
                <div class="alert alert-success py-1 px-3 mb-0 d-inline-block small">
                    <i class="fas fa-check-circle me-1"></i> Đơn hàng đã giao thành công và kết thúc luồng xử lý.
                </div>
            `;
        } else if (status === 'Cancelled') {
            buttonsHtml = `
                <div class="alert alert-danger py-1 px-3 mb-0 d-inline-block small">
                    <i class="fas fa-ban me-1"></i> Đơn hàng đã bị hủy, không thể thay đổi trạng thái.
                </div>
            `;
        }

        orderStatusActionButtons.innerHTML = buttonsHtml;

        orderStatusActionButtons.querySelectorAll('.btn-change-status').forEach(btn => {
            btn.addEventListener('click', async function () {
                const newStatus = this.getAttribute('data-status');
                const actionName = this.textContent.trim();

                if (!confirm(`Bạn có chắc chắn muốn chuyển trạng thái đơn hàng sang "${newStatus}"?`)) {
                    return;
                }

                this.disabled = true;
                const oldText = this.innerHTML;
                this.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span> Đang cập nhật...';

                try {
                    const res = await ApiClient.patch(`/api/admin/orders/${order.order_number}/status`, {
                        status: newStatus
                    });

                    if (res && res.success) {
                        Utils.showToast('Cập nhật trạng thái đơn hàng thành công!', 'success');
                        // Reload chi tiết đơn và danh sách
                        openOrderDetailModal(order.order_number);
                        loadOrders(currentPage);
                    } else {
                        Utils.showToast(res?.error?.message || 'Không thể chuyển trạng thái đơn', 'error');
                        this.disabled = false;
                        this.innerHTML = oldText;
                    }
                } catch (error) {
                    Utils.showToast(error.message || 'Lỗi khi cập nhật trạng thái', 'error');
                    this.disabled = false;
                    this.innerHTML = oldText;
                }
            });
        });
    }

    btnFilter?.addEventListener('click', () => loadOrders(1));
    btnReload?.addEventListener('click', () => loadOrders(currentPage));
    searchInput?.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') loadOrders(1);
    });
    statusFilter?.addEventListener('change', () => loadOrders(1));
    paymentMethodFilter?.addEventListener('change', () => loadOrders(1));

    loadOrders(1);
});
