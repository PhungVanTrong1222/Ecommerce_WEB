/**
 * TechShop - Order Detail Page JavaScript
 * Hiển thị chi tiết đơn hàng, timeline trạng thái, thanh toán lại VNPay, hủy đơn
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
        }
    };

    const pageContainer = document.getElementById('order-detail-page');
    const orderNumber = pageContainer?.getAttribute('data-order-number');

    const elements = {
        placedBanner: document.getElementById('order-placed-banner'),
        loading: document.getElementById('order-loading'),
        error: document.getElementById('order-error'),
        errorTitle: document.getElementById('error-title'),
        errorMessage: document.getElementById('error-message'),
        content: document.getElementById('order-content'),
        txtOrderNumber: document.getElementById('txt-order-number'),
        badgeOrderStatus: document.getElementById('badge-order-status'),
        txtCreatedAt: document.getElementById('txt-created-at'),
        txtPaymentMethod: document.getElementById('txt-payment-method'),
        badgePaymentStatus: document.getElementById('badge-payment-status'),
        btnPayNow: document.getElementById('btn-pay-now'),
        btnCancelOrder: document.getElementById('btn-cancel-order'),
        paymentExpiredAlert: document.getElementById('payment-expired-alert'),
        txtPaymentExpiresAt: document.getElementById('txt-payment-expires-at'),
        timelineProgressBar: document.getElementById('timeline-progress-bar'),
        timelineCard: document.getElementById('order-timeline-card'),
        tbody: document.getElementById('order-items-tbody'),
        subtotal: document.getElementById('txt-subtotal'),
        totalAmount: document.getElementById('txt-total-amount'),
        receiverName: document.getElementById('txt-receiver-name'),
        receiverPhone: document.getElementById('txt-receiver-phone'),
        receiverAddress: document.getElementById('txt-receiver-address'),
        blockOrderNote: document.getElementById('block-order-note'),
        receiverNote: document.getElementById('txt-receiver-note'),
        btnConfirmCancel: document.getElementById('btn-confirm-cancel'),
        cancelReason: document.getElementById('cancel-reason')
    };

    let orderData = null;

    // Kiểm tra ?placed=1
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('placed') === '1') {
        elements.placedBanner.classList.remove('d-none');
    }

    /**
     * Tải thông tin đơn hàng
     */
    async function loadOrderDetail() {
        if (!orderNumber) {
            showError('Mã đơn hàng không hợp lệ.');
            return;
        }

        elements.loading.classList.remove('d-none');
        elements.content.classList.add('d-none');
        elements.error.classList.add('d-none');

        try {
            const res = await api.get(`/api/orders/${encodeURIComponent(orderNumber)}`);
            const data = res?.data || res;

            if (data && data.order_number) {
                orderData = data;
                renderOrderDetail(data);
                elements.content.classList.remove('d-none');
            } else {
                throw new Error('Không tìm thấy thông tin đơn hàng.');
            }
        } catch (error) {
            console.error('Lỗi tải chi tiết đơn hàng:', error);
            if (error?.status === 401) {
                showToast('Vui lòng đăng nhập để xem thông tin đơn hàng.', 'warning');
                setTimeout(() => {
                    const returnUrl = window.location.pathname + window.location.search;
                    window.location.href = '/login?redirect=' + encodeURIComponent(returnUrl);
                }, 800);
                return;
            }

            const errMsg = error?.message || error?.error?.message || 'Đơn hàng không tồn tại hoặc bạn không có quyền xem đơn hàng này.';
            showError(errMsg);
        } finally {
            elements.loading.classList.add('d-none');
        }
    }

    function showError(msg) {
        elements.error.classList.remove('d-none');
        elements.errorMessage.textContent = msg;
    }

    /**
     * Render chi tiết đơn hàng ra giao diện
     */
    function renderOrderDetail(order) {
        elements.txtOrderNumber.textContent = order.order_number;
        elements.txtCreatedAt.textContent = formatDate(order.created_at);
        elements.txtPaymentMethod.textContent = order.payment_method;

        // Trạng thái đơn hàng
        renderOrderStatusBadge(order.status);

        // Trạng thái thanh toán
        const pStatus = order.payment ? order.payment.status : 'Unpaid';
        renderPaymentStatusBadge(pStatus);

        // Thanh toán VNPay lại nếu còn chờ
        const isVNPay = order.payment_method === 'VNPay';
        const isPending = order.status === 'Pending';
        const isUnpaid = pStatus !== 'Paid';

        if (isPending && isVNPay && isUnpaid) {
            elements.btnPayNow.classList.remove('d-none');
            if (order.payment_expires_at) {
                elements.paymentExpiredAlert.classList.remove('d-none');
                elements.txtPaymentExpiresAt.textContent = formatDate(order.payment_expires_at);
            }
        } else {
            elements.btnPayNow.classList.add('d-none');
            elements.paymentExpiredAlert.classList.add('d-none');
        }

        // Nút hủy đơn hàng
        if (isPending && isUnpaid) {
            elements.btnCancelOrder.classList.remove('d-none');
        } else {
            elements.btnCancelOrder.classList.add('d-none');
        }

        // Timeline trạng thái
        updateTimeline(order.status);

        // Địa chỉ giao hàng
        const addr = order.shipping_address || {};
        elements.receiverName.textContent = addr.fullname || '--';
        elements.receiverPhone.textContent = addr.phone || '--';
        elements.receiverAddress.textContent = addr.address || '--';

        if (order.note) {
            elements.receiverNote.textContent = order.note;
            elements.blockOrderNote.classList.remove('d-none');
        } else {
            elements.blockOrderNote.classList.add('d-none');
        }

        // Bảng sản phẩm
        elements.tbody.innerHTML = '';
        const items = order.items || [];
        items.forEach(item => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <div class="fw-semibold text-dark">${escapeHtml(item.product_name)}</div>
                    <div class="small text-muted">
                        <span>Phân loại: ${escapeHtml(item.variant_name)}</span>
                        <span class="mx-1">•</span>
                        <span>SKU: ${escapeHtml(item.sku)}</span>
                    </div>
                </td>
                <td class="text-center">${formatPrice(item.unit_price)}</td>
                <td class="text-center fw-medium">${item.quantity}</td>
                <td class="text-end fw-bold text-danger">${formatPrice(item.subtotal)}</td>
            `;
            elements.tbody.appendChild(tr);
        });

        elements.subtotal.textContent = formatPrice(order.subtotal);
        elements.totalAmount.textContent = formatPrice(order.total_amount);
    }

    /**
     * Hiển thị badge trạng thái đơn hàng
     */
    function renderOrderStatusBadge(status) {
        const badgeMap = {
            'Pending': { label: 'Chờ xử lý', class: 'status-badge pending' },
            'Confirmed': { label: 'Đã xác nhận', class: 'status-badge confirmed' },
            'Processing': { label: 'Đang đóng gói', class: 'status-badge processing' },
            'Shipped': { label: 'Đang giao hàng', class: 'status-badge shipped' },
            'Delivered': { label: 'Đã giao hàng', class: 'status-badge delivered' },
            'Cancelled': { label: 'Đã hủy', class: 'status-badge cancelled' }
        };

        const config = badgeMap[status] || { label: status, class: 'status-badge' };
        elements.badgeOrderStatus.className = config.class;
        elements.badgeOrderStatus.textContent = config.label;
    }

    /**
     * Hiển thị badge trạng thái thanh toán
     */
    function renderPaymentStatusBadge(status) {
        const badgeMap = {
            'Unpaid': { label: 'Chưa thanh toán', class: 'badge bg-danger' },
            'Paid': { label: 'Đã thanh toán', class: 'badge bg-success' },
            'Refunded': { label: 'Đã hoàn tiền', class: 'badge bg-secondary' }
        };
        const config = badgeMap[status] || { label: status, class: 'badge bg-secondary' };
        elements.badgePaymentStatus.innerHTML = `<span class="${config.class}">${config.label}</span>`;
    }

    /**
     * Cập nhật thanh tiến trình Timeline (Stepper 5 bước)
     */
    function updateTimeline(status) {
        if (status === 'Cancelled') {
            elements.timelineCard.innerHTML = `
                <div class="ts-card-body p-4 text-center">
                    <span class="status-badge cancelled fs-6 px-4 py-2 mb-2"><i class="fa-solid fa-ban me-1"></i> Đơn hàng đã bị hủy</span>
                    <p class="text-muted small mb-0 mt-2">Đơn hàng này đã kết thúc và không còn tiếp tục được xử lý.</p>
                </div>
            `;
            return;
        }

        const stepsOrder = ['step-pending', 'step-confirmed', 'step-processing', 'step-shipped', 'step-delivered'];
        const statusIndexMap = {
            'Pending': 0,
            'Confirmed': 1,
            'Processing': 2,
            'Shipped': 3,
            'Delivered': 4
        };

        const currentIndex = statusIndexMap[status] !== undefined ? statusIndexMap[status] : 0;
        const progressPercent = (currentIndex / (stepsOrder.length - 1)) * 100;
        if (elements.timelineProgressBar) {
            elements.timelineProgressBar.style.width = progressPercent + '%';
        }

        stepsOrder.forEach((stepId, index) => {
            const el = document.getElementById(stepId);
            if (!el) return;
            el.classList.remove('completed', 'active');
            if (index < currentIndex) {
                el.classList.add('completed');
            } else if (index === currentIndex) {
                el.classList.add('active');
            }
        });
    }

    /**
     * Xử lý thanh toán ngay qua VNPay
     */
    elements.btnPayNow.addEventListener('click', async function () {
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang chuyển hướng...';

        try {
            const res = await api.post('/api/payments/vnpay/create', {
                order_number: orderNumber
            });
            const data = res?.data || res;

            if (data && data.payment_url) {
                window.location.href = data.payment_url;
            } else {
                throw new Error('Không nhận được đường dẫn thanh toán từ cổng VNPay.');
            }
        } catch (error) {
            console.error('Lỗi khi tạo VNPay payment:', error);
            const errMsg = error?.message || error?.error?.message || 'Không thể tạo yêu cầu thanh toán VNPay.';
            showToast(errMsg, 'error');
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-credit-card me-1"></i> Thanh toán ngay qua VNPay';
        }
    });

    /**
     * Xử lý Hủy đơn hàng
     */
    elements.btnConfirmCancel.addEventListener('click', async function () {
        const reason = elements.cancelReason.value.trim();
        if (!reason) {
            elements.cancelReason.classList.add('is-invalid');
            return;
        }

        elements.cancelReason.classList.remove('is-invalid');
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang hủy...';

        try {
            const res = await api.post(`/api/orders/${encodeURIComponent(orderNumber)}/cancel`, {
                reason: reason
            });

            showToast('Hủy đơn hàng thành công.', 'success');
            // Đóng modal
            const modalEl = document.getElementById('cancelOrderModal');
            if (window.bootstrap && window.bootstrap.Modal) {
                const modal = window.bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }

            // Tải lại chi tiết đơn
            await loadOrderDetail();
        } catch (error) {
            console.error('Lỗi khi hủy đơn hàng:', error);
            const errMsg = error?.message || error?.error?.message || 'Không thể hủy đơn hàng.';
            showToast(errMsg, 'error');
        } finally {
            elements.btnConfirmCancel.disabled = false;
            elements.btnConfirmCancel.textContent = 'Xác nhận hủy đơn';
        }
    });

    // Khởi chạy
    loadOrderDetail();
});
