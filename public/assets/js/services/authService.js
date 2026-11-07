/**
 * Authentication & Profile Service
 */
(function () {
    window.AuthService = {
        /**
         * Đăng nhập
         * @param {string} email
         * @param {string} password
         * @returns {Promise<Object>}
         */
        login: async function (email, password) {
            const res = await window.ApiClient.post('/api/auth/login', { email, password });
            return res.data;
        },

        /**
         * Đăng ký
         * @param {Object} data { fullname, email, password, password_confirmation, phone }
         * @returns {Promise<Object>}
         */
        register: async function (data) {
            const res = await window.ApiClient.post('/api/auth/register', data);
            return res.data;
        },

        /**
         * Đăng xuất
         * @returns {Promise<void>}
         */
        logout: async function () {
            await window.ApiClient.post('/api/auth/logout');
        },

        /**
         * Lấy thông tin user hiện tại kèm roles & permissions
         * @returns {Promise<Object>}
         */
        me: async function () {
            const res = await window.ApiClient.get('/api/auth/me');
            return res.data;
        },

        /**
         * Đổi mật khẩu
         * @param {Object} data { current_password, new_password, new_password_confirmation }
         * @returns {Promise<Object>}
         */
        changePassword: async function (data) {
            const res = await window.ApiClient.post('/api/auth/change-password', data);
            return res.data;
        },

        /**
         * Cập nhật thông tin cá nhân
         * @param {Object} data { fullname, phone, address }
         * @returns {Promise<Object>}
         */
        updateProfile: async function (data) {
            const res = await window.ApiClient.patch('/api/profile', data);
            return res.data;
        }
    };
})();
