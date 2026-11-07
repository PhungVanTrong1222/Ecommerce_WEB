/**
 * TechShop - Home Page Logic (Vanilla JS)
 * Module: Storefront
 */

document.addEventListener('DOMContentLoaded', function () {
    initHeroSlider();
    loadCategories();
    loadFlashSaleProducts();
    loadNewProducts();
    loadPremiumProducts();
});

// Fallback helpers nếu Utils chưa có
const formatCurrency = (amount) => {
    if (window.Utils && typeof window.Utils.formatPrice === 'function') {
        return window.Utils.formatPrice(amount);
    }
    return new Intl.NumberFormat('vi-VN').format(amount || 0) + '₫';
};

const calcDiscount = (original, current) => {
    if (window.Utils && typeof window.Utils.discountPercent === 'function') {
        return window.Utils.discountPercent(original, current);
    }
    if (!original || !current || original <= current) return 0;
    return Math.round(((original - current) / original) * 100);
};

const escapeHtmlText = (str) => {
    if (window.Utils && typeof window.Utils.escapeHtml === 'function') {
        return window.Utils.escapeHtml(str);
    }
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
};

/**
 * 1. Khởi tạo Banner Slider tự động
 */
function initHeroSlider() {
    const slidesContainer = document.getElementById('heroSlides');
    const dots = document.querySelectorAll('#sliderDots .dot');
    const prevBtn = document.getElementById('sliderPrevBtn');
    const nextBtn = document.getElementById('sliderNextBtn');

    if (!slidesContainer) return;

    let currentIndex = 0;
    const totalSlides = slidesContainer.children.length;
    let autoSlideInterval = null;

    function goToSlide(index) {
        currentIndex = (index + totalSlides) % totalSlides;
        slidesContainer.style.transform = `translateX(-${currentIndex * 100}%)`;

        dots.forEach((dot, idx) => {
            dot.classList.toggle('active', idx === currentIndex);
        });
    }

    function startAutoSlide() {
        stopAutoSlide();
        autoSlideInterval = setInterval(() => {
            goToSlide(currentIndex + 1);
        }, 4000);
    }

    function stopAutoSlide() {
        if (autoSlideInterval) {
            clearInterval(autoSlideInterval);
            autoSlideInterval = null;
        }
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            goToSlide(currentIndex - 1);
            startAutoSlide();
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            goToSlide(currentIndex + 1);
            startAutoSlide();
        });
    }

    dots.forEach((dot, idx) => {
        dot.addEventListener('click', () => {
            goToSlide(idx);
            startAutoSlide();
        });
    });

    // Tạm dừng khi hover chuột vào slider
    const sliderEl = document.getElementById('heroSlider');
    if (sliderEl) {
        sliderEl.addEventListener('mouseenter', stopAutoSlide);
        sliderEl.addEventListener('mouseleave', startAutoSlide);
    }

    startAutoSlide();
}

/**
 * 2. Nạp danh mục từ Catalog API
 */
async function loadCategories() {
    const grid = document.getElementById('categoryGrid');
    if (!grid) return;

    const iconMap = {
        'dien-thoai': 'fa-mobile-screen',
        'laptop': 'fa-laptop',
        'may-tinh-bang': 'fa-tablet-screen-button',
        'am-thanh': 'fa-headphones',
        'phu-kien': 'fa-keyboard',
        'dong-ho-thong-minh': 'fa-clock',
    };

    try {
        let categories = [];
        if (window.CatalogService && typeof window.CatalogService.getCategories === 'function') {
            const res = await window.CatalogService.getCategories();
            if (res && res.success && Array.isArray(res.data)) {
                categories = res.data;
            }
        } else {
            const res = await fetch('/api/categories');
            const data = await res.json();
            if (data && data.success && Array.isArray(data.data)) {
                categories = data.data;
            }
        }

        if (!categories.length) {
            grid.innerHTML = '<p class="text-muted" style="grid-column: 1/-1; text-align: center;">Chưa có danh mục nào.</p>';
            return;
        }

        grid.innerHTML = categories.map(cat => {
            const iconClass = iconMap[cat.slug] || 'fa-tags';
            return `
                <a href="/products?category=${encodeURIComponent(cat.slug)}" class="category-card">
                    <div class="category-icon">
                        <i class="fa-solid ${iconClass}"></i>
                    </div>
                    <span class="category-name">${escapeHtmlText(cat.name)}</span>
                </a>
            `;
        }).join('');
    } catch (err) {
        console.error('Lỗi khi tải danh mục:', err);
        grid.innerHTML = '<p class="text-muted" style="grid-column: 1/-1; text-align: center;">Không thể tải danh mục sản phẩm.</p>';
    }
}

/**
 * 3. Nạp danh sách Giảm giá sốc (on_sale = 1)
 */
async function loadFlashSaleProducts() {
    const grid = document.getElementById('flashSaleGrid');
    if (!grid) return;

    showLoadingSkeleton(grid, 4);

    try {
        let products = [];
        const params = { on_sale: 1, page_size: 8 };

        if (window.CatalogService && typeof window.CatalogService.getProducts === 'function') {
            const res = await window.CatalogService.getProducts(params);
            if (res && res.success && Array.isArray(res.data)) {
                products = res.data;
            }
        } else {
            const res = await fetch('/api/products?on_sale=1&page_size=8');
            const data = await res.json();
            if (data && data.success && Array.isArray(data.data)) {
                products = data.data;
            }
        }

        renderProducts(grid, products, 'Hiện chưa có sản phẩm giảm giá sốc nào.');
    } catch (err) {
        console.error('Lỗi khi tải sản phẩm giảm giá sốc:', err);
        grid.innerHTML = '<p class="text-muted" style="grid-column: 1/-1; text-align: center;">Không thể tải sản phẩm.</p>';
    }
}

/**
 * 4. Nạp danh sách Sản phẩm mới nhất (sort = newest)
 */
async function loadNewProducts() {
    const grid = document.getElementById('newProductsGrid');
    if (!grid) return;

    showLoadingSkeleton(grid, 4);

    try {
        let products = [];
        const params = { sort: 'newest', page_size: 8 };

        if (window.CatalogService && typeof window.CatalogService.getProducts === 'function') {
            const res = await window.CatalogService.getProducts(params);
            if (res && res.success && Array.isArray(res.data)) {
                products = res.data;
            }
        } else {
            const res = await fetch('/api/products?sort=newest&page_size=8');
            const data = await res.json();
            if (data && data.success && Array.isArray(data.data)) {
                products = data.data;
            }
        }

        renderProducts(grid, products, 'Chưa có sản phẩm mới nào.');
    } catch (err) {
        console.error('Lỗi khi tải sản phẩm mới:', err);
        grid.innerHTML = '<p class="text-muted" style="grid-column: 1/-1; text-align: center;">Không thể tải sản phẩm.</p>';
    }
}

/**
 * 5. Nạp danh sách Sản phẩm cao cấp nổi bật (sort = price_desc)
 */
async function loadPremiumProducts() {
    const grid = document.getElementById('premiumProductsGrid');
    if (!grid) return;

    showLoadingSkeleton(grid, 4);

    try {
        let products = [];
        const params = { sort: 'price_desc', page_size: 8 };

        if (window.CatalogService && typeof window.CatalogService.getProducts === 'function') {
            const res = await window.CatalogService.getProducts(params);
            if (res && res.success && Array.isArray(res.data)) {
                products = res.data;
            }
        } else {
            const res = await fetch('/api/products?sort=price_desc&page_size=8');
            const data = await res.json();
            if (data && data.success && Array.isArray(data.data)) {
                products = data.data;
            }
        }

        renderProducts(grid, products, 'Chưa có sản phẩm nổi bật.');
    } catch (err) {
        console.error('Lỗi khi tải sản phẩm cao cấp:', err);
        grid.innerHTML = '<p class="text-muted" style="grid-column: 1/-1; text-align: center;">Không thể tải sản phẩm.</p>';
    }
}

/**
 * Helper: Hiển thị skeleton loading
 */
function showLoadingSkeleton(container, count = 4) {
    let skeletons = '';
    for (let i = 0; i < count; i++) {
        skeletons += `
            <div class="product-card skeleton-card" style="min-height: 310px;">
                <div class="skeleton-icon" style="width: 100%; height: 160px; border-radius: 8px;"></div>
                <div class="skeleton-text" style="width: 40%; height: 10px; margin-top: 12px;"></div>
                <div class="skeleton-text" style="width: 85%; height: 14px; margin-top: 8px;"></div>
                <div class="skeleton-text" style="width: 60%; height: 16px; margin-top: 14px;"></div>
            </div>
        `;
    }
    container.innerHTML = skeletons;
}

/**
 * Helper: Render danh sách card sản phẩm chuẩn HTML
 */
function renderProducts(container, products, emptyMsg = 'Không tìm thấy sản phẩm nào.') {
    if (!products || !products.length) {
        container.innerHTML = `<p class="text-muted" style="grid-column: 1/-1; text-align: center; padding: 24px 0;">${escapeHtmlText(emptyMsg)}</p>`;
        return;
    }

    container.innerHTML = products.map(p => {
        const hasSale = Boolean(p.has_sale && p.min_original_price && p.min_original_price > p.min_price);
        const discount = hasSale ? calcDiscount(p.min_original_price, p.min_price) : 0;
        const inStock = Boolean(p.in_stock);

        return `
            <a href="/products/${encodeURIComponent(p.slug)}" class="product-card">
                ${hasSale && discount > 0 ? `<span class="discount-badge">-${discount}%</span>` : ''}
                ${!inStock ? `<span class="stock-badge out-of-stock">Hết hàng</span>` : ''}
                <div class="product-img">
                    ${p.primary_image ? `
                        <img src="${escapeHtmlText(p.primary_image)}" 
                             alt="${escapeHtmlText(p.name)}" 
                             loading="lazy" 
                             onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\\'fa-solid fa-image\\' style=\\'font-size:42px;color:#cbd5e1;\\'></i>';">
                    ` : `
                        <i class="fa-solid fa-image" style="font-size:42px;color:#cbd5e1;"></i>
                    `}
                </div>
                <div class="product-info">
                    ${p.brand ? `<div class="product-brand">${escapeHtmlText(p.brand)}</div>` : ''}
                    <div class="product-name" title="${escapeHtmlText(p.name)}">${escapeHtmlText(p.name)}</div>
                    <div class="product-price">
                        <span class="price-sale">${formatCurrency(p.min_price)}</span>
                        ${hasSale ? `<span class="price-original">${formatCurrency(p.min_original_price)}</span>` : ''}
                    </div>
                </div>
            </a>
        `;
    }).join('');
}
