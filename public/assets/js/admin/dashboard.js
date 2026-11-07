/**
 * Admin Dashboard JavaScript
 * Gọi song song các API thống kê và render KPI, Đơn hàng gần đây, Cảnh báo tồn kho.
 */
document.addEventListener('DOMContentLoaded', function () {
    const kpiRevenue = document.getElementById('kpiRevenue');
    const kpiOrders = document.getElementById('kpiOrders');
    const kpiProducts = document.getElementById('kpiProducts');
    const kpiUsers = document.getElementById('kpiUsers');
    const tableRecentOrders = document.getElementById('tableRecentOrders')?.querySelector('tbody');
    const tableLowStock = document.getElementById('tableLowStock')?.querySelector('tbody');
    const btnRefresh = document.getElementById('btnRefreshDashboard');

    if (btnRefresh) {
        btnRefresh.addEventListener('click', function () {
            loadDashboardData();
        });
    }

    function getOrderStatusBadge(status) {
        const map = {
            'Pending': '<span class="badge bg-warning text-dark px-2 py-1">Chờ xác nhận</span>',
            'Confirmed': '<span class="badge bg-info px-2 py-1">Đã xác nhận</span>',
            'Processing': '<span class="badge bg-primary px-2 py-1">Đang xử lý</span>',
            'Shipped': '<span class="badge bg-secondary px-2 py-1">Đang giao</span>',
            'Delivered': '<span class="badge bg-success px-2 py-1">Đã giao</span>',
            'Cancelled': '<span class="badge bg-danger px-2 py-1">Đã hủy</span>',
        };
        return map[status] || `<span class="badge bg-light text-dark border px-2 py-1">${Utils.escapeHtml(status || 'N/A')}</span>`;
    }

    function getPaymentStatusBadge(status) {
        const map = {
            'Paid': '<span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i>Đã TT</span>',
            'Pending': '<span class="text-warning fw-bold"><i class="fas fa-clock me-1"></i>Chưa TT</span>',
            'Processing': '<span class="text-info fw-bold"><i class="fas fa-spinner fa-spin me-1"></i>Đang xử lý</span>',
            'Failed': '<span class="text-danger fw-bold"><i class="fas fa-times-circle me-1"></i>Thất bại</span>',
            'Cancelled': '<span class="text-muted fw-bold"><i class="fas fa-ban me-1"></i>Đã hủy</span>',
        };
        return map[status] || `<span class="text-muted">${Utils.escapeHtml(status || 'N/A')}</span>`;
    }

    async function loadDashboardData() {
        // Reset spinners
        if (kpiRevenue) kpiRevenue.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
        if (kpiOrders) kpiOrders.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
        if (kpiProducts) kpiProducts.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
        if (kpiUsers) kpiUsers.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';

        if (tableRecentOrders) {
            tableRecentOrders.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <span class="ms-2">Đang tải dữ liệu đơn hàng...</span>
                    </td>
                </tr>`;
        }

        if (tableLowStock) {
            tableLowStock.innerHTML = `
                <tr>
                    <td colspan="3" class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm text-danger" role="status"></div>
                        <span class="ms-2">Đang kiểm tra kho hàng...</span>
                    </td>
                </tr>`;
        }

        try {
            // Gọi song song 5 APIs theo đúng hợp đồng thiết kế
            const [ordersRes, paymentsRes, productsRes, inventoryRes, usersRes] = await Promise.all([
                ApiClient.get('/api/admin/orders?page_size=10').catch(() => null),
                ApiClient.get('/api/admin/payments?status=Paid&page_size=100').catch(() => null),
                ApiClient.get('/api/admin/products?page_size=1').catch(() => null),
                ApiClient.get('/api/admin/inventory?low_stock=1&page_size=10').catch(() => null),
                ApiClient.get('/api/admin/users?page_size=1').catch(() => null),
            ]);

            // 1. Doanh thu thực thu từ các payment "Paid"
            let totalRevenue = 0;
            if (paymentsRes && paymentsRes.success && Array.isArray(paymentsRes.data)) {
                totalRevenue = paymentsRes.data.reduce((sum, p) => sum + (parseFloat(p.amount) || 0), 0);
            }
            if (kpiRevenue) kpiRevenue.textContent = Utils.formatPrice(totalRevenue);

            // 2. Tổng đơn hàng
            const totalOrders = ordersRes?.meta?.total ?? ordersRes?.data?.length ?? 0;
            if (kpiOrders) kpiOrders.textContent = Number(totalOrders).toLocaleString('vi-VN');

            // 3. Tổng sản phẩm
            const totalProducts = productsRes?.meta?.total ?? productsRes?.data?.length ?? 0;
            if (kpiProducts) kpiProducts.textContent = Number(totalProducts).toLocaleString('vi-VN');

            // 4. Tổng người dùng
            const totalUsers = usersRes?.meta?.total ?? usersRes?.data?.length ?? 0;
            if (kpiUsers) kpiUsers.textContent = Number(totalUsers).toLocaleString('vi-VN');

            // 5. Render Bảng Đơn hàng gần đây
            if (tableRecentOrders) {
                const recentOrders = ordersRes?.data || [];
                if (recentOrders.length === 0) {
                    tableRecentOrders.innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                Chưa có đơn hàng nào trong hệ thống.
                            </td>
                        </tr>`;
                } else {
                    tableRecentOrders.innerHTML = recentOrders.map(order => `
                        <tr>
                            <td>
                                <a href="/admin/orders" class="fw-bold text-primary text-decoration-none">
                                    ${Utils.escapeHtml(order.order_number)}
                                </a>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">${Utils.escapeHtml(order.user_fullname || 'Khách vãng lai')}</div>
                                <small class="text-muted">${Utils.escapeHtml(order.user_email || '')}</small>
                            </td>
                            <td class="fw-bold text-dark">
                                ${Utils.formatPrice(order.total_amount)}
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">${Utils.escapeHtml(order.payment_method)}</span>
                            </td>
                            <td>
                                ${getOrderStatusBadge(order.order_status || order.status)}
                            </td>
                            <td class="text-muted small">
                                ${Utils.formatDate(order.created_at)}
                            </td>
                        </tr>
                    `).join('');
                }
            }

            // 6. Render Bảng Cảnh báo tồn kho thấp
            if (tableLowStock) {
                const lowStockItems = inventoryRes?.data || [];
                if (lowStockItems.length === 0) {
                    tableLowStock.innerHTML = `
                        <tr>
                            <td colspan="3" class="text-center py-4 text-success fw-bold">
                                <i class="fas fa-check-circle me-1"></i> Kho hàng an toàn, không có biến thể sắp hết.
                            </td>
                        </tr>`;
                } else {
                    tableLowStock.innerHTML = lowStockItems.map(item => `
                        <tr>
                            <td>
                                <code class="text-dark fw-bold">${Utils.escapeHtml(item.sku)}</code>
                            </td>
                            <td>
                                <div class="fw-bold text-truncate" style="max-width: 220px;" title="${Utils.escapeHtml(item.product_name)}">
                                    ${Utils.escapeHtml(item.product_name)}
                                </div>
                                <small class="text-muted">${Utils.escapeHtml(item.variant_name || '')}</small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-danger px-2 py-1 fw-bold fs-6">
                                    ${item.quantity}
                                </span>
                            </td>
                        </tr>
                    `).join('');
                }
            }
        } catch (error) {
            console.error('Lỗi khi tải dữ liệu dashboard:', error);
            if (typeof Utils !== 'undefined' && Utils.showToast) {
                Utils.showToast('Không thể tải dữ liệu dashboard, vui lòng thử lại', 'error');
            }
        }
    }

    loadDashboardData();
});
