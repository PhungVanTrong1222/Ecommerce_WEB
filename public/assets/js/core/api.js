/**
 * ApiClient for Sanctum SPA Session & REST API calls
 */
(function () {
    /**
     * Lấy giá trị cookie theo tên
     * @param {string} name
     * @returns {string|null}
     */
    function getCookie(name) {
        const matches = document.cookie.match(new RegExp(
            '(?:^|; )' + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + '=([^;]*)'
        ));
        return matches ? decodeURIComponent(matches[1]) : null;
    }

    let csrfPromise = null;

    window.ApiClient = {
        /**
         * Đảm bảo đã có CSRF cookie trước khi gửi request biến đổi dữ liệu
         * @param {boolean} force Có bắt buộc refresh lại không
         * @returns {Promise<void>}
         */
        ensureCsrf: function (force = false) {
            const hasXsrf = !!getCookie('XSRF-TOKEN');
            if (hasXsrf && !force) {
                return Promise.resolve();
            }

            if (!csrfPromise || force) {
                csrfPromise = fetch('/sanctum/csrf-cookie', {
                    method: 'GET',
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json'
                    }
                }).then(response => {
                    csrfPromise = null;
                    if (!response.ok) {
                        throw new Error('Không thể khởi tạo phiên CSRF bảo mật');
                    }
                }).catch(err => {
                    csrfPromise = null;
                    throw err;
                });
            }

            return csrfPromise;
        },

        /**
         * Gửi request chung đến API
         * @param {string} endpoint Đường dẫn API (ví dụ: '/api/products' hoặc 'api/auth/me')
         * @param {Object} options Tùy chọn fetch
         * @returns {Promise<Object>} { success, data, meta }
         */
        request: async function (endpoint, options = {}) {
            const url = endpoint.startsWith('http') || endpoint.startsWith('/') ? endpoint : '/' + endpoint;
            const method = (options.method || 'GET').toUpperCase();
            const isModifying = ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method);

            if (isModifying) {
                await this.ensureCsrf();
            }

            const headers = {
                'Accept': 'application/json',
                ...(options.headers || {})
            };

            const xsrfToken = getCookie('XSRF-TOKEN');
            if (xsrfToken) {
                headers['X-XSRF-TOKEN'] = xsrfToken;
            }

            // Nếu không phải FormData thì mặc định là JSON
            if (options.body && !(options.body instanceof FormData) && !headers['Content-Type']) {
                headers['Content-Type'] = 'application/json';
            }

            const fetchConfig = {
                method: method,
                credentials: 'include',
                headers: headers,
                ...options
            };

            let response;
            try {
                response = await fetch(url, fetchConfig);
            } catch (networkError) {
                throw {
                    code: 'NETWORK_ERROR',
                    message: 'Không thể kết nối đến máy chủ. Vui lòng kiểm tra đường truyền.',
                    details: networkError,
                    status: 0
                };
            }

            // Xử lý tự động retry nếu gặp lỗi 419 (CSRF Token Mismatch)
            if (response.status === 419 && isModifying && !options._retried) {
                await this.ensureCsrf(true);
                return this.request(endpoint, { ...options, _retried: true });
            }

            let json = null;
            try {
                json = await response.json();
            } catch (e) {
                // Phản hồi không phải JSON hợp lệ
                if (!response.ok) {
                    throw {
                        code: 'HTTP_' + response.status,
                        message: response.statusText || 'Lỗi hệ thống',
                        details: [],
                        status: response.status
                    };
                }
                return { success: true, data: null, meta: {} };
            }

            if (!response.ok || (json && json.success === false)) {
                const errObj = (json && json.error) ? json.error : {};
                throw {
                    code: errObj.code || ('HTTP_' + response.status),
                    message: errObj.message || 'Yêu cầu không thành công',
                    details: errObj.details || [],
                    status: response.status
                };
            }

            return json;
        },

        /**
         * Gửi GET request
         */
        get: function (endpoint, params = {}, options = {}) {
            let url = endpoint;
            if (params && Object.keys(params).length > 0) {
                const searchParams = new URLSearchParams();
                Object.entries(params).forEach(([key, val]) => {
                    if (val !== null && val !== undefined && val !== '') {
                        searchParams.append(key, val);
                    }
                });
                const queryString = searchParams.toString();
                if (queryString) {
                    url += (url.includes('?') ? '&' : '?') + queryString;
                }
            }
            return this.request(url, { ...options, method: 'GET' });
        },

        /**
         * Gửi POST request
         */
        post: function (endpoint, body = {}, options = {}) {
            return this.request(endpoint, {
                ...options,
                method: 'POST',
                body: body instanceof FormData ? body : JSON.stringify(body)
            });
        },

        /**
         * Gửi PATCH request
         */
        patch: function (endpoint, body = {}, options = {}) {
            return this.request(endpoint, {
                ...options,
                method: 'PATCH',
                body: body instanceof FormData ? body : JSON.stringify(body)
            });
        },

        /**
         * Gửi DELETE request
         */
        delete: function (endpoint, body = null, options = {}) {
            const config = { ...options, method: 'DELETE' };
            if (body) {
                config.body = body instanceof FormData ? body : JSON.stringify(body);
            }
            return this.request(endpoint, config);
        },

        /**
         * Upload file qua Multipart FormData
         */
        upload: function (endpoint, formData, options = {}) {
            return this.request(endpoint, {
                ...options,
                method: 'POST',
                body: formData
                // Lưu ý: không set Content-Type header để browser tự sinh boundary
            });
        }
    };
})();
