/**
 * Admin Inventory Management JavaScript
 */
document.addEventListener('DOMContentLoaded', function () {
    let currentPage = 1;
    let currentSelectedVariant = null;

    const searchInput = document.getElementById('invSearchInput');
    const lowStockCheckbox = document.getElementById('invLowStockCheckbox');
    const btnFilter = document.getElementById('btnFilterInventory');
    const btnReload = document.getElementById('btnReloadInventory');
    const tableBody = document.getElementById('inventoryTableBody');
    const paginationContainer = document.getElementById('paginationInventory');

    const modalAdjust = document.getElementById('modalAdjustInventory');
    const formAdjust = document.getElementById('formAdjustInventory');
    const adjustVariantId = document.getElementById('adjustVariantId');
    const adjustProductName = document.getElementById('adjustProductName');
    const adjustVariantName = document.getElementById('adjustVariantName');
    const adjustSku = document.getElementById('adjustSku');
    const adjustCurrentQty = document.getElementById('adjustCurrentQty');
    const adjustAmount = document.getElementById('adjustAmount');
    const adjustExpectedQty = document.getElementById('adjustExpectedQty');
    const adjustReason = document.getElementById('adjustReason');
    const btnSubmitAdjust = document.getElementById('btnSubmitAdjust');

    // Kiểm tra query param từ URL (ví dụ được redirect từ Dashboard sang: /admin/inventory?low_stock=1)
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('low_stock') === '1' && lowStockCheckbox) {
        lowStockCheckbox.checked = true;
    }

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

    document.getElementById('btnCloseAdjustModal')?.addEventListener('click', () => hideModal(modalAdjust));
    document.getElementById('btnCancelAdjustModal')?.addEventListener('click', () => hideModal(modalAdjust));

    async function loadInventory(page = 1) {
        currentPage = page;
        if (tableBody) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <span class="ml-2">Đang tải dữ liệu tồn kho...</span>
                    </td>
                </tr>`;
        }

        const params = new URLSearchParams({
            page: currentPage,
            page_size: 15
        });

        const q = (searchInput?.value || '').trim();
        if (q) params.append('q', q);

        if (lowStockCheckbox?.checked) {
            params.append('low_stock', '1');
        }

        try {
            const res = await ApiClient.get('/api/admin/inventory?' + params.toString());
            if (res && res.success) {
                renderInventoryTable(res.data || []);
                renderPagination(res.meta || {});
            } else {
                tableBody.innerHTML = `
                    <tr><td colspan="7" class="text-center py-4 text-danger">Không thể tải dữ liệu tồn kho.</td></tr>`;
            }
        } catch (error) {
            console.error('Lỗi khi tải inventory:', error);
            tableBody.innerHTML = `
                <tr><td colspan="7" class="text-center py-4 text-danger">Đã có lỗi xảy ra khi kết nối máy chủ.</td></tr>`;
        }
    }

    function renderInventoryTable(items) {
        if (!tableBody) return;

        if (items.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        Không tìm thấy biến thể nào phù hợp với bộ lọc.
                    </td>
                </tr>`;
            return;
        }

        tableBody.innerHTML = items.map(item => {
            const isLowStock = item.quantity <= 5;
            const qtyBadge = isLowStock
                ? `<span class="badge bg-danger px-3 py-1 fs-6 fw-bold"><i class="fas fa-exclamation-triangle me-1"></i>${item.quantity}</span>`
                : `<span class="badge bg-success px-3 py-1 fs-6 fw-bold">${item.quantity}</span>`;

            return `
                <tr class="${isLowStock ? 'table-danger' : ''}">
                    <td>
                        <code class="text-dark fw-bold">${Utils.escapeHtml(item.sku)}</code>
                    </td>
                    <td>
                        <strong class="text-dark fs-6">${Utils.escapeHtml(item.product_name)}</strong>
                    </td>
                    <td>
                        <span class="text-muted fw-medium">${Utils.escapeHtml(item.variant_name || 'Mặc định')}</span>
                    </td>
                    <td class="text-end fw-bold text-dark">
                        ${item.sale_price ? `<span class="text-danger">${Utils.formatPrice(item.sale_price)}</span> <br><small class="text-muted text-decoration-line-through">${Utils.formatPrice(item.price)}</small>` : Utils.formatPrice(item.price)}
                    </td>
                    <td class="text-center">
                        ${qtyBadge}
                    </td>
                    <td class="text-muted small">
                        ${Utils.formatDate(item.updated_at)}
                    </td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-primary btn-adjust-stock" data-item='${JSON.stringify(item)}'>
                            <i class="fas fa-sliders-h me-1"></i> Điều chỉnh
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        tableBody.querySelectorAll('.btn-adjust-stock').forEach(btn => {
            btn.addEventListener('click', function () {
                const itemData = JSON.parse(this.getAttribute('data-item'));
                openAdjustModal(itemData);
            });
        });
    }

    function renderPagination(meta) {
        if (!paginationContainer) return;
        const total = meta.total || 0;
        const totalPages = meta.total_pages || Math.ceil(total / (meta.page_size || 15)) || 1;
        const current = meta.page || 1;

        if (totalPages <= 1) {
            paginationContainer.innerHTML = `<span class="text-muted small">Hiển thị tổng số <strong>${total}</strong> biến thể</span>`;
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
                Hiển thị trang <strong>${current}</strong> / <strong>${totalPages}</strong> (Tổng <strong>${total}</strong> biến thể)
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
                    loadInventory(p);
                }
            });
        });
    }

    function openAdjustModal(item) {
        currentSelectedVariant = item;

        if (adjustVariantId) adjustVariantId.value = item.variant_id;
        if (adjustProductName) adjustProductName.textContent = item.product_name;
        if (adjustVariantName) adjustVariantName.textContent = item.variant_name || 'Mặc định';
        if (adjustSku) adjustSku.textContent = item.sku;
        if (adjustCurrentQty) {
            adjustCurrentQty.textContent = item.quantity;
            adjustCurrentQty.className = 'badge ms-1 px-2 py-1 fs-6 ' + (item.quantity <= 5 ? 'bg-danger' : 'bg-success');
        }

        document.getElementById('adjustTypeAdd').checked = true;
        if (adjustAmount) adjustAmount.value = 1;
        if (adjustReason) adjustReason.value = '';

        calculateExpectedQty();
        showModal(modalAdjust);
    }

    function calculateExpectedQty() {
        if (!currentSelectedVariant) return;
        const currentQty = parseInt(currentSelectedVariant.quantity) || 0;
        const amount = parseInt(adjustAmount?.value) || 0;
        const isAdd = document.getElementById('adjustTypeAdd')?.checked;

        const expected = isAdd ? (currentQty + amount) : (currentQty - amount);
        if (adjustExpectedQty) {
            adjustExpectedQty.textContent = expected;
            if (expected < 0) {
                adjustExpectedQty.className = 'h5 mb-0 text-danger font-weight-bold';
            } else {
                adjustExpectedQty.className = 'h5 mb-0 text-primary font-weight-bold';
            }
        }
    }

    document.querySelectorAll('input[name="adjustType"]').forEach(r => {
        r.addEventListener('change', calculateExpectedQty);
    });
    adjustAmount?.addEventListener('input', calculateExpectedQty);

    // Submit Điều chỉnh Tồn kho
    formAdjust?.addEventListener('submit', async function (e) {
        e.preventDefault();
        const variantId = adjustVariantId?.value;
        const amount = parseInt(adjustAmount?.value);
        const reason = (adjustReason?.value || '').trim();
        const isAdd = document.getElementById('adjustTypeAdd')?.checked;

        if (!variantId || isNaN(amount) || amount <= 0) {
            Utils.showToast('Vui lòng nhập số lượng điều chỉnh hợp lệ (> 0)', 'warning');
            return;
        }

        if (!reason) {
            Utils.showToast('Vui lòng nhập lý do điều chỉnh kho', 'warning');
            return;
        }

        const delta = isAdd ? amount : -amount;

        // Kiểm tra nếu giảm quá số lượng hiện tại
        const currentQty = parseInt(currentSelectedVariant?.quantity) || 0;
        if (!isAdd && amount > currentQty) {
            if (!confirm(`Số lượng giảm (${amount}) lớn hơn tồn kho hiện tại (${currentQty}). Hành động này sẽ bị hệ thống từ chối. Bạn vẫn muốn thử?`)) {
                return;
            }
        }

        btnSubmitAdjust.disabled = true;
        btnSubmitAdjust.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span> Đang xử lý...';

        try {
            const res = await ApiClient.post(`/api/admin/inventory/${variantId}/adjust`, {
                delta: delta,
                reason: reason
            });

            if (res && res.success) {
                Utils.showToast('Điều chỉnh tồn kho thành công!', 'success');
                hideModal(modalAdjust);
                loadInventory(currentPage);
            } else {
                Utils.showToast(res?.error?.message || 'Không thể điều chỉnh tồn kho', 'error');
            }
        } catch (error) {
            Utils.showToast(error.message || 'Lỗi khi điều chỉnh tồn kho', 'error');
        } finally {
            btnSubmitAdjust.disabled = false;
            btnSubmitAdjust.innerHTML = '<i class="fas fa-check mr-1"></i> Xác nhận điều chỉnh';
        }
    });

    btnFilter?.addEventListener('click', () => loadInventory(1));
    btnReload?.addEventListener('click', () => loadInventory(currentPage));
    searchInput?.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') loadInventory(1);
    });
    lowStockCheckbox?.addEventListener('change', () => loadInventory(1));

    loadInventory(1);
});
