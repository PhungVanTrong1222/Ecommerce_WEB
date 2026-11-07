/**
 * Cart Service
 */
(function () {
    window.CartService = {
        /**
         * Lấy thông tin giỏ hàng hiện tại (guest session hoặc user account)
         * @returns {Promise<Object>}
         */
        getCart: async function () {
            const res = await window.ApiClient.get('/api/cart');
            return {
                ...(res.data || {}),
                success: true,
                data: res.data
            };
        },

        /**
         * Thêm sản phẩm biến thể vào giỏ hàng
         * @param {number|string} variantId
         * @param {number} quantity
         * @returns {Promise<Object>}
         */
        addItem: async function (variantId, quantity = 1) {
            const res = await window.ApiClient.post('/api/cart/items', {
                variant_id: variantId,
                quantity: quantity
            });
            // Tự động bắn event để header cập nhật số lượng giỏ hàng
            window.dispatchEvent(new CustomEvent('cart:updated', { detail: res.data }));
            return {
                ...(res.data || {}),
                success: true,
                data: res.data
            };
        },

        /**
         * Cập nhật số lượng của một mục trong giỏ
         * @param {number|string} itemId ID của cart_item
         * @param {number} quantity
         * @returns {Promise<Object>}
         */
        updateItem: async function (itemId, quantity) {
            const res = await window.ApiClient.patch(`/api/cart/items/${itemId}`, {
                quantity: quantity
            });
            window.dispatchEvent(new CustomEvent('cart:updated', { detail: res.data }));
            return {
                ...(res.data || {}),
                success: true,
                data: res.data
            };
        },

        /**
         * Xóa một mục khỏi giỏ hàng
         * @param {number|string} itemId
         * @returns {Promise<Object>}
         */
        removeItem: async function (itemId) {
            const res = await window.ApiClient.delete(`/api/cart/items/${itemId}`);
            window.dispatchEvent(new CustomEvent('cart:updated', { detail: res.data }));
            return {
                ...(res.data || {}),
                success: true,
                data: res.data
            };
        },

        /**
         * Xóa toàn bộ giỏ hàng
         * @returns {Promise<void>}
         */
        clearCart: async function () {
            await window.ApiClient.delete('/api/cart');
            window.dispatchEvent(new CustomEvent('cart:updated', { detail: { items: [], total_quantity: 0, subtotal: 0 } }));
        }
    };
})();
