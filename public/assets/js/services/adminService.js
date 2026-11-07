/**
 * Admin Service (CRUD & Management APIs)
 */
(function () {
    window.AdminService = {
        // --- 1. Categories Management ---
        getCategories: async function (params = {}) {
            const res = await window.ApiClient.get('/api/admin/categories', params);
            return res.data;
        },

        createCategory: async function (data) {
            const res = await window.ApiClient.post('/api/admin/categories', data);
            return res.data;
        },

        updateCategory: async function (id, data) {
            const res = await window.ApiClient.patch(`/api/admin/categories/${id}`, data);
            return res.data;
        },

        // --- 2. Products & Variants Management ---
        getProducts: async function (params = {}) {
            const res = await window.ApiClient.get('/api/admin/products', params);
            return {
                items: res.data || [],
                meta: res.meta || {}
            };
        },

        getProductDetail: async function (id) {
            const res = await window.ApiClient.get(`/api/admin/products/${id}`);
            return res.data;
        },

        createProduct: async function (data) {
            const res = await window.ApiClient.post('/api/admin/products', data);
            return res.data;
        },

        updateProduct: async function (id, data) {
            const res = await window.ApiClient.patch(`/api/admin/products/${id}`, data);
            return res.data;
        },

        createVariant: async function (productId, data) {
            const res = await window.ApiClient.post(`/api/admin/products/${productId}/variants`, data);
            return res.data;
        },

        updateVariant: async function (variantId, data) {
            const res = await window.ApiClient.patch(`/api/admin/variants/${variantId}`, data);
            return res.data;
        },

        uploadProductImage: async function (productId, file, isPrimary = false) {
            const formData = new FormData();
            formData.append('image', file);
            if (isPrimary) {
                formData.append('is_primary', '1');
            }
            const res = await window.ApiClient.upload(`/api/admin/products/${productId}/images`, formData);
            return res.data;
        },

        deleteProductImage: async function (imageId) {
            await window.ApiClient.delete(`/api/admin/product-images/${imageId}`);
        },

        // --- 3. Inventory Management ---
        getInventory: async function (params = {}) {
            const res = await window.ApiClient.get('/api/admin/inventory', params);
            return {
                items: res.data || [],
                meta: res.meta || {}
            };
        },

        adjustInventory: async function (variantId, quantityChange, reason = '') {
            const res = await window.ApiClient.post(`/api/admin/inventory/${variantId}/adjust`, {
                quantity_change: quantityChange,
                reason: reason
            });
            return res.data;
        },

        // --- 4. Orders Management ---
        getOrders: async function (params = {}) {
            const res = await window.ApiClient.get('/api/admin/orders', params);
            return {
                items: res.data || [],
                meta: res.meta || {}
            };
        },

        getOrderDetail: async function (orderNumber) {
            const res = await window.ApiClient.get(`/api/admin/orders/${encodeURIComponent(orderNumber)}`);
            return res.data;
        },

        updateOrderStatus: async function (orderNumber, status, notes = '') {
            const res = await window.ApiClient.patch(`/api/admin/orders/${encodeURIComponent(orderNumber)}/status`, {
                status: status,
                notes: notes
            });
            return res.data;
        },

        // --- 5. Payments Management ---
        getPayments: async function (params = {}) {
            const res = await window.ApiClient.get('/api/admin/payments', params);
            return {
                items: res.data || [],
                meta: res.meta || {}
            };
        },

        confirmCodPayment: async function (orderNumber) {
            const res = await window.ApiClient.post(`/api/admin/payments/${encodeURIComponent(orderNumber)}/confirm-cod`);
            return res.data;
        },

        // --- 6. Users Management ---
        getUsers: async function (params = {}) {
            const res = await window.ApiClient.get('/api/admin/users', params);
            return {
                items: res.data || [],
                meta: res.meta || {}
            };
        },

        updateUserStatus: async function (userId, status) {
            const res = await window.ApiClient.patch(`/api/admin/users/${userId}/status`, {
                status: status
            });
            return res.data;
        }
    };
})();
