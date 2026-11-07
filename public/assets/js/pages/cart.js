/**
 * TechShop - Cart Page JavaScript
 * Quản lý giỏ hàng: hiển thị, tăng giảm số lượng, xóa item, kiểm tra khả dụng
 */

document.addEventListener('DOMContentLoaded', function () {
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
        },
        async delete(url) {
            if (window.ApiClient && typeof window.ApiClient.delete === 'function') {
                return window.ApiClient.delete(url);
            }
            const res = await fetch(url, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
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
        }
    };

    const elements = {
        loading: document.getElementById('cart-loading'),
        empty: document.getElementById('cart-empty'),
        content: document.getElementById('cart-content'),
        tbody: document.getElementById('cart-items-tbody'),
        itemCountText: document.getElementById('cart-item-count-text'),
        unavailableAlert: document.getElementById('unavailable-alert'),
        summaryTotalQty: document.getElementById('summary-total-qty'),
        summarySubtotal: document.getElementById('summary-subtotal'),
        summaryTotalAmount: document.getElementById('summary-total-amount'),
        checkoutWarningText: document.getElementById('checkout-warning-text'),
        btnCheckout: document.getElementById('btn-checkout'),
        btnClearCart: document.getElementById('btn-clear-cart')
    };

    let cartState = null;
    let updatingItemId = null;

    /**
     * Tải dữ liệu giỏ hàng từ API
     */
    async function loadCart() {
        elements.loading.classList.remove('d-none');
        elements.empty.classList.add('d-none');
        elements.content.classList.add('d-none');

        try {
            const res = await api.get('/api/cart');
            const data = res?.data || res;
            if (data) {
                cartState = data;
                renderCart(data);
            } else {
                showToast('Không thể tải dữ liệu giỏ hàng.', 'error');
            }
        } catch (error) {
            console.error('Lỗi tải giỏ hàng:', error);
            const errMsg = error?.message || error?.error?.message || 'Đã xảy ra lỗi khi tải giỏ hàng.';
            showToast(errMsg, 'error');
        } finally {
            elements.loading.classList.add('d-none');
        }
    }

    /**
     * Render dữ liệu giỏ hàng ra giao diện
     */
    function renderCart(cart) {
        const items = cart.items || [];

        if (items.length === 0) {
            elements.empty.classList.remove('d-none');
            elements.content.classList.add('d-none');
            elements.itemCountText.textContent = '';
            if (window.CartService && typeof window.CartService.updateBadge === 'function') {
                window.CartService.updateBadge(0);
            }
            return;
        }

        elements.empty.classList.add('d-none');
        elements.content.classList.remove('d-none');
        elements.itemCountText.textContent = `(${cart.total_quantity} sản phẩm)`;

        if (window.CartService && typeof window.CartService.updateBadge === 'function') {
            window.CartService.updateBadge(cart.total_quantity);
        }

        // Cảnh báo unavailable items
        if (cart.has_unavailable_items) {
            elements.unavailableAlert.classList.remove('d-none');
            elements.unavailableAlert.classList.add('d-flex');
            elements.checkoutWarningText.classList.remove('d-none');
            elements.btnCheckout.disabled = true;
            elements.btnCheckout.classList.add('disabled');
        } else {
            elements.unavailableAlert.classList.add('d-none');
            elements.unavailableAlert.classList.remove('d-flex');
            elements.checkoutWarningText.classList.add('d-none');
            elements.btnCheckout.disabled = false;
            elements.btnCheckout.classList.remove('disabled');
        }

        // Tóm tắt đơn hàng
        elements.summaryTotalQty.textContent = cart.total_quantity;
        elements.summarySubtotal.textContent = formatPrice(cart.subtotal);
        elements.summaryTotalAmount.textContent = formatPrice(cart.subtotal);

        // Render từng dòng
        elements.tbody.innerHTML = '';
        items.forEach(item => {
            const tr = document.createElement('tr');
            if (!item.is_available) {
                tr.classList.add('unavailable-row');
            }

            let badgeHtml = '';
            if (!item.is_available) {
                if (item.unavailable_reason === 'OUT_OF_STOCK') {
                    badgeHtml = '<span class="badge bg-danger ms-2"><i class="fa-solid fa-circle-xmark me-1"></i>Hết hàng</span>';
                } else if (item.unavailable_reason === 'INSUFFICIENT_STOCK') {
                    badgeHtml = `<span class="badge bg-warning text-dark ms-2"><i class="fa-solid fa-triangle-exclamation me-1"></i>Chỉ còn ${item.available_stock}</span>`;
                } else if (item.unavailable_reason === 'INACTIVE') {
                    badgeHtml = '<span class="badge bg-secondary ms-2"><i class="fa-solid fa-ban me-1"></i>Ngừng bán</span>';
                }
            }

            const imgHtml = item.image_url
                ? `<img src="${escapeHtml(item.image_url)}" alt="${escapeHtml(item.product_name)}" class="cart-item-img me-3">`
                : `<div class="cart-item-img me-3 d-flex align-items-center justify-content-center text-muted"><i class="fa-solid fa-image"></i></div>`;

            const priceHtml = item.sale_price !== null && item.sale_price < item.price
                ? `<div>
                    <span class="fw-bold text-danger">${formatPrice(item.effective_price)}</span>
                    <span class="text-muted text-decoration-line-through small ms-1 d-block">${formatPrice(item.price)}</span>
                   </div>`
                : `<span class="fw-bold text-dark">${formatPrice(item.effective_price)}</span>`;

            tr.innerHTML = `
                <td>
                    <div class="d-flex align-items-center">
                        ${imgHtml}
                        <div>
                            <a href="/products/${escapeHtml(item.product_slug)}" class="cart-item-title d-block mb-1">
                                ${escapeHtml(item.product_name)}
                            </a>
                            <div class="cart-item-meta">
                                <span>Phân loại: <strong>${escapeHtml(item.variant_name)}</strong></span>
                                <span class="mx-1">•</span>
                                <span>SKU: ${escapeHtml(item.sku)}</span>
                                ${badgeHtml}
                            </div>
                        </div>
                    </div>
                </td>
                <td>${priceHtml}</td>
                <td class="text-center">
                    <div class="qty-control mx-auto">
                        <button class="btn-decrease" type="button" data-id="${item.id}" data-qty="${item.quantity}" ${item.quantity <= 1 ? 'disabled' : ''}>-</button>
                        <input type="text" class="input-qty" value="${item.quantity}" data-id="${item.id}" readonly>
                        <button class="btn-increase" type="button" data-id="${item.id}" data-qty="${item.quantity}" ${item.quantity >= 99 ? 'disabled' : ''}>+</button>
                    </div>
                </td>
                <td class="text-end fw-bold ${item.is_available ? 'text-danger' : 'text-muted'}">
                    ${formatPrice(item.subtotal)}
                </td>
                <td class="text-center">
                    <button type="button" class="btn-remove-cart-item btn-remove-item" data-id="${item.id}" title="Xóa sản phẩm">
                        <i class="fa-regular fa-trash-can"></i>
                    </button>
                </td>
            `;

            elements.tbody.appendChild(tr);
        });

        attachItemEvents();
    }

    /**
     * Gắn sự kiện cho các nút tăng giảm & xóa
     */
    function attachItemEvents() {
        document.querySelectorAll('.btn-decrease').forEach(btn => {
            btn.addEventListener('click', async function () {
                const id = this.getAttribute('data-id');
                const curQty = parseInt(this.getAttribute('data-qty'), 10);
                if (curQty > 1) {
                    await updateQuantity(id, curQty - 1);
                }
            });
        });

        document.querySelectorAll('.btn-increase').forEach(btn => {
            btn.addEventListener('click', async function () {
                const id = this.getAttribute('data-id');
                const curQty = parseInt(this.getAttribute('data-qty'), 10);
                if (curQty < 99) {
                    await updateQuantity(id, curQty + 1);
                }
            });
        });

        document.querySelectorAll('.btn-remove-item').forEach(btn => {
            btn.addEventListener('click', async function () {
                const id = this.getAttribute('data-id');
                if (confirm('Bạn có chắc chắn muốn xóa sản phẩm này khỏi giỏ hàng?')) {
                    await removeItem(id);
                }
            });
        });
    }

    /**
     * Cập nhật số lượng sản phẩm
     */
    async function updateQuantity(itemId, newQty) {
        if (updatingItemId) return;
        updatingItemId = itemId;

        try {
            const res = await api.patch(`/api/cart/items/${itemId}`, { quantity: newQty });
            const data = res?.data || res;
            if (data && data.items) {
                cartState = data;
                renderCart(data);
            } else {
                throw new Error('Dữ liệu giỏ hàng trả về không hợp lệ.');
            }
        } catch (error) {
            console.error('Lỗi cập nhật số lượng:', error);
            const errMsg = error?.message || error?.error?.message || 'Không thể cập nhật số lượng.';
            showToast(errMsg, 'error');
            await loadCart();
        } finally {
            updatingItemId = null;
        }
    }

    /**
     * Xóa 1 sản phẩm khỏi giỏ hàng
     */
    async function removeItem(itemId) {
        try {
            const res = await api.delete(`/api/cart/items/${itemId}`);
            const data = res?.data || res;
            if (data && data.items !== undefined) {
                cartState = data;
                renderCart(data);
                showToast('Đã xóa sản phẩm khỏi giỏ hàng.', 'success');
            } else {
                throw new Error('Không thể xóa sản phẩm.');
            }
        } catch (error) {
            console.error('Lỗi xóa sản phẩm:', error);
            const errMsg = error?.message || error?.error?.message || 'Không thể xóa sản phẩm.';
            showToast(errMsg, 'error');
        }
    }

    /**
     * Xóa toàn bộ giỏ hàng
     */
    elements.btnClearCart.addEventListener('click', async function () {
        if (!confirm('Bạn có chắc chắn muốn xóa toàn bộ sản phẩm trong giỏ hàng?')) {
            return;
        }

        try {
            const res = await api.delete('/api/cart');
            const data = res?.data || res;
            if (data && data.items !== undefined) {
                cartState = data;
                renderCart(data);
                showToast('Đã dọn sạch giỏ hàng.', 'success');
            } else {
                throw new Error('Không thể dọn sạch giỏ hàng.');
            }
        } catch (error) {
            console.error('Lỗi dọn sạch giỏ hàng:', error);
            const errMsg = error?.message || error?.error?.message || 'Đã xảy ra lỗi khi dọn sạch giỏ hàng.';
            showToast(errMsg, 'error');
        }
    });

    /**
     * Xử lý tiến hành đặt hàng (Checkout)
     */
    elements.btnCheckout.addEventListener('click', async function () {
        if (!cartState || !cartState.items || cartState.items.length === 0) {
            showToast('Giỏ hàng của bạn đang trống.', 'warning');
            return;
        }

        if (cartState.has_unavailable_items) {
            showToast('Vui lòng điều chỉnh hoặc xóa các sản phẩm không khả dụng trước khi đặt hàng.', 'warning');
            return;
        }

        // Kiểm tra xem đã đăng nhập chưa (ApiClient throw 401 khi chưa login)
        try {
            const authRes = await api.get('/api/auth/me');
            if (authRes && (authRes.data || authRes.id)) {
                window.location.href = '/checkout';
            } else {
                redirectToLogin();
            }
        } catch (authErr) {
            redirectToLogin();
        }
    });

    function redirectToLogin() {
        showToast('Vui lòng đăng nhập để đặt hàng (giỏ hàng của bạn sẽ được giữ nguyên).', 'info');
        setTimeout(() => {
            window.location.href = '/login?redirect=' + encodeURIComponent('/checkout');
        }, 800);
    }

    // Khởi chạy ban đầu
    loadCart();
});
