/**
 * Catalog Service (Categories & Products)
 */
(function () {
    let categoriesPromise = null;

    window.CatalogService = {
        /**
         * Lấy danh sách danh mục sản phẩm đang active (kèm cache promise chống trùng lặp request trên cùng trang)
         * @param {boolean} force Có bắt buộc gọi lại API hay không
         * @returns {Promise<{success: boolean, data: Array}>}
         */
        getCategories: function (force = false) {
            if (categoriesPromise && !force) {
                return categoriesPromise;
            }
            categoriesPromise = window.ApiClient.get('/api/categories').catch(err => {
                categoriesPromise = null;
                throw err;
            });
            return categoriesPromise;
        },

        /**
         * Lấy danh sách sản phẩm hiển thị công khai (hỗ trợ filter, pagination, sort)
         * @param {Object} params { category_slug, brand, min_price, max_price, sort, page, page_size, search, q }
         * @returns {Promise<{success: boolean, data: Array, items: Array, meta: Object}>}
         */
        getProducts: async function (params = {}) {
            const res = await window.ApiClient.get('/api/products', params);
            return {
                success: true,
                data: res.data || [],
                items: res.data || [],
                meta: res.meta || {}
            };
        },

        /**
         * Lấy chi tiết sản phẩm theo slug (kèm đầy đủ biến thể và hình ảnh)
         * @param {string} slug
         * @returns {Promise<{success: boolean, data: Object}>}
         */
        getProductDetail: async function (slug) {
            const res = await window.ApiClient.get(`/api/products/${encodeURIComponent(slug)}`);
            return res;
        }
    };
})();
