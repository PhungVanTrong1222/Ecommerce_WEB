/**
 * TechShop - Checkout Page JavaScript
 * Quản lý quy trình đặt hàng: xác thực, điền thông tin, chọn thanh toán, submit checkout
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

    const elements = {
        loading: document.getElementById('checkout-loading'),
        content: document.getElementById('checkout-content'),
        form: document.getElementById('checkout-form'),
        fullname: document.getElementById('fullname'),
        phone: document.getElementById('phone'),
        address: document.getElementById('address'),
        note: document.getElementById('note'),
        itemsList: document.getElementById('checkout-items-list'),
        itemCount: document.getElementById('checkout-item-count'),
        subtotal: document.getElementById('checkout-subtotal'),
        totalAmount: document.getElementById('checkout-total-amount'),
        btnSubmitOrder: document.getElementById('btn-submit-order')
    };

    let currentUser = null;
    let currentCart = null;
    let isSubmitting = false;

    /**
     * Khởi tạo trang: kiểm tra đăng nhập & giỏ hàng
     */
    async function initCheckout() {
        // 1. Kiểm tra xác thực người dùng (ApiClient throw 401 khi chưa login)
        try {
            const authRes = await api.get('/api/auth/me');
            if (authRes && authRes.data) {
                currentUser = authRes.data;
            } else {
                handleUnauthenticated();
                return;
            }
        } catch (authErr) {
            handleUnauthenticated();
            return;
        }

        // Điền sẵn thông tin người dùng nếu có
        if (currentUser) {
            if (currentUser.fullname) elements.fullname.value = currentUser.fullname;
            if (currentUser.phone) elements.phone.value = currentUser.phone;
            if (currentUser.address) elements.address.value = currentUser.address;
        }

        // 2. Tải giỏ hàng
        try {
            const cartRes = await api.get('/api/cart');
            const cartData = cartRes?.data || cartRes;

            if (!cartData || !cartData.items || cartData.items.length === 0) {
                showToast('Giỏ hàng của bạn đang trống. Đang chuyển về trang giỏ hàng...', 'info');
                setTimeout(() => {
                    window.location.href = '/cart';
                }, 1000);
                return;
            }

            currentCart = cartData;

            // 3. Kiểm tra sản phẩm không khả dụng
            if (currentCart.has_unavailable_items) {
                showToast('Giỏ hàng có sản phẩm không khả dụng hoặc đã hết hàng. Vui lòng kiểm tra lại.', 'warning');
                setTimeout(() => {
                    window.location.href = '/cart';
                }, 1500);
                return;
            }

            // Render tóm tắt sản phẩm
            renderOrderSummary(currentCart);

            elements.loading.classList.add('d-none');
            elements.content.classList.remove('d-none');
        } catch (cartErr) {
            console.error('Lỗi tải giỏ hàng khi checkout:', cartErr);
            showToast(cartErr?.message || 'Không thể chuẩn bị thông tin thanh toán. Vui lòng thử lại sau.', 'error');
            setTimeout(() => {
                window.location.href = '/cart';
            }, 1500);
        }
    }

    function handleUnauthenticated() {
        showToast('Vui lòng đăng nhập để tiến hành đặt hàng.', 'warning');
        setTimeout(() => {
            window.location.href = '/login?redirect=' + encodeURIComponent('/checkout');
        }, 500);
    }

    /**
     * Render tóm tắt đơn hàng ở cột bên phải
     */
    function renderOrderSummary(cart) {
        elements.itemCount.textContent = cart.total_quantity;
        elements.subtotal.textContent = formatPrice(cart.subtotal);
        elements.totalAmount.textContent = formatPrice(cart.subtotal);

        elements.itemsList.innerHTML = '';
        (cart.items || []).forEach(item => {
            const itemRow = document.createElement('div');
            itemRow.className = 'd-flex justify-content-between align-items-center mb-2 pb-2 border-bottom';

            const imgHtml = item.image_url
                ? `<img src="${escapeHtml(item.image_url)}" alt="${escapeHtml(item.product_name)}" class="rounded me-2" style="width: 44px; height: 44px; object-fit: cover;">`
                : `<div class="bg-light rounded me-2 d-flex align-items-center justify-content-center text-muted" style="width: 44px; height: 44px;"><i class="bi bi-box"></i></div>`;

            itemRow.innerHTML = `
                <div class="d-flex align-items-center" style="max-width: 65%;">
                    ${imgHtml}
                    <div class="text-truncate">
                        <div class="fw-semibold text-truncate small">${escapeHtml(item.product_name)}</div>
                        <div class="text-muted small">${escapeHtml(item.variant_name)} × ${item.quantity}</div>
                    </div>
                </div>
                <div class="fw-semibold text-danger small text-end">
                    ${formatPrice(item.subtotal)}
                </div>
            `;
            elements.itemsList.appendChild(itemRow);
        });
    }

    /**
     * Xử lý gửi đơn hàng
     */
    elements.btnSubmitOrder.addEventListener('click', async function () {
        if (isSubmitting) return;

        // Reset trạng thái validation
        elements.fullname.classList.remove('is-invalid');
        elements.phone.classList.remove('is-invalid');
        elements.address.classList.remove('is-invalid');

        let isValid = true;
        const fullname = elements.fullname.value.trim();
        const phone = elements.phone.value.trim();
        const address = elements.address.value.trim();
        const note = elements.note.value.trim();

        if (!fullname) {
            elements.fullname.classList.add('is-invalid');
            isValid = false;
        }

        const phoneRegex = /(84|0[3|5|7|8|9])+([0-9]{8})\b/;
        if (!phone || phone.length < 8) {
            elements.phone.classList.add('is-invalid');
            isValid = false;
        }

        if (!address) {
            elements.address.classList.add('is-invalid');
            isValid = false;
        }

        const paymentMethodEl = document.querySelector('input[name="payment_method"]:checked');
        const paymentMethod = paymentMethodEl ? paymentMethodEl.value : 'COD';

        if (!isValid) {
            showToast('Vui lòng điền đầy đủ và chính xác các thông tin nhận hàng bắt buộc.', 'warning');
            return;
        }

        isSubmitting = true;
        elements.btnSubmitOrder.disabled = true;
        elements.btnSubmitOrder.innerHTML = `
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            Đang xử lý đơn hàng...
        `;

        try {
            const payload = {
                fullname: fullname,
                phone: phone,
                address: address,
                note: note || null,
                payment_method: paymentMethod
            };

            const res = await api.post('/api/orders/checkout', payload);
            const orderData = res?.data || res;

            if (orderData && orderData.order_number) {
                showToast('Đặt hàng thành công!', 'success');

                if (window.CartService && typeof window.CartService.updateBadge === 'function') {
                    window.CartService.updateBadge(0);
                }

                // Nếu chọn VNPay và có link thanh toán
                if (paymentMethod === 'VNPay' && orderData.payment_url) {
                    window.location.href = orderData.payment_url;
                } else {
                    // Thanh toán COD
                    window.location.href = '/orders/' + encodeURIComponent(orderData.order_number) + '?placed=1';
                }
            } else {
                throw new Error('Không nhận được thông tin đơn hàng đã tạo.');
            }
        } catch (error) {
            console.error('Lỗi khi submit checkout:', error);

            const errCode = error?.code || error?.error?.code;
            let errMsg = error?.message || error?.error?.message || 'Đặt hàng không thành công. Vui lòng kiểm tra lại.';

            // Nếu có chi tiết validation
            if (error?.details && typeof error.details === 'object') {
                const detailMsgs = Object.values(error.details).flat().join(' ');
                if (detailMsgs) errMsg = detailMsgs;
            }

            if (errCode === 'INSUFFICIENT_STOCK' || errCode === 'VARIANT_INACTIVE' || errCode === 'CART_EMPTY') {
                showToast(errMsg, 'error');
                setTimeout(() => {
                    window.location.href = '/cart';
                }, 2000);
            } else {
                showToast(errMsg, 'error');
            }

            resetSubmitButton();
        }
    });

    function resetSubmitButton() {
        isSubmitting = false;
        elements.btnSubmitOrder.disabled = false;
        elements.btnSubmitOrder.innerHTML = 'Xác nhận đặt hàng';
    }

    // Khởi chạy
    initCheckout();
});
