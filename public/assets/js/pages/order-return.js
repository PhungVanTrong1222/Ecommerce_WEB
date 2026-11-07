/**
 * TechShop - VNPay Order Return Page JavaScript
 * Xử lý kết quả trả về từ cổng thanh toán VNPay
 */

document.addEventListener('DOMContentLoaded', async function () {
    const formatPrice = (v) => {
        if (window.Utils && typeof window.Utils.formatPrice === 'function') {
            return window.Utils.formatPrice(v);
        }
        return Number(v || 0).toLocaleString('vi-VN') + ' ₫';
    };

    const escapeHtml = (s) => {
        if (window.Utils && typeof window.Utils.escapeHtml === 'function') {
            return window.Utils.escapeHtml(s);
        }
        return String(s || '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
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
            return res.json();
        }
    };

    const elements = {
        loading: document.getElementById('vnpay-loading'),
        result: document.getElementById('vnpay-result'),
        header: document.getElementById('result-header'),
        iconContainer: document.getElementById('result-icon-container'),
        title: document.getElementById('result-title'),
        subtitle: document.getElementById('result-subtitle'),
        orderNumber: document.getElementById('res-order-number'),
        amount: document.getElementById('res-amount'),
        transactionNo: document.getElementById('res-transaction-no'),
        rowTransactionNo: document.getElementById('row-transaction-no'),
        paymentStatus: document.getElementById('res-payment-status'),
        message: document.getElementById('res-message'),
        btnViewOrder: document.getElementById('btn-view-order')
    };

    const queryString = window.location.search;

    if (!queryString) {
        elements.loading.classList.add('d-none');
        elements.result.classList.remove('d-none');
        elements.header.className = 'bg-danger text-white text-center py-4 px-3';
        elements.iconContainer.innerHTML = `
            <svg width="64" height="64" fill="currentColor" viewBox="0 0 16 16">
                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
            </svg>
        `;
        elements.title.textContent = 'Thông tin thanh toán không hợp lệ';
        elements.subtitle.textContent = 'Không tìm thấy thông số giao dịch từ cổng thanh toán.';
        elements.message.textContent = 'Thiếu tham số giao dịch';
        elements.btnViewOrder.classList.add('d-none');
        return;
    }

    try {
        const res = await api.get('/api/payments/vnpay/return' + queryString);

        elements.loading.classList.add('d-none');
        elements.result.classList.remove('d-none');

        if (res && res.success && res.data) {
            const data = res.data;
            const isSuccess = data.is_success;
            const isValidSig = data.is_valid_signature;

            // Thiết lập header & icon
            if (isSuccess && isValidSig) {
                elements.header.className = 'return-header-success text-white text-center py-5 px-4';
                elements.iconContainer.innerHTML = `<i class="fa-solid fa-circle-check"></i>`;
                elements.title.textContent = 'Thanh toán thành công!';
                elements.subtitle.textContent = 'Đơn hàng của bạn đã được ghi nhận thanh toán thành công.';
            } else {
                elements.header.className = 'return-header-danger text-white text-center py-5 px-4';
                elements.iconContainer.innerHTML = `<i class="fa-solid fa-circle-xmark"></i>`;
                elements.title.textContent = 'Thanh toán không thành công';
                elements.subtitle.textContent = !isValidSig ? 'Chữ ký bảo mật không hợp lệ.' : 'Giao dịch bị từ chối hoặc đã bị hủy.';
            }

            // Điền thông tin chi tiết
            elements.orderNumber.textContent = data.order_number || 'N/A';
            elements.amount.textContent = formatPrice(data.amount);

            if (data.transaction_no) {
                elements.transactionNo.textContent = data.transaction_no;
            } else {
                elements.rowTransactionNo.classList.add('d-none');
            }

            elements.paymentStatus.innerHTML = isSuccess
                ? '<span class="badge bg-success">Đã thanh toán</span>'
                : '<span class="badge bg-danger">Chưa thanh toán</span>';

            elements.message.textContent = data.message || 'Giao dịch hoàn tất';

            if (data.order_number) {
                elements.btnViewOrder.href = '/orders/' + encodeURIComponent(data.order_number);
                elements.btnViewOrder.classList.remove('d-none');
            } else {
                elements.btnViewOrder.classList.add('d-none');
            }
        } else {
            elements.header.className = 'bg-danger text-white text-center py-4 px-3';
            elements.title.textContent = 'Lỗi xử lý kết quả';
            elements.subtitle.textContent = res?.error?.message || 'Không thể xác thực giao dịch.';
            elements.message.textContent = res?.error?.message || 'Lỗi không xác định';
            elements.btnViewOrder.classList.add('d-none');
        }
    } catch (error) {
        console.error('Lỗi khi gọi VNPay return API:', error);
        elements.loading.classList.add('d-none');
        elements.result.classList.remove('d-none');
        elements.header.className = 'bg-danger text-white text-center py-4 px-3';
        elements.title.textContent = 'Lỗi kết nối';
        elements.subtitle.textContent = 'Đã xảy ra lỗi khi kiểm tra kết quả thanh toán.';
        elements.message.textContent = 'Lỗi mạng hoặc máy chủ không phản hồi';
        elements.btnViewOrder.classList.add('d-none');
    }
});
