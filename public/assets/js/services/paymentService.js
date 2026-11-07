/**
 * Payment Service (VNPay)
 */
(function () {
    window.PaymentService = {
        /**
         * Tạo URL thanh toán VNPay cho đơn hàng
         * @param {string} orderNumber
         * @returns {Promise<{payment_url: string}>}
         */
        createVnPayUrl: async function (orderNumber) {
            const res = await window.ApiClient.post('/api/payments/vnpay/create', {
                order_number: orderNumber
            });
            return res.data;
        },

        /**
         * Xác minh kết quả trả về từ cổng VNPay sau khi redirect
         * @param {Object} params Tham số từ URL (location.search)
         * @returns {Promise<Object>}
         */
        verifyVnPayReturn: async function (params = {}) {
            const res = await window.ApiClient.get('/api/payments/vnpay/return', params);
            return res.data;
        }
    };
})();
