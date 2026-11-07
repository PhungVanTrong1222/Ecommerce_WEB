/**
 * TechShop - Products Catalog Logic (Vanilla JS)
 * Module: Storefront
 */

document.addEventListener('DOMContentLoaded', function () {
    CatalogApp.init();
});

const CatalogApp = {
    // Trạng thái bộ lọc
    filters: {
        q: '',
        category: '',
        brand: '',
        min_price: '',
        max_price: '',
        on_sale: '',
        sort: 'newest',
        page: 1,
        page_size: 12
    },

    categories: [],

    init() {
        this.parseUrlParams();
        this.bindEvents();
        this.loadCategories().then(() => {
            this.fetchProducts();
        });
    },

    // 1. Đọc params từ URL
    parseUrlParams() {
        const urlParams = new URLSearchParams(window.location.search);

        this.filters.q = urlParams.get('q') || '';
        this.filters.category = urlParams.get('category') || '';
        this.filters.brand = urlParams.get('brand') || '';
        this.filters.min_price = urlParams.get('min_price') || '';
        this.filters.max_price = urlParams.get('max_price') || '';
        this.filters.on_sale = urlParams.get('on_sale') || (urlParams.get('sale') === '1' ? '1' : '');
        this.filters.sort = urlParams.get('sort') || 'newest';
        this.filters.page = parseInt(urlParams.get('page'), 10) || 1;

        // Đồng bộ lên UI ban đầu
        const keywordInput = document.getElementById('filterKeyword');
        if (keywordInput) keywordInput.value = this.filters.q;

        const minInput = document.getElementById('minPriceInput');
        if (minInput) minInput.value = this.filters.min_price;

        const maxInput = document.getElementById('maxPriceInput');
        if (maxInput) maxInput.value = this.filters.max_price;

        const onSaleCheckbox = document.getElementById('filterOnSale');
        if (onSaleCheckbox) onSaleCheckbox.checked = this.filters.on_sale === '1';

        const sortSelect = document.getElementById('sortSelect');
        if (sortSelect) sortSelect.value = this.filters.sort;

        // Highlight brand chip
        this.updateBrandChipsUI();

        // Highlight quick price pills
        this.updateQuickPriceUI();
    },

    // 2. Cập nhật query param lên URL
    updateUrl() {
        const params = new URLSearchParams();

        if (this.filters.q) params.set('q', this.filters.q);
        if (this.filters.category) params.set('category', this.filters.category);
        if (this.filters.brand) params.set('brand', this.filters.brand);
        if (this.filters.min_price) params.set('min_price', this.filters.min_price);
        if (this.filters.max_price) params.set('max_price', this.filters.max_price);
        if (this.filters.on_sale === '1') params.set('on_sale', '1');
        if (this.filters.sort && this.filters.sort !== 'newest') params.set('sort', this.filters.sort);
        if (this.filters.page && this.filters.page > 1) params.set('page', this.filters.page);

        const newUrl = `${window.location.pathname}${params.toString() ? '?' + params.toString() : ''}`;
        window.history.pushState({}, '', newUrl);
    },

    // 3. Tải danh mục và render vào sidebar
    async loadCategories() {
        const container = document.getElementById('categoryFilterList');
        if (!container) return;

        try {
            let list = [];
            if (window.CatalogService && typeof window.CatalogService.getCategories === 'function') {
                const res = await window.CatalogService.getCategories();
                if (res && res.success && Array.isArray(res.data)) list = res.data;
            } else {
                const res = await fetch('/api/categories');
                const data = await res.json();
                if (data && data.success && Array.isArray(data.data)) list = data.data;
            }

            this.categories = list;

            let html = `
                <label class="filter-radio-item ${!this.filters.category ? 'active' : ''}">
                    <input type="radio" name="categoryFilter" value="" ${!this.filters.category ? 'checked' : ''}>
                    <span>Tất cả danh mục</span>
                </label>
            `;

            list.forEach(cat => {
                const isChecked = this.filters.category === cat.slug;
                html += `
                    <label class="filter-radio-item ${isChecked ? 'active' : ''}">
                        <input type="radio" name="categoryFilter" value="${this.escapeHtml(cat.slug)}" ${isChecked ? 'checked' : ''}>
                        <span>${this.escapeHtml(cat.name)}</span>
                    </label>
                `;
            });

            container.innerHTML = html;
            this.updateBreadcrumb();
        } catch (err) {
            console.error('Lỗi khi tải danh mục catalog:', err);
        }
    },

    // 4. Lấy dữ liệu sản phẩm từ API
    async fetchProducts() {
        const grid = document.getElementById('productsGrid');
        const emptyState = document.getElementById('emptyState');
        const countEl = document.getElementById('resultsCount');
        const paginationBar = document.getElementById('paginationBar');

        if (!grid) return;

        this.showSkeletonLoading();
        this.renderActiveFilterChips();
        this.updateUrl();

        try {
            const apiParams = {
                page: this.filters.page,
                page_size: this.filters.page_size,
                sort: this.filters.sort
            };

            if (this.filters.q) apiParams.q = this.filters.q;
            if (this.filters.category) apiParams.category = this.filters.category;
            if (this.filters.brand) apiParams.brand = this.filters.brand;
            if (this.filters.min_price) apiParams.min_price = this.filters.min_price;
            if (this.filters.max_price) apiParams.max_price = this.filters.max_price;
            if (this.filters.on_sale === '1') apiParams.on_sale = 1;

            let result = null;
            if (window.CatalogService && typeof window.CatalogService.getProducts === 'function') {
                result = await window.CatalogService.getProducts(apiParams);
            } else {
                const queryStr = new URLSearchParams(apiParams).toString();
                const res = await fetch(`/api/products?${queryStr}`);
                result = await res.json();
            }

            const products = (result && result.success && Array.isArray(result.data)) ? result.data : [];
            const meta = (result && result.meta) ? result.meta : { total: products.length, page: 1, total_pages: 1 };

            if (countEl) {
                countEl.textContent = `Tìm thấy ${meta.total} sản phẩm`;
            }

            if (!products.length) {
                grid.style.display = 'none';
                if (emptyState) emptyState.style.display = 'block';
                if (paginationBar) paginationBar.innerHTML = '';
            } else {
                grid.style.display = 'grid';
                if (emptyState) emptyState.style.display = 'none';
                this.renderProductCards(products);
                this.renderPagination(meta);
            }
        } catch (err) {
            console.error('Lỗi khi lấy danh sách sản phẩm:', err);
            if (countEl) countEl.textContent = 'Lỗi khi tải sản phẩm';
            grid.innerHTML = '<p class="text-muted" style="grid-column: 1/-1; text-align: center; padding: 40px 0;">Đã xảy ra lỗi khi tải danh sách sản phẩm.</p>';
        }
    },

    // 5. Render thẻ sản phẩm
    renderProductCards(products) {
        const grid = document.getElementById('productsGrid');
        if (!grid) return;

        grid.innerHTML = products.map(p => {
            const hasSale = Boolean(p.has_sale && p.min_original_price && p.min_original_price > p.min_price);
            const discount = hasSale ? this.calcDiscount(p.min_original_price, p.min_price) : 0;
            const inStock = Boolean(p.in_stock);

            return `
                <a href="/products/${encodeURIComponent(p.slug)}" class="product-card">
                    ${hasSale && discount > 0 ? `<span class="discount-badge">-${discount}%</span>` : ''}
                    ${!inStock ? `<span class="stock-badge out-of-stock">Hết hàng</span>` : ''}
                    <div class="product-img">
                        ${p.primary_image ? `
                            <img src="${this.escapeHtml(p.primary_image)}" 
                                 alt="${this.escapeHtml(p.name)}" 
                                 loading="lazy" 
                                 onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\\'fa-solid fa-image\\' style=\\'font-size:42px;color:#cbd5e1;\\'></i>';">
                        ` : `
                            <i class="fa-solid fa-image" style="font-size:42px;color:#cbd5e1;"></i>
                        `}
                    </div>
                    <div class="product-info">
                        ${p.brand ? `<div class="product-brand">${this.escapeHtml(p.brand)}</div>` : ''}
                        <div class="product-name" title="${this.escapeHtml(p.name)}">${this.escapeHtml(p.name)}</div>
                        <div class="product-price">
                            <span class="price-sale">${this.formatPrice(p.min_price)}</span>
                            ${hasSale ? `<span class="price-original">${this.formatPrice(p.min_original_price)}</span>` : ''}
                        </div>
                    </div>
                </a>
            `;
        }).join('');
    },

    // 6. Render phân trang
    renderPagination(meta) {
        const bar = document.getElementById('paginationBar');
        if (!bar) return;

        const totalPages = meta.total_pages || Math.ceil((meta.total || 0) / this.filters.page_size) || 1;
        const currentPage = meta.page || this.filters.page || 1;

        if (totalPages <= 1) {
            bar.innerHTML = '';
            return;
        }

        let html = '';

        // Nút Trước
        html += `
            <button type="button" class="pagination-btn" ${currentPage <= 1 ? 'disabled' : ''} data-page="${currentPage - 1}">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
        `;

        // Tạo danh sách trang thông minh
        const pages = [];
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                pages.push(i);
            } else if (pages[pages.length - 1] !== '...') {
                pages.push('...');
            }
        }

        pages.forEach(p => {
            if (p === '...') {
                html += `<span class="pagination-dots">...</span>`;
            } else {
                html += `
                    <button type="button" class="pagination-btn ${p === currentPage ? 'active' : ''}" data-page="${p}">
                        ${p}
                    </button>
                `;
            }
        });

        // Nút Sau
        html += `
            <button type="button" class="pagination-btn" ${currentPage >= totalPages ? 'disabled' : ''} data-page="${currentPage + 1}">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        `;

        bar.innerHTML = html;

        // Gắn sự kiện click trang
        bar.querySelectorAll('.pagination-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const targetPage = parseInt(btn.getAttribute('data-page'), 10);
                if (targetPage && targetPage !== currentPage) {
                    this.filters.page = targetPage;
                    this.fetchProducts();
                    window.scrollTo({ top: 150, behavior: 'smooth' });
                }
            });
        });
    },

    // 7. Render Active Filter Chips
    renderActiveFilterChips() {
        const bar = document.getElementById('activeFiltersBar');
        const list = document.getElementById('activeChipsList');
        if (!bar || !list) return;

        const chips = [];

        if (this.filters.q) {
            chips.push({
                type: 'q',
                label: `Từ khóa: "${this.filters.q}"`
            });
        }

        if (this.filters.category) {
            const catObj = this.categories.find(c => c.slug === this.filters.category);
            chips.push({
                type: 'category',
                label: `Danh mục: ${catObj ? catObj.name : this.filters.category}`
            });
        }

        if (this.filters.brand) {
            chips.push({
                type: 'brand',
                label: `Thương hiệu: ${this.filters.brand}`
            });
        }

        if (this.filters.min_price || this.filters.max_price) {
            let priceLabel = 'Giá: ';
            if (this.filters.min_price && this.filters.max_price) {
                priceLabel += `${this.formatPrice(this.filters.min_price)} - ${this.formatPrice(this.filters.max_price)}`;
            } else if (this.filters.min_price) {
                priceLabel += `Từ ${this.formatPrice(this.filters.min_price)}`;
            } else {
                priceLabel += `Dưới ${this.formatPrice(this.filters.max_price)}`;
            }
            chips.push({ type: 'price', label: priceLabel });
        }

        if (this.filters.on_sale === '1') {
            chips.push({ type: 'on_sale', label: 'Đang giảm giá' });
        }

        if (!chips.length) {
            bar.style.display = 'none';
            list.innerHTML = '';
            return;
        }

        bar.style.display = 'flex';
        list.innerHTML = chips.map(c => `
            <span class="active-chip">
                ${this.escapeHtml(c.label)}
                <i class="fa-solid fa-xmark active-chip-remove" data-type="${c.type}"></i>
            </span>
        `).join('');

        // Bắt sự kiện xóa từng chip
        list.querySelectorAll('.active-chip-remove').forEach(icon => {
            icon.addEventListener('click', () => {
                const type = icon.getAttribute('data-type');
                this.removeFilter(type);
            });
        });
    },

    removeFilter(type) {
        if (type === 'q') {
            this.filters.q = '';
            const el = document.getElementById('filterKeyword');
            if (el) el.value = '';
        } else if (type === 'category') {
            this.filters.category = '';
            const radio = document.querySelector('input[name="categoryFilter"][value=""]');
            if (radio) radio.checked = true;
            this.updateBreadcrumb();
        } else if (type === 'brand') {
            this.filters.brand = '';
            this.updateBrandChipsUI();
        } else if (type === 'price') {
            this.filters.min_price = '';
            this.filters.max_price = '';
            const minEl = document.getElementById('minPriceInput');
            const maxEl = document.getElementById('maxPriceInput');
            if (minEl) minEl.value = '';
            if (maxEl) maxEl.value = '';
            this.updateQuickPriceUI();
        } else if (type === 'on_sale') {
            this.filters.on_sale = '';
            const el = document.getElementById('filterOnSale');
            if (el) el.checked = false;
        }

        this.filters.page = 1;
        this.fetchProducts();
    },

    resetAllFilters() {
        this.filters.q = '';
        this.filters.category = '';
        this.filters.brand = '';
        this.filters.min_price = '';
        this.filters.max_price = '';
        this.filters.on_sale = '';
        this.filters.sort = 'newest';
        this.filters.page = 1;

        // Reset UI
        const keywordInput = document.getElementById('filterKeyword');
        if (keywordInput) keywordInput.value = '';

        const minInput = document.getElementById('minPriceInput');
        if (minInput) minInput.value = '';

        const maxInput = document.getElementById('maxPriceInput');
        if (maxInput) maxInput.value = '';

        const onSaleCheckbox = document.getElementById('filterOnSale');
        if (onSaleCheckbox) onSaleCheckbox.checked = false;

        const sortSelect = document.getElementById('sortSelect');
        if (sortSelect) sortSelect.value = 'newest';

        const defaultRadio = document.querySelector('input[name="categoryFilter"][value=""]');
        if (defaultRadio) defaultRadio.checked = true;

        this.updateBrandChipsUI();
        this.updateQuickPriceUI();
        this.updateBreadcrumb();

        this.fetchProducts();
    },

    // 8. Lắng nghe các sự kiện
    bindEvents() {
        // Tìm kiếm từ khóa
        const keywordInput = document.getElementById('filterKeyword');
        const searchBtn = document.getElementById('btnSearchSubmit');

        if (searchBtn && keywordInput) {
            searchBtn.addEventListener('click', () => {
                this.filters.q = keywordInput.value.trim();
                this.filters.page = 1;
                this.fetchProducts();
            });

            keywordInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.filters.q = keywordInput.value.trim();
                    this.filters.page = 1;
                    this.fetchProducts();
                }
            });
        }

        // Chọn danh mục
        const catContainer = document.getElementById('categoryFilterList');
        if (catContainer) {
            catContainer.addEventListener('change', (e) => {
                if (e.target && e.target.name === 'categoryFilter') {
                    this.filters.category = e.target.value;
                    this.filters.page = 1;
                    this.updateBreadcrumb();
                    this.fetchProducts();
                }
            });
        }

        // Chọn thương hiệu
        const brandContainer = document.getElementById('brandFilterList');
        if (brandContainer) {
            brandContainer.addEventListener('click', (e) => {
                const chip = e.target.closest('.filter-chip');
                if (chip) {
                    this.filters.brand = chip.getAttribute('data-brand') || '';
                    this.filters.page = 1;
                    this.updateBrandChipsUI();
                    this.fetchProducts();
                }
            });
        }

        // Quick price pills
        document.querySelectorAll('.price-pill').forEach(pill => {
            pill.addEventListener('click', () => {
                const min = pill.getAttribute('data-min');
                const max = pill.getAttribute('data-max');

                this.filters.min_price = min;
                this.filters.max_price = max;

                const minInput = document.getElementById('minPriceInput');
                const maxInput = document.getElementById('maxPriceInput');
                if (minInput) minInput.value = min;
                if (maxInput) maxInput.value = max;

                this.updateQuickPriceUI();
                this.filters.page = 1;
                this.fetchProducts();
            });
        });

        // Áp dụng giá tùy chỉnh
        const applyPriceBtn = document.getElementById('applyPriceBtn');
        if (applyPriceBtn) {
            applyPriceBtn.addEventListener('click', () => {
                const minInput = document.getElementById('minPriceInput');
                const maxInput = document.getElementById('maxPriceInput');

                this.filters.min_price = minInput ? minInput.value.trim() : '';
                this.filters.max_price = maxInput ? maxInput.value.trim() : '';
                this.filters.page = 1;

                this.updateQuickPriceUI();
                this.fetchProducts();
            });
        }

        // Checkbox hàng giảm giá
        const onSaleCheckbox = document.getElementById('filterOnSale');
        if (onSaleCheckbox) {
            onSaleCheckbox.addEventListener('change', (e) => {
                this.filters.on_sale = e.target.checked ? '1' : '';
                this.filters.page = 1;
                this.fetchProducts();
            });
        }

        // Sắp xếp
        const sortSelect = document.getElementById('sortSelect');
        if (sortSelect) {
            sortSelect.addEventListener('change', (e) => {
                this.filters.sort = e.target.value;
                this.filters.page = 1;
                this.fetchProducts();
            });
        }

        // Nút Xóa bộ lọc
        const clearBtn = document.getElementById('clearFiltersBtn');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => this.resetAllFilters());
        }

        const resetEmptyBtn = document.getElementById('btnResetEmptyFilters');
        if (resetEmptyBtn) {
            resetEmptyBtn.addEventListener('click', () => this.resetAllFilters());
        }

        // Toggle filter trên mobile
        const toggleMobileBtn = document.getElementById('toggleMobileFilterBtn');
        const sidebar = document.getElementById('catalogSidebar');
        if (toggleMobileBtn && sidebar) {
            toggleMobileBtn.addEventListener('click', () => {
                sidebar.classList.toggle('show-mobile');
            });
        }
    },

    // UI Helpers
    updateBrandChipsUI() {
        document.querySelectorAll('#brandFilterList .filter-chip').forEach(chip => {
            const b = chip.getAttribute('data-brand') || '';
            chip.classList.toggle('active', b === this.filters.brand);
        });
    },

    updateQuickPriceUI() {
        document.querySelectorAll('.price-pill').forEach(pill => {
            const min = pill.getAttribute('data-min') || '';
            const max = pill.getAttribute('data-max') || '';
            const isActive = (min === (this.filters.min_price || '') && max === (this.filters.max_price || ''));
            pill.classList.toggle('active', isActive);
        });
    },

    updateBreadcrumb() {
        const wrap = document.getElementById('breadcrumbCategory');
        const nameEl = document.getElementById('breadcrumbCategoryName');
        if (!wrap || !nameEl) return;

        if (this.filters.category) {
            const cat = this.categories.find(c => c.slug === this.filters.category);
            nameEl.textContent = cat ? cat.name : this.filters.category;
            wrap.style.display = 'inline';
        } else {
            wrap.style.display = 'none';
        }
    },

    showSkeletonLoading() {
        const grid = document.getElementById('productsGrid');
        if (!grid) return;

        let skeletons = '';
        for (let i = 0; i < 8; i++) {
            skeletons += `
                <div class="product-card skeleton-card" style="min-height: 310px;">
                    <div class="skeleton-icon" style="width: 100%; height: 160px; border-radius: 8px;"></div>
                    <div class="skeleton-text" style="width: 40%; height: 10px; margin-top: 12px;"></div>
                    <div class="skeleton-text" style="width: 85%; height: 14px; margin-top: 8px;"></div>
                    <div class="skeleton-text" style="width: 60%; height: 16px; margin-top: 14px;"></div>
                </div>
            `;
        }
        grid.innerHTML = skeletons;
    },

    formatPrice(amount) {
        if (window.Utils && typeof window.Utils.formatPrice === 'function') {
            return window.Utils.formatPrice(amount);
        }
        return new Intl.NumberFormat('vi-VN').format(amount || 0) + '₫';
    },

    calcDiscount(original, current) {
        if (window.Utils && typeof window.Utils.discountPercent === 'function') {
            return window.Utils.discountPercent(original, current);
        }
        if (!original || !current || original <= current) return 0;
        return Math.round(((original - current) / original) * 100);
    },

    escapeHtml(str) {
        if (window.Utils && typeof window.Utils.escapeHtml === 'function') {
            return window.Utils.escapeHtml(str);
        }
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
};
