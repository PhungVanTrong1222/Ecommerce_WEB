/**
 * Utility functions for TechShop Frontend
 */
(function () {
    window.Utils = {
        /**
         * Định dạng số tiền VND
         * @param {number|string} amount
         * @returns {string}
         */
        formatPrice: function (amount) {
            const num = parseFloat(amount);
            if (isNaN(num)) return '0₫';
            return new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND'
            }).format(num);
        },

        /**
         * Định dạng ngày giờ chuẩn Việt Nam
         * @param {string|Date} dateStr
         * @param {boolean} includeTime
         * @returns {string}
         */
        formatDate: function (dateStr, includeTime = true) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;

            const day = String(d.getDate()).padStart(2, '0');
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const year = d.getFullYear();

            if (!includeTime) {
                return `${day}/${month}/${year}`;
            }

            const hours = String(d.getHours()).padStart(2, '0');
            const minutes = String(d.getMinutes()).padStart(2, '0');
            return `${hours}:${minutes} ${day}/${month}/${year}`;
        },

        /**
         * Escape HTML để phòng chống XSS
         * @param {string} text
         * @returns {string}
         */
        escapeHtml: function (text) {
            if (text === null || text === undefined) return '';
            const div = document.createElement('div');
            div.textContent = String(text);
            return div.innerHTML;
        },

        /**
         * Tính phần trăm giảm giá
         * @param {number} originalPrice
         * @param {number} salePrice
         * @returns {number|null}
         */
        discountPercent: function (originalPrice, salePrice) {
            const orig = parseFloat(originalPrice);
            const sale = parseFloat(salePrice);
            if (!orig || !sale || sale >= orig) return null;
            return Math.round(((orig - sale) / orig) * 100);
        },

        /**
         * Hiển thị thông báo Toast góc màn hình (sử dụng tiền tố ts-toast để không xung đột với Bootstrap 5)
         * @param {string} message
         * @param {'success'|'error'|'warning'|'info'} type
         * @param {number} duration Thời gian hiển thị (ms)
         */
        showToast: function (message, type = 'info', duration = 3500) {
            let container = document.getElementById('tsToastContainer') || document.getElementById('toast-container') || document.getElementById('toastContainer');
            if (!container) {
                container = document.createElement('div');
                container.id = 'tsToastContainer';
                container.className = 'ts-toast-container';
                document.body.appendChild(container);
            }

            const toast = document.createElement('div');
            toast.className = `ts-toast ts-toast-${type}`;

            let iconHtml = '<i class="fa-solid fa-circle-info ts-toast-icon"></i>';
            if (type === 'success') {
                iconHtml = '<i class="fa-solid fa-circle-check ts-toast-icon"></i>';
            } else if (type === 'error') {
                iconHtml = '<i class="fa-solid fa-circle-xmark ts-toast-icon"></i>';
            } else if (type === 'warning') {
                iconHtml = '<i class="fa-solid fa-triangle-exclamation ts-toast-icon"></i>';
            }

            toast.innerHTML = `
                ${iconHtml}
                <div class="ts-toast-body">${this.escapeHtml(message)}</div>
                <button type="button" class="ts-toast-close" aria-label="Đóng">&times;</button>
            `;

            container.appendChild(toast);

            const removeToast = () => {
                toast.classList.add('ts-toast-hide');
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            };

            const closeBtn = toast.querySelector('.ts-toast-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', removeToast);
            }

            setTimeout(removeToast, duration);
        },

        /**
         * Lấy tham số từ Query String URL
         * @param {string} name
         * @returns {string|null}
         */
        getQueryParam: function (name) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(name);
        },

        /**
         * Cập nhật Query Parameters trên URL mà không tải lại trang
         * @param {Object} params
         * @param {boolean} replaceState
         */
        setQueryParams: function (params, replaceState = false) {
            const url = new URL(window.location);
            Object.keys(params).forEach(key => {
                const val = params[key];
                if (val === null || val === undefined || val === '') {
                    url.searchParams.delete(key);
                } else {
                    url.searchParams.set(key, val);
                }
            });

            if (replaceState) {
                window.history.replaceState({}, '', url);
            } else {
                window.history.pushState({}, '', url);
            }
        }
    };
})();
