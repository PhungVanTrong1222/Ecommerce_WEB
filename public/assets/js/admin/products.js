/**
 * Admin Products Management JavaScript
 */
document.addEventListener('DOMContentLoaded', function () {
    let currentPage = 1;
    let categoriesList = [];
    let currentDetailProduct = null;

    // Elements
    const searchInput = document.getElementById('productSearchInput');
    const categoryFilter = document.getElementById('productCategoryFilter');
    const statusFilter = document.getElementById('productStatusFilter');
    const btnFilter = document.getElementById('btnFilterProducts');
    const tableBody = document.getElementById('productTableBody');
    const paginationContainer = document.getElementById('paginationProducts');

    // Create Modal Elements
    const modalCreateProduct = document.getElementById('modalCreateProduct');
    const btnOpenCreateModal = document.getElementById('btnOpenCreateProductModal');
    const formCreateProduct = document.getElementById('formCreateProduct');
    const createProductCategory = document.getElementById('createProductCategory');
    const createProductSpecsContainer = document.getElementById('createProductSpecsContainer');
    const btnAddSpecRow = document.getElementById('btnAddSpecRow');
    const createVariantsTableBody = document.getElementById('createVariantsTableBody');
    const btnAddVariantRow = document.getElementById('btnAddVariantRow');

    // Edit Modal Elements
    const modalEditProduct = document.getElementById('modalEditProduct');
    const editProductId = document.getElementById('editProductId');
    const editProductCategory = document.getElementById('editProductCategory');
    const editProductBrand = document.getElementById('editProductBrand');
    const editProductName = document.getElementById('editProductName');
    const editProductStatus = document.getElementById('editProductStatus');
    const editProductDesc = document.getElementById('editProductDesc');
    const editProductSpecsContainer = document.getElementById('editProductSpecsContainer');
    const btnEditAddSpecRow = document.getElementById('btnEditAddSpecRow');
    const formUpdateGeneral = document.getElementById('formUpdateGeneralProduct');

    const editVariantsTableBody = document.getElementById('editVariantsTableBody');
    const editVariantsCount = document.getElementById('editVariantsCount');
    const btnToggleAddNewVariant = document.getElementById('btnToggleAddNewVariant');
    const cardAddNewVariant = document.getElementById('cardAddNewVariant');
    const formAddNewVariant = document.getElementById('formAddNewVariant');
    const btnCancelAddNewVariant = document.getElementById('btnCancelAddNewVariant');

    const editImagesCount = document.getElementById('editImagesCount');
    const productImagesGallery = document.getElementById('productImagesGallery');
    const formUploadProductImage = document.getElementById('formUploadProductImage');
    const uploadImageFile = document.getElementById('uploadImageFile');
    const uploadImageIsPrimary = document.getElementById('uploadImageIsPrimary');

    function showModal(modalEl) {
        if (!modalEl) return;
        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else {
            modalEl.classList.add('show');
            modalEl.style.display = 'block';
            document.body.classList.add('modal-open');
        }
    }

    function hideModal(modalEl) {
        if (!modalEl) return;
        if (window.bootstrap && window.bootstrap.Modal) {
            const instance = window.bootstrap.Modal.getInstance(modalEl);
            if (instance) {
                instance.hide();
                return;
            }
        }
        modalEl.classList.remove('show');
        modalEl.style.display = 'none';
        document.body.classList.remove('modal-open');
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    }

    // Modal close buttons
    document.getElementById('btnCloseCreateProductModal')?.addEventListener('click', () => hideModal(modalCreateProduct));
    document.getElementById('btnCancelCreateProductModal')?.addEventListener('click', () => hideModal(modalCreateProduct));
    document.getElementById('btnCloseEditProductModal')?.addEventListener('click', () => hideModal(modalEditProduct));
    document.getElementById('btnBottomCloseEditProduct')?.addEventListener('click', () => hideModal(modalEditProduct));

    // Tab switching support without jQuery dependency
    document.querySelectorAll('#editProductTabs .nav-link').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            document.querySelectorAll('#editProductTabs .nav-link').forEach(l => l.classList.remove('active'));
            document.querySelectorAll('#editProductTabContent .tab-pane').forEach(p => {
                p.classList.remove('show', 'active');
            });
            this.classList.add('active');
            const targetPane = document.querySelector(targetId);
            if (targetPane) targetPane.classList.add('show', 'active');
        });
    });

    // 1. Tải Categories ban đầu
    async function loadCategories() {
        try {
            const res = await ApiClient.get('/api/admin/categories');
            if (res && res.success && Array.isArray(res.data)) {
                categoriesList = res.data;
                const optionsHtml = '<option value="">-- Chọn danh mục --</option>' +
                    categoriesList.map(c => `<option value="${c.id}">${Utils.escapeHtml(c.name)}</option>`).join('');

                if (productCategoryFilter) {
                    productCategoryFilter.innerHTML = '<option value="">Tất cả danh mục</option>' +
                        categoriesList.map(c => `<option value="${c.id}">${Utils.escapeHtml(c.name)}</option>`).join('');
                }
                if (createProductCategory) createProductCategory.innerHTML = optionsHtml;
                if (editProductCategory) editProductCategory.innerHTML = optionsHtml;
            }
        } catch (e) {
            console.error('Không thể load categories:', e);
        }
    }

    // 2. Tải danh sách sản phẩm phân trang
    async function loadProducts(page = 1) {
        currentPage = page;
        if (tableBody) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <span class="ml-2">Đang tải sản phẩm...</span>
                    </td>
                </tr>`;
        }

        const params = new URLSearchParams({
            page: currentPage,
            page_size: 10
        });

        const q = (searchInput?.value || '').trim();
        if (q) params.append('q', q);

        const catId = categoryFilter?.value;
        if (catId) params.append('category_id', catId);

        const status = statusFilter?.value;
        if (status) params.append('status', status);

        try {
            const res = await ApiClient.get('/api/admin/products?' + params.toString());
            if (res && res.success) {
                renderProductsTable(res.data || []);
                renderPagination(res.meta || {});
            } else {
                tableBody.innerHTML = `
                    <tr><td colspan="7" class="text-center py-4 text-danger">Không thể tải dữ liệu sản phẩm.</td></tr>`;
            }
        } catch (error) {
            console.error('Lỗi khi tải products:', error);
            tableBody.innerHTML = `
                <tr><td colspan="7" class="text-center py-4 text-danger">Đã có lỗi xảy ra khi kết nối máy chủ.</td></tr>`;
        }
    }

    function renderProductsTable(products) {
        if (!tableBody) return;

        if (products.length === 0) {
            tableBody.innerHTML = `
                <tr><td colspan="7" class="text-center py-4 text-muted">Không có sản phẩm nào phù hợp.</td></tr>`;
            return;
        }

        tableBody.innerHTML = products.map(p => {
            const imgSrc = p.primary_image || '/assets/images/no-image.png';
            const isActive = p.status === 'Active';
            const statusBadge = isActive
                ? '<span class="badge badge-success px-2 py-1">Active</span>'
                : '<span class="badge badge-secondary px-2 py-1">Inactive</span>';
            const catName = p.category?.name || p.category_name || 'N/A';

            return `
                <tr>
                    <td class="text-center">
                        <img src="${Utils.escapeHtml(imgSrc)}" alt="${Utils.escapeHtml(p.name)}" 
                             class="rounded border" style="width: 50px; height: 50px; object-fit: cover;"
                             onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2250%22 height=%2250%22><rect width=%2250%22 height=%2250%22 fill=%22%23eee%22/><text x=%2250%%22 y=%2250%%22 fill=%22%23aaa%22 text-anchor=%22middle%22 dy=%22.3em%22 font-size=%2210%22>No Image</text></svg>'">
                    </td>
                    <td>
                        <div class="font-weight-bold text-dark font-size-15">${Utils.escapeHtml(p.name)}</div>
                        <small class="text-muted">Slug: ${Utils.escapeHtml(p.slug || '')}</small>
                    </td>
                    <td>
                        <div><span class="badge badge-light border text-dark">${Utils.escapeHtml(catName)}</span></div>
                        <small class="text-muted font-weight-500">${Utils.escapeHtml(p.brand || 'No brand')}</small>
                    </td>
                    <td class="text-center font-weight-bold">
                        ${p.variant_count ?? 0}
                    </td>
                    <td class="text-center font-weight-bold ${p.total_stock <= 5 ? 'text-danger' : 'text-dark'}">
                        ${p.total_stock ?? 0}
                    </td>
                    <td class="text-center">
                        ${statusBadge}
                    </td>
                    <td class="text-right">
                        <button class="btn btn-sm btn-primary btn-edit-product" data-id="${p.id}">
                            <i class="fas fa-edit mr-1"></i> Chi tiết
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        tableBody.querySelectorAll('.btn-edit-product').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                openProductDetailModal(id);
            });
        });
    }

    function renderPagination(meta) {
        if (!paginationContainer) return;
        const total = meta.total || 0;
        const totalPages = meta.total_pages || Math.ceil(total / (meta.page_size || 10)) || 1;
        const current = meta.page || 1;

        if (totalPages <= 1) {
            paginationContainer.innerHTML = `<span class="text-muted small">Hiển thị tổng số <strong>${total}</strong> sản phẩm</span>`;
            return;
        }

        let pagesHtml = '';
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= current - 2 && i <= current + 2)) {
                pagesHtml += `
                    <button class="btn btn-sm ${i === current ? 'btn-primary font-weight-bold' : 'btn-outline-secondary'} mx-1 btn-page" data-page="${i}">
                        ${i}
                    </button>
                `;
            } else if (i === current - 3 || i === current + 3) {
                pagesHtml += '<span class="mx-1 text-muted">...</span>';
            }
        }

        paginationContainer.innerHTML = `
            <span class="text-muted small">
                Hiển thị trang <strong>${current}</strong> / <strong>${totalPages}</strong> (Tổng <strong>${total}</strong> SP)
            </span>
            <div>
                <button class="btn btn-sm btn-outline-secondary mr-1 btn-page" data-page="${current - 1}" ${current <= 1 ? 'disabled' : ''}>
                    &laquo; Trước
                </button>
                ${pagesHtml}
                <button class="btn btn-sm btn-outline-secondary ml-1 btn-page" data-page="${current + 1}" ${current >= totalPages ? 'disabled' : ''}>
                    Sau &raquo;
                </button>
            </div>
        `;

        paginationContainer.querySelectorAll('.btn-page').forEach(btn => {
            btn.addEventListener('click', function () {
                const p = parseInt(this.getAttribute('data-page'));
                if (p >= 1 && p <= totalPages && p !== current) {
                    loadProducts(p);
                }
            });
        });
    }

    // ==========================================
    // 3. XỬ LÝ TẠO SẢN PHẨM MỚI (Modal 1)
    // ==========================================
    function addSpecRow(container, key = '', val = '') {
        const row = document.createElement('div');
        row.className = 'row spec-row mb-2 align-items-center';
        row.innerHTML = `
            <div class="col-5">
                <input type="text" class="form-control form-control-sm spec-key" placeholder="Tên thông số (RAM, Bộ nhớ, Chip...)" value="${Utils.escapeHtml(key)}">
            </div>
            <div class="col-6">
                <input type="text" class="form-control form-control-sm spec-val" placeholder="Giá trị (8GB, 256GB...)" value="${Utils.escapeHtml(val)}">
            </div>
            <div class="col-1 text-right">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-spec p-1" title="Xóa dòng">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        row.querySelector('.btn-remove-spec').addEventListener('click', () => row.remove());
        container.appendChild(row);
    }

    function addVariantRow(sku = '', name = '', price = '', salePrice = '', qty = 10) {
        if (!createVariantsTableBody) return;
        const tr = document.createElement('tr');
        tr.className = 'variant-input-row';
        tr.innerHTML = `
            <td>
                <input type="text" class="form-control form-control-sm var-sku" required placeholder="SKU-..." value="${Utils.escapeHtml(sku)}">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm var-name" required placeholder="Tên biến thể" value="${Utils.escapeHtml(name)}">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm var-price" required min="0" step="1000" placeholder="0" value="${price}">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm var-saleprice" min="0" step="1000" placeholder="0" value="${salePrice}">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm var-qty" min="0" value="${qty}">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger p-1 btn-del-var" title="Xóa">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        `;
        tr.querySelector('.btn-del-var').addEventListener('click', function () {
            if (createVariantsTableBody.querySelectorAll('tr').length <= 1) {
                Utils.showToast('Sản phẩm cần có ít nhất 1 biến thể!', 'warning');
                return;
            }
            tr.remove();
        });
        createVariantsTableBody.appendChild(tr);
    }

    btnOpenCreateModal?.addEventListener('click', function () {
        if (formCreateProduct) formCreateProduct.reset();
        if (createProductSpecsContainer) createProductSpecsContainer.innerHTML = '';
        if (createVariantsTableBody) createVariantsTableBody.innerHTML = '';
        addSpecRow(createProductSpecsContainer, 'Màn hình', '');
        addSpecRow(createProductSpecsContainer, 'RAM', '');
        addVariantRow('', 'Mặc định', '', '', 10);
        showModal(modalCreateProduct);
    });

    btnAddSpecRow?.addEventListener('click', () => addSpecRow(createProductSpecsContainer));
    btnAddVariantRow?.addEventListener('click', () => addVariantRow());

    // Submit Tạo Sản Phẩm Mới
    formCreateProduct?.addEventListener('submit', async function (e) {
        e.preventDefault();

        const categoryId = createProductCategory?.value;
        const name = (document.getElementById('createProductName')?.value || '').trim();
        const brand = (document.getElementById('createProductBrand')?.value || '').trim();
        const status = document.getElementById('createProductStatus')?.value || 'Active';
        const description = (document.getElementById('createProductDesc')?.value || '').trim();

        if (!categoryId || !name) {
            Utils.showToast('Vui lòng nhập danh mục và tên sản phẩm', 'warning');
            return;
        }

        // Thu thập specifications
        const specifications = {};
        createProductSpecsContainer.querySelectorAll('.spec-row').forEach(row => {
            const k = (row.querySelector('.spec-key')?.value || '').trim();
            const v = (row.querySelector('.spec-val')?.value || '').trim();
            if (k) specifications[k] = v;
        });

        // Thu thập variants
        const variants = [];
        const varRows = createVariantsTableBody.querySelectorAll('tr');
        if (varRows.length === 0) {
            Utils.showToast('Vui lòng thêm ít nhất 1 biến thể sản phẩm', 'warning');
            return;
        }

        for (const row of varRows) {
            const sku = (row.querySelector('.var-sku')?.value || '').trim();
            const varName = (row.querySelector('.var-name')?.value || '').trim();
            const price = parseFloat(row.querySelector('.var-price')?.value);
            const salePriceVal = row.querySelector('.var-saleprice')?.value;
            const salePrice = salePriceVal !== '' ? parseFloat(salePriceVal) : null;
            const initialQuantity = parseInt(row.querySelector('.var-qty')?.value) || 0;

            if (!sku || !varName || isNaN(price)) {
                Utils.showToast('Vui lòng điền đầy đủ SKU, Tên biến thể và Giá bán', 'warning');
                return;
            }

            variants.push({
                sku: sku,
                variant_name: varName,
                price: price,
                sale_price: salePrice,
                initial_quantity: initialQuantity
            });
        }

        const payload = {
            category_id: parseInt(categoryId),
            name: name,
            brand: brand || null,
            status: status,
            description: description || null,
            specifications: Object.keys(specifications).length > 0 ? specifications : null,
            variants: variants
        };

        const btnSubmit = document.getElementById('btnSubmitCreateProduct');
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span> Đang tạo...';

        try {
            const res = await ApiClient.post('/api/admin/products', payload);
            if (res && res.success) {
                Utils.showToast('Tạo sản phẩm mới thành công!', 'success');
                hideModal(modalCreateProduct);
                loadProducts(1);
            } else {
                Utils.showToast(res?.error?.message || 'Không thể tạo sản phẩm', 'error');
            }
        } catch (error) {
            Utils.showToast(error.message || 'Lỗi khi tạo sản phẩm', 'error');
        } finally {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="fas fa-save mr-1"></i> Tạo sản phẩm';
        }
    });

    // ==========================================
    // 4. CHI TIẾT & CHỈNH SỬA SẢN PHẨM (Modal 2)
    // ==========================================
    async function openProductDetailModal(productId) {
        try {
            const res = await ApiClient.get(`/api/admin/products/${productId}`);
            if (!res || !res.success) {
                Utils.showToast(res?.error?.message || 'Không thể lấy thông tin sản phẩm', 'error');
                return;
            }

            currentDetailProduct = res.data;
            const p = currentDetailProduct;

            document.getElementById('editProductModalTitle').innerHTML = `
                <i class="fas fa-box-open mr-1"></i> Chi tiết Sản phẩm #${p.id} - ${Utils.escapeHtml(p.name)}
            `;

            // Tab 1: General Info
            if (editProductId) editProductId.value = p.id;
            if (editProductCategory) editProductCategory.value = p.category_id;
            if (editProductBrand) editProductBrand.value = p.brand || '';
            if (editProductName) editProductName.value = p.name || '';
            if (editProductStatus) editProductStatus.value = p.status || 'Active';
            if (editProductDesc) editProductDesc.value = p.description || '';

            // Specs
            if (editProductSpecsContainer) {
                editProductSpecsContainer.innerHTML = '';
                const specs = p.specifications || {};
                const keys = Object.keys(specs);
                if (keys.length === 0) {
                    addSpecRow(editProductSpecsContainer, '', '');
                } else {
                    keys.forEach(k => addSpecRow(editProductSpecsContainer, k, specs[k]));
                }
            }

            // Tab 2: Variants
            renderEditVariantsTab(p.variants || []);

            // Tab 3: Images
            renderEditImagesTab(p.images || []);

            // Switch to Tab 1 by default
            document.getElementById('tabGeneralLink')?.click();

            showModal(modalEditProduct);
        } catch (error) {
            console.error(error);
            Utils.showToast('Lỗi khi tải chi tiết sản phẩm', 'error');
        }
    }

    btnEditAddSpecRow?.addEventListener('click', () => addSpecRow(editProductSpecsContainer));

    // Submit Tab 1: Cập nhật thông tin chung
    formUpdateGeneral?.addEventListener('submit', async function (e) {
        e.preventDefault();
        const id = editProductId?.value;
        if (!id) return;

        const specs = {};
        editProductSpecsContainer.querySelectorAll('.spec-row').forEach(row => {
            const k = (row.querySelector('.spec-key')?.value || '').trim();
            const v = (row.querySelector('.spec-val')?.value || '').trim();
            if (k) specs[k] = v;
        });

        const payload = {
            category_id: parseInt(editProductCategory?.value),
            name: (editProductName?.value || '').trim(),
            brand: (editProductBrand?.value || '').trim() || null,
            status: editProductStatus?.value || 'Active',
            description: (editProductDesc?.value || '').trim() || null,
            specifications: Object.keys(specs).length > 0 ? specs : null
        };

        const btnSubmit = document.getElementById('btnSubmitUpdateGeneral');
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span> Đang lưu...';

        try {
            const res = await ApiClient.patch(`/api/admin/products/${id}`, payload);
            if (res && res.success) {
                Utils.showToast('Cập nhật thông tin sản phẩm thành công!', 'success');
                loadProducts(currentPage);
            } else {
                Utils.showToast(res?.error?.message || 'Lỗi khi cập nhật sản phẩm', 'error');
            }
        } catch (error) {
            Utils.showToast(error.message || 'Lỗi cập nhật', 'error');
        } finally {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="fas fa-save mr-1"></i> Lưu thông tin chung';
        }
    });

    // Render Tab 2 Variants
    function renderEditVariantsTab(variants) {
        if (editVariantsCount) editVariantsCount.textContent = variants.length;
        if (!editVariantsTableBody) return;

        if (variants.length === 0) {
            editVariantsTableBody.innerHTML = `
                <tr><td colspan="7" class="text-center py-3 text-muted">Chưa có biến thể nào.</td></tr>`;
            return;
        }

        editVariantsTableBody.innerHTML = variants.map(v => `
            <tr>
                <td><code>${Utils.escapeHtml(v.sku)}</code></td>
                <td><strong>${Utils.escapeHtml(v.variant_name)}</strong></td>
                <td>${Utils.formatPrice(v.price)}</td>
                <td>${v.sale_price ? `<span class="text-danger font-weight-bold">${Utils.formatPrice(v.sale_price)}</span>` : '<span class="text-muted">-</span>'}</td>
                <td class="text-center font-weight-bold ${v.stock_quantity <= 5 ? 'text-danger' : ''}">${v.stock_quantity ?? 0}</td>
                <td class="text-center">
                    <span class="badge ${v.status === 'Active' ? 'badge-success' : 'badge-secondary'} px-2 py-1">
                        ${Utils.escapeHtml(v.status)}
                    </span>
                </td>
                <td class="text-right">
                    <button type="button" class="btn btn-sm btn-outline-primary btn-quick-edit-variant" data-var='${JSON.stringify(v)}'>
                        <i class="fas fa-edit"></i> Sửa
                    </button>
                </td>
            </tr>
        `).join('');

        editVariantsTableBody.querySelectorAll('.btn-quick-edit-variant').forEach(btn => {
            btn.addEventListener('click', function () {
                const variantData = JSON.parse(this.getAttribute('data-var'));
                editVariantPrompt(variantData);
            });
        });
    }

    async function editVariantPrompt(v) {
        const newName = prompt('Tên biến thể:', v.variant_name);
        if (newName === null) return;

        const newPrice = prompt('Giá bán (VND):', v.price);
        if (newPrice === null) return;

        const newSalePrice = prompt('Giá khuyến mãi (để trống nếu không có):', v.sale_price || '');
        if (newSalePrice === null) return;

        const newStatus = prompt('Trạng thái (Active hoặc Inactive):', v.status);
        if (newStatus === null) return;

        const payload = {
            sku: v.sku,
            variant_name: newName.trim(),
            price: parseFloat(newPrice),
            sale_price: newSalePrice.trim() !== '' ? parseFloat(newSalePrice) : null,
            status: newStatus.trim() === 'Inactive' ? 'Inactive' : 'Active'
        };

        try {
            const res = await ApiClient.patch(`/api/admin/variants/${v.id}`, payload);
            if (res && res.success) {
                Utils.showToast('Cập nhật biến thể thành công!', 'success');
                // Reload lại chi tiết product
                openProductDetailModal(editProductId.value);
                loadProducts(currentPage);
            } else {
                Utils.showToast(res?.error?.message || 'Không thể cập nhật biến thể', 'error');
            }
        } catch (e) {
            Utils.showToast(e.message || 'Lỗi khi sửa biến thể', 'error');
        }
    }

    // Toggle Form thêm biến thể mới
    btnToggleAddNewVariant?.addEventListener('click', () => {
        cardAddNewVariant?.classList.toggle('d-none');
    });
    btnCancelAddNewVariant?.addEventListener('click', () => {
        cardAddNewVariant?.classList.add('d-none');
    });

    formAddNewVariant?.addEventListener('submit', async function (e) {
        e.preventDefault();
        const pId = editProductId?.value;
        if (!pId) return;

        const sku = (document.getElementById('newVarSku')?.value || '').trim();
        const name = (document.getElementById('newVarName')?.value || '').trim();
        const price = parseFloat(document.getElementById('newVarPrice')?.value);
        const salePriceVal = document.getElementById('newVarSalePrice')?.value;
        const salePrice = salePriceVal !== '' ? parseFloat(salePriceVal) : null;
        const initialQuantity = parseInt(document.getElementById('newVarQty')?.value) || 0;

        const payload = {
            sku: sku,
            variant_name: name,
            price: price,
            sale_price: salePrice,
            initial_quantity: initialQuantity
        };

        try {
            const res = await ApiClient.post(`/api/admin/products/${pId}/variants`, payload);
            if (res && res.success) {
                Utils.showToast('Thêm biến thể mới thành công!', 'success');
                formAddNewVariant.reset();
                cardAddNewVariant.classList.add('d-none');
                openProductDetailModal(pId);
                loadProducts(currentPage);
            } else {
                Utils.showToast(res?.error?.message || 'Không thể thêm biến thể', 'error');
            }
        } catch (error) {
            Utils.showToast(error.message || 'Lỗi khi thêm biến thể', 'error');
        }
    });

    // Render Tab 3 Images
    function renderEditImagesTab(images) {
        if (editImagesCount) editImagesCount.textContent = images.length;
        if (!productImagesGallery) return;

        if (images.length === 0) {
            productImagesGallery.innerHTML = `
                <div class="col-12 text-center py-4 text-muted">
                    Sản phẩm chưa có hình ảnh nào.
                </div>`;
            return;
        }

        productImagesGallery.innerHTML = images.map(img => `
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card h-100 border shadow-sm">
                    <img src="${Utils.escapeHtml(img.image_path)}" class="card-img-top p-2" 
                         style="height: 180px; object-fit: contain;" alt="Product image">
                    <div class="card-body p-2 text-center border-top">
                        ${img.is_primary ? '<span class="badge badge-success mb-1">Ảnh chính</span>' : '<span class="badge badge-light border mb-1">Ảnh phụ</span>'}
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-image mt-1" data-id="${img.id}">
                                <i class="fas fa-trash-alt mr-1"></i> Xóa ảnh
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

        productImagesGallery.querySelectorAll('.btn-delete-image').forEach(btn => {
            btn.addEventListener('click', async function () {
                const imgId = this.getAttribute('data-id');
                if (!confirm('Bạn có chắc chắn muốn xóa hình ảnh này?')) return;

                try {
                    const res = await ApiClient.delete(`/api/admin/product-images/${imgId}`);
                    if (res && res.success) {
                        Utils.showToast('Đã xóa hình ảnh!', 'success');
                        openProductDetailModal(editProductId.value);
                        loadProducts(currentPage);
                    } else {
                        Utils.showToast(res?.error?.message || 'Không thể xóa ảnh', 'error');
                    }
                } catch (e) {
                    Utils.showToast(e.message || 'Lỗi khi xóa ảnh', 'error');
                }
            });
        });
    }

    // Submit upload ảnh mới
    formUploadProductImage?.addEventListener('submit', async function (e) {
        e.preventDefault();
        const pId = editProductId?.value;
        const file = uploadImageFile?.files?.[0];
        if (!pId || !file) {
            Utils.showToast('Vui lòng chọn file hình ảnh', 'warning');
            return;
        }

        const isPrimary = uploadImageIsPrimary?.checked ? 1 : 0;
        const formData = new FormData();
        formData.append('image', file);
        formData.append('is_primary', isPrimary);

        const btnSubmit = document.getElementById('btnSubmitUploadImage');
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span> Đang tải...';

        try {
            const res = await ApiClient.upload(`/api/admin/products/${pId}/images`, formData);
            if (res && res.success) {
                Utils.showToast('Tải ảnh lên thành công!', 'success');
                formUploadProductImage.reset();
                openProductDetailModal(pId);
                loadProducts(currentPage);
            } else {
                Utils.showToast(res?.error?.message || 'Không thể upload ảnh', 'error');
            }
        } catch (error) {
            Utils.showToast(error.message || 'Lỗi upload ảnh', 'error');
        } finally {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="fas fa-upload mr-1"></i> Tải ảnh lên';
        }
    });

    // Gán sự kiện lọc
    btnFilter?.addEventListener('click', () => loadProducts(1));
    searchInput?.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') loadProducts(1);
    });
    categoryFilter?.addEventListener('change', () => loadProducts(1));
    statusFilter?.addEventListener('change', () => loadProducts(1));

    // Khởi động
    loadCategories();
    loadProducts(1);
});
