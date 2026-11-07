/**
 * Order Service
 */
(function () {
    window.OrderService = {
        /**
         * Đặt hàng từ giỏ hàng hiện tại
         * @param {Object} data { shipping_fullname, shipping_phone, shipping_address, payment_method, notes }
         * @returns {Promise<Object>}
         */
        checkout: async function (data) {
            const res = await window.ApiClient.post('/api/orders/checkout', data);
            // Sau khi đặt hàng thành công, giỏ hàng đã bị xóa ở backend -> phát event cập nhật
            window.dispatchEvent(new CustomEvent('cart:updated', { detail: { items: [], total_quantity: 0, subtotal: 0 } }));
            return res.data;
        },

        /**
         * Lấy danh sách đơn hàng của người dùng hiện tại
         * @param {Object} params { page, page_size, status }
         * @returns {Promise<{items: Array, meta: Object}>}
         */
        getOrders: async function (params = {}) {
            const res = await window.ApiClient.get('/api/orders', params);
            return {
                items: res.data || [],
                meta: res.meta || {}
            };
        },

        /**
         * Lấy chi tiết đơn hàng theo mã đơn (orderNumber)
         * @param {string} orderNumber
         * @returns {Promise<Object>}
         */
        getOrderDetail: async function (orderNumber) {
            const res = await window.ApiClient.get(`/api/orders/${encodeURIComponent(orderNumber)}`);
            return res.data;
        },

        /**
         * Hủy đơn hàng
         * @param {string} orderNumber
         * @param {string} reason
         * @returns {Promise<Object>}
         */
        cancelOrder: async function (orderNumber, reason = '') {
            const res = await window.ApiClient.post(`/api/orders/${encodeURIComponent(orderNumber)}/cancel`, {
                reason: reason
            });
            return res.data;
        }
    };
})();
