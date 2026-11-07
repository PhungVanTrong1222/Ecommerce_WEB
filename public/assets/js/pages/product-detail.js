/**
 * TechShop - Product Detail Page Logic (Vanilla JS)
 * Module: Storefront
 */

document.addEventListener('DOMContentLoaded', function () {
    ProductDetailApp.init();
});

const ProductDetailApp = {
    slug: '',
    product: null,
    selectedVariant: null,
    allImages: [],

    init() {
        const pageEl = document.getElementById('productDetailPage');
        if (!pageEl) return;

        // Lấy slug từ data attribute hoặc pathname
        this.slug = pageEl.getAttribute('data-slug');
        if (!this.slug) {
            const parts = window.location.pathname.split('/').filter(Boolean);
            this.slug = parts[parts.length - 1] || '';
        }

        if (!this.slug) {
            this.showError();
            return;
        }

        this.bindEvents();
        this.loadProductDetail();
    },

    // 1. Tải chi tiết sản phẩm từ API
    async loadProductDetail() {
        const loadingEl = document.getElementById('detailLoadingState');
        const containerEl = document.getElementById('detailContainer');
        const errorEl = document.getElementById('detailErrorState');

        try {
            let res = null;
            if (window.CatalogService && typeof window.CatalogService.getProductDetail === 'function') {
                res = await window.CatalogService.getProductDetail(this.slug);
            } else {
                const response = await fetch(`/api/products/${encodeURIComponent(this.slug)}`);
                res = await response.json();
            }

            if (!res || !res.success || !res.data) {
                this.showError();
                return;
            }

            this.product = res.data;

            if (loadingEl) loadingEl.style.display = 'none';
            if (errorEl) errorEl.style.display = 'none';
            if (containerEl) containerEl.style.display = 'block';

            this.renderProductInfo();
            this.setupGallery();
            this.setupVariants();
            this.renderSpecifications();
            this.loadRelatedProducts();
        } catch (err) {
            console.error('Lỗi khi tải chi tiết sản phẩm:', err);
            this.showError();
        }
    },

    showError() {
        const loadingEl = document.getElementById('detailLoadingState');
        const containerEl = document.getElementById('detailContainer');
        const errorEl = document.getElementById('detailErrorState');

        if (loadingEl) loadingEl.style.display = 'none';
        if (containerEl) containerEl.style.display = 'none';
        if (errorEl) errorEl.style.display = 'block';
    },

    // 2. Điền thông tin cơ bản
    renderProductInfo() {
        const p = this.product;

        document.title = `${p.name} - TechShop`;

        // Breadcrumb
        const bcCategoryLink = document.getElementById('bcCategoryLink');
        const bcProductName = document.getElementById('bcProductName');
        if (bcCategoryLink && p.category) {
            bcCategoryLink.textContent = p.category.name;
            bcCategoryLink.href = `/products?category=${encodeURIComponent(p.category.slug)}`;
        }
        if (bcProductName) {
            bcProductName.textContent = p.name;
        }

        // Title & Brand
        const titleEl = document.getElementById('productTitle');
        const brandEl = document.getElementById('productBrand');
        if (titleEl) titleEl.textContent = p.name;
        if (brandEl) {
            if (p.brand) {
                brandEl.textContent = p.brand;
                brandEl.style.display = 'inline-block';
            } else {
                brandEl.style.display = 'none';
            }
        }

        // Description
        const descEl = document.getElementById('productDescription');
        if (descEl) {
            descEl.textContent = p.description || 'Chưa có thông tin mô tả chi tiết cho sản phẩm này.';
        }
    },

    // 3. Cài đặt Thư viện ảnh (Gallery)
    setupGallery() {
        const mainImg = document.getElementById('mainProductImage');
        const track = document.getElementById('thumbnailsTrack');
        if (!mainImg || !track) return;

        // Tập hợp tất cả ảnh (product images + variant images)
        const imagesList = [];

        if (Array.isArray(this.product.images) && this.product.images.length > 0) {
            this.product.images.forEach(img => {
                imagesList.push({
                    url: img.url || img.image_path,
                    is_primary: Boolean(img.is_primary)
                });
            });
        }

        if (Array.isArray(this.product.variants)) {
            this.product.variants.forEach(v => {
                if (Array.isArray(v.images)) {
                    v.images.forEach(vi => {
                        const url = vi.url || vi.image_path;
                        if (!imagesList.some(item => item.url === url)) {
                            imagesList.push({ url, is_primary: false });
                        }
                    });
                }
            });
        }

        // Fallback nếu không có ảnh nào
        if (imagesList.length === 0) {
            imagesList.push({
                url: '/assets/images/placeholder.png',
                is_primary: true
            });
        }

        this.allImages = imagesList;

        // Chọn ảnh primary hoặc ảnh đầu tiên
        const primaryImg = imagesList.find(img => img.is_primary) || imagesList[0];
        mainImg.src = primaryImg.url;
        mainImg.onerror = () => {
            mainImg.src = 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800&q=80';
        };

        // Render thumbnails
        if (imagesList.length <= 1) {
            track.style.display = 'none';
            return;
        }

        track.style.display = 'flex';
        track.innerHTML = imagesList.map((img, idx) => `
            <div class="thumb-item ${img.url === primaryImg.url ? 'active' : ''}" data-index="${idx}">
                <img src="${this.escapeHtml(img.url)}" alt="Thumbnail" onerror="this.src='/assets/images/placeholder.png'">
            </div>
        `).join('');

        track.querySelectorAll('.thumb-item').forEach(thumb => {
            thumb.addEventListener('click', () => {
                const idx = parseInt(thumb.getAttribute('data-index'), 10);
                this.setActiveImage(imagesList[idx].url);
            });
        });
    },

    setActiveImage(url) {
        const mainImg = document.getElementById('mainProductImage');
        const track = document.getElementById('thumbnailsTrack');
        if (mainImg) mainImg.src = url;

        if (track) {
            track.querySelectorAll('.thumb-item').forEach(t => {
                const imgInside = t.querySelector('img');
                const isActive = imgInside && imgInside.getAttribute('src') === url;
                t.classList.toggle('active', isActive);
            });
        }
    },

    // 4. Cài đặt danh sách biến thể (Variants)
    setupVariants() {
        const container = document.getElementById('variantSelector');
        const section = document.getElementById('variantSection');
        if (!container) return;

        const variants = this.product.variants || [];
        if (!variants.length) {
            if (section) section.style.display = 'none';
            return;
        }

        container.innerHTML = variants.map(v => {
            const isOutOfStock = (v.quantity <= 0);
            return `
                <button type="button" 
                        class="variant-pill ${isOutOfStock ? 'disabled' : ''}" 
                        data-id="${v.id}"
                        title="${isOutOfStock ? 'Hết hàng' : 'Còn hàng'}">
                    <span>${this.escapeHtml(v.variant_name)}</span>
                </button>
            `;
        }).join('');

        // Tự động chọn biến thể đầu tiên còn hàng, nếu hết hàng cả thì chọn cái đầu tiên
        let defaultVariant = variants.find(v => v.quantity > 0) || variants[0];
        this.selectVariant(defaultVariant);

        // Bắt sự kiện click pill
        container.querySelectorAll('.variant-pill').forEach(pill => {
            pill.addEventListener('click', () => {
                const vId = pill.getAttribute('data-id');
                const target = variants.find(v => String(v.id) === String(vId));
                if (target) {
                    this.selectVariant(target);
                }
            });
        });
    },

    // 5. Chọn một biến thể cụ thể
    selectVariant(v) {
        this.selectedVariant = v;

        // Cập nhật class active trên pills
        document.querySelectorAll('#variantSelector .variant-pill').forEach(pill => {
            const isCurrent = (pill.getAttribute('data-id') === String(v.id));
            pill.classList.toggle('active', isCurrent);
        });

        // Tên variant
        const nameEl = document.getElementById('selectedVariantName');
        if (nameEl) nameEl.textContent = v.variant_name;

        // SKU
        const skuEl = document.getElementById('selectedSku');
        if (skuEl) skuEl.textContent = v.sku;

        // Giá
        const priceEl = document.getElementById('selectedPrice');
        const originalPriceEl = document.getElementById('selectedOriginalPrice');
        const discountTagEl = document.getElementById('selectedDiscountTag');
        const mainBadgeEl = document.getElementById('mainDiscountBadge');

        const effectivePrice = v.effective_price || (v.sale_price ? v.sale_price : v.price);
        const hasSale = Boolean(v.sale_price && v.sale_price < v.price);
        const discountPercent = hasSale ? this.calcDiscount(v.price, v.sale_price) : 0;

        if (priceEl) priceEl.textContent = this.formatPrice(effectivePrice);

        if (hasSale && discountPercent > 0) {
            if (originalPriceEl) {
                originalPriceEl.textContent = this.formatPrice(v.price);
                originalPriceEl.style.display = 'inline-block';
            }
            if (discountTagEl) {
                discountTagEl.textContent = `Tiết kiệm ${discountPercent}%`;
                discountTagEl.style.display = 'inline-block';
            }
            if (mainBadgeEl) {
                mainBadgeEl.textContent = `-${discountPercent}%`;
                mainBadgeEl.style.display = 'block';
            }
        } else {
            if (originalPriceEl) originalPriceEl.style.display = 'none';
            if (discountTagEl) discountTagEl.style.display = 'none';
            if (mainBadgeEl) mainBadgeEl.style.display = 'none';
        }

        // Tồn kho & Buttons
        const stockEl = document.getElementById('selectedStock');
        const qtyInput = document.getElementById('detailQty');
        const addBtn = document.getElementById('btnAddToCart');
        const buyBtn = document.getElementById('btnBuyNow');

        const isAvailable = (v.quantity > 0);

        if (stockEl) {
            if (isAvailable) {
                stockEl.textContent = `Còn ${v.quantity} sản phẩm`;
                stockEl.className = 'stock-status in-stock';
            } else {
                stockEl.textContent = 'Hết hàng';
                stockEl.className = 'stock-status out-of-stock';
            }
        }

        if (qtyInput) {
            qtyInput.max = isAvailable ? v.quantity : 1;
            const currentVal = parseInt(qtyInput.value, 10) || 1;
            if (currentVal > v.quantity) {
                qtyInput.value = isAvailable ? 1 : 0;
            } else if (currentVal < 1 && isAvailable) {
                qtyInput.value = 1;
            }
            qtyInput.disabled = !isAvailable;
        }

        if (addBtn && buyBtn) {
            if (isAvailable) {
                addBtn.disabled = false;
                buyBtn.disabled = false;
                addBtn.innerHTML = '<i class="fa-solid fa-cart-plus"></i> Thêm vào giỏ hàng';
                buyBtn.innerHTML = '<i class="fa-solid fa-bolt"></i> Mua ngay';
            } else {
                addBtn.disabled = true;
                buyBtn.disabled = true;
                addBtn.innerHTML = 'Tạm hết hàng';
                buyBtn.innerHTML = 'Tạm hết hàng';
            }
        }

        // Nếu biến thể có ảnh riêng, chuyển ảnh chính sang ảnh đó
        if (Array.isArray(v.images) && v.images.length > 0) {
            const vImgUrl = v.images[0].url || v.images[0].image_path;
            if (vImgUrl) {
                this.setActiveImage(vImgUrl);
            }
        }
    },

    // 6. Render Bảng thông số kỹ thuật (Specifications)
    renderSpecifications() {
        const tbody = document.getElementById('specsTableBody');
        const table = document.getElementById('specsTable');
        const notice = document.getElementById('noSpecsNotice');
        if (!tbody) return;

        let specs = this.product.specifications;

        if (typeof specs === 'string') {
            try {
                specs = JSON.parse(specs);
            } catch (e) {
                specs = null;
            }
        }

        if (!specs || typeof specs !== 'object' || Object.keys(specs).length === 0) {
            if (table) table.style.display = 'none';
            if (notice) notice.style.display = 'block';
            return;
        }

        if (table) table.style.display = 'table';
        if (notice) notice.style.display = 'none';

        tbody.innerHTML = Object.entries(specs).map(([key, val]) => `
            <tr>
                <th>${this.escapeHtml(key)}</th>
                <td>${this.escapeHtml(typeof val === 'object' ? JSON.stringify(val) : String(val))}</td>
            </tr>
        `).join('');
    },

    // 7. Tải sản phẩm liên quan (cùng danh mục)
    async loadRelatedProducts() {
        const grid = document.getElementById('relatedProductsGrid');
        const section = document.getElementById('relatedSection');
        if (!grid || !this.product.category) return;

        try {
            let list = [];
            const params = {
                category: this.product.category.slug,
                page_size: 5
            };

            if (window.CatalogService && typeof window.CatalogService.getProducts === 'function') {
                const res = await window.CatalogService.getProducts(params);
                if (res && res.success && Array.isArray(res.data)) list = res.data;
            } else {
                const query = new URLSearchParams(params).toString();
                const res = await fetch(`/api/products?${query}`);
                const data = await res.json();
                if (data && data.success && Array.isArray(data.data)) list = data.data;
            }

            // Loại trừ sản phẩm hiện tại
            const filtered = list.filter(p => String(p.id) !== String(this.product.id)).slice(0, 4);

            if (!filtered.length) {
                if (section) section.style.display = 'none';
                return;
            }

            if (section) section.style.display = 'block';

            grid.innerHTML = filtered.map(p => {
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
        } catch (err) {
            console.error('Lỗi khi tải sản phẩm liên quan:', err);
            if (section) section.style.display = 'none';
        }
    },

    // 8. Gắn sự kiện (Quantity, Add to Cart, Buy Now)
    bindEvents() {
        const minusBtn = document.getElementById('btnQtyMinus');
        const plusBtn = document.getElementById('btnQtyPlus');
        const qtyInput = document.getElementById('detailQty');
        const addBtn = document.getElementById('btnAddToCart');
        const buyBtn = document.getElementById('btnBuyNow');

        if (minusBtn && qtyInput) {
            minusBtn.addEventListener('click', () => {
                let current = parseInt(qtyInput.value, 10) || 1;
                if (current > 1) {
                    qtyInput.value = current - 1;
                }
            });
        }

        if (plusBtn && qtyInput) {
            plusBtn.addEventListener('click', () => {
                let current = parseInt(qtyInput.value, 10) || 1;
                const max = parseInt(qtyInput.max, 10) || 999;
                if (current < max) {
                    qtyInput.value = current + 1;
                }
            });
        }

        if (qtyInput) {
            qtyInput.addEventListener('change', () => {
                let current = parseInt(qtyInput.value, 10) || 1;
                const max = parseInt(qtyInput.max, 10) || 999;
                if (current < 1) qtyInput.value = 1;
                else if (current > max) qtyInput.value = max;
            });
        }

        // Thêm vào giỏ hàng
        if (addBtn) {
            addBtn.addEventListener('click', () => this.handleAddToCart(false));
        }

        // Mua ngay
        if (buyBtn) {
            buyBtn.addEventListener('click', () => this.handleAddToCart(true));
        }
    },

    // 9. Xử lý Thêm vào giỏ hàng & Mua ngay
    async handleAddToCart(redirectCheckout = false) {
        if (!this.selectedVariant) {
            this.showToast('Vui lòng chọn phiên bản sản phẩm', 'warning');
            return;
        }

        if (this.selectedVariant.quantity <= 0) {
            this.showToast('Sản phẩm phiên bản này hiện đã hết hàng', 'warning');
            return;
        }

        const qtyInput = document.getElementById('detailQty');
        const quantity = parseInt(qtyInput ? qtyInput.value : 1, 10) || 1;

        const addBtn = document.getElementById('btnAddToCart');
        const buyBtn = document.getElementById('btnBuyNow');

        try {
            if (addBtn) addBtn.disabled = true;
            if (buyBtn) buyBtn.disabled = true;

            let success = false;

            if (window.CartService && typeof window.CartService.addItem === 'function') {
                const res = await window.CartService.addItem(this.selectedVariant.id, quantity);
                success = Boolean(res && (res.success || res.data));
            } else {
                const res = await fetch('/api/cart/items', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-XSRF-TOKEN': this.getCsrfToken()
                    },
                    body: JSON.stringify({
                        variant_id: this.selectedVariant.id,
                        quantity: quantity
                    })
                });
                const data = await res.json();
                success = Boolean(data && data.success);
            }

            if (success) {
                if (redirectCheckout) {
                    window.location.href = '/checkout';
                } else {
                    this.showToast('Đã thêm sản phẩm vào giỏ hàng thành công!', 'success');
                    if (window.AppAuth && typeof window.AppAuth.refreshCartBadge === 'function') {
                        window.AppAuth.refreshCartBadge();
                    }
                }
            } else {
                this.showToast('Không thể thêm sản phẩm vào giỏ hàng', 'error');
            }
        } catch (err) {
            console.error('Lỗi khi thêm vào giỏ hàng:', err);
            this.showToast('Đã có lỗi xảy ra khi thêm vào giỏ hàng', 'error');
        } finally {
            if (addBtn && this.selectedVariant.quantity > 0) addBtn.disabled = false;
            if (buyBtn && this.selectedVariant.quantity > 0) buyBtn.disabled = false;
        }
    },

    // Helpers
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
    },

    showToast(msg, type = 'success') {
        if (window.Utils && typeof window.Utils.showToast === 'function') {
            window.Utils.showToast(msg, type);
            return;
        }
        alert(msg);
    },

    getCsrfToken() {
        const match = document.cookie.match(new RegExp('(^|;\\s*)(XSRF-TOKEN)=([^;]*)'));
        return match ? decodeURIComponent(match[3]) : '';
    }
};
