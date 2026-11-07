/**
 * Admin Categories Management JavaScript
 */
document.addEventListener('DOMContentLoaded', function () {
    let categoriesList = [];
    const tableBody = document.getElementById('categoryTableBody');
    const searchInput = document.getElementById('categorySearchInput');
    const statusFilter = document.getElementById('categoryStatusFilter');
    const btnReload = document.getElementById('btnReloadCategories');
    const btnOpenCreate = document.getElementById('btnOpenCreateModal');

    const categoryModal = document.getElementById('categoryModal');
    const categoryForm = document.getElementById('categoryForm');
    const categoryModalTitle = document.getElementById('categoryModalTitle');
    const categoryIdInput = document.getElementById('categoryId');
    const categoryNameInput = document.getElementById('categoryName');
    const categoryDescInput = document.getElementById('categoryDescription');
    const categoryStatusInput = document.getElementById('categoryStatus');
    const btnSaveCategory = document.getElementById('btnSaveCategory');

    function openModal() {
        if (!categoryModal) return;
        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(categoryModal).show();
        } else {
            categoryModal.classList.add('show');
            categoryModal.style.display = 'block';
            document.body.classList.add('modal-open');
        }
    }

    function closeModal() {
        if (!categoryModal) return;
        if (window.bootstrap && window.bootstrap.Modal) {
            const instance = window.bootstrap.Modal.getInstance(categoryModal);
            if (instance) {
                instance.hide();
                return;
            }
        }
        categoryModal.classList.remove('show');
        categoryModal.style.display = 'none';
        document.body.classList.remove('modal-open');
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    }

    document.getElementById('btnCloseCategoryModal')?.addEventListener('click', closeModal);
    document.getElementById('btnCancelCategoryModal')?.addEventListener('click', closeModal);

    async function loadCategories() {
        if (tableBody) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <span class="ml-2">Đang tải danh mục...</span>
                    </td>
                </tr>`;
        }

        try {
            const res = await ApiClient.get('/api/admin/categories');
            if (res && res.success && Array.isArray(res.data)) {
                categoriesList = res.data;
                renderCategories();
            } else {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-4 text-danger">
                            Không thể tải danh sách danh mục.
                        </td>
                    </tr>`;
            }
        } catch (error) {
            console.error('Lỗi khi tải categories:', error);
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4 text-danger">
                        Đã có lỗi xảy ra khi kết nối máy chủ.
                    </td>
                </tr>`;
        }
    }

    function renderCategories() {
        if (!tableBody) return;

        const keyword = (searchInput?.value || '').trim().toLowerCase();
        const statusVal = statusFilter?.value || '';

        const filtered = categoriesList.filter(cat => {
            const matchName = (cat.name || '').toLowerCase().includes(keyword) || (cat.slug || '').toLowerCase().includes(keyword);
            const matchStatus = !statusVal || cat.status === statusVal;
            return matchName && matchStatus;
        });

        if (filtered.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        Không tìm thấy danh mục nào phù hợp.
                    </td>
                </tr>`;
            return;
        }

        tableBody.innerHTML = filtered.map(cat => {
            const isActive = cat.status === 'Active';
            const statusBadge = isActive
                ? '<span class="badge bg-success px-2 py-1"><i class="fas fa-check-circle me-1"></i>Active</span>'
                : '<span class="badge bg-secondary px-2 py-1"><i class="fas fa-eye-slash me-1"></i>Inactive</span>';

            const toggleBtnText = isActive ? 'Ẩn' : 'Hiện';
            const toggleBtnClass = isActive ? 'btn-outline-warning' : 'btn-outline-success';

            return `
                <tr>
                    <td><span class="text-muted fw-bold">#${Utils.escapeHtml(cat.id)}</span></td>
                    <td>
                        <strong class="text-dark fs-6">${Utils.escapeHtml(cat.name)}</strong>
                    </td>
                    <td><code class="text-muted">${Utils.escapeHtml(cat.slug || '')}</code></td>
                    <td class="text-muted small" style="max-width: 250px;">
                        ${Utils.escapeHtml(cat.description || 'Chưa có mô tả')}
                    </td>
                    <td class="text-center fw-bold">
                        ${cat.product_count ?? 0}
                    </td>
                    <td class="text-center">
                        ${statusBadge}
                    </td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary me-1 btn-edit-category" data-id="${cat.id}">
                            <i class="fas fa-edit me-1"></i> Sửa
                        </button>
                        <button class="btn btn-sm ${toggleBtnClass} btn-toggle-category" data-id="${cat.id}" data-current="${cat.status}">
                            ${toggleBtnText}
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        // Gán sự kiện cho các nút Sửa & Toggle
        tableBody.querySelectorAll('.btn-edit-category').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const cat = categoriesList.find(c => String(c.id) === String(id));
                if (cat) {
                    openEditModal(cat);
                }
            });
        });

        tableBody.querySelectorAll('.btn-toggle-category').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const currentStatus = this.getAttribute('data-current');
                const newStatus = currentStatus === 'Active' ? 'Inactive' : 'Active';
                toggleCategoryStatus(id, newStatus);
            });
        });
    }

    function openCreateModal() {
        if (categoryModalTitle) categoryModalTitle.textContent = 'Thêm danh mục mới';
        if (categoryIdInput) categoryIdInput.value = '';
        if (categoryNameInput) categoryNameInput.value = '';
        if (categoryDescInput) categoryDescInput.value = '';
        if (categoryStatusInput) categoryStatusInput.value = 'Active';
        openModal();
    }

    function openEditModal(cat) {
        if (categoryModalTitle) categoryModalTitle.textContent = 'Chỉnh sửa danh mục #' + cat.id;
        if (categoryIdInput) categoryIdInput.value = cat.id;
        if (categoryNameInput) categoryNameInput.value = cat.name || '';
        if (categoryDescInput) categoryDescInput.value = cat.description || '';
        if (categoryStatusInput) categoryStatusInput.value = cat.status || 'Active';
        openModal();
    }

    async function toggleCategoryStatus(id, newStatus) {
        const cat = categoriesList.find(c => String(c.id) === String(id));
        const actionText = newStatus === 'Active' ? 'kích hoạt hiển thị' : 'tạm ẩn';
        if (!confirm(`Bạn có chắc chắn muốn ${actionText} danh mục "${cat?.name || id}"?`)) {
            return;
        }

        try {
            const res = await ApiClient.patch(`/api/admin/categories/${id}`, {
                status: newStatus
            });
            if (res && res.success) {
                Utils.showToast(`Đã ${actionText} danh mục thành công`, 'success');
                loadCategories();
            } else {
                Utils.showToast(res?.error?.message || 'Không thể cập nhật trạng thái', 'error');
            }
        } catch (error) {
            Utils.showToast(error.message || 'Lỗi khi cập nhật trạng thái', 'error');
        }
    }

    if (categoryForm) {
        categoryForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const id = categoryIdInput?.value;
            const name = (categoryNameInput?.value || '').trim();
            const description = (categoryDescInput?.value || '').trim();
            const status = categoryStatusInput?.value || 'Active';

            if (!name) {
                Utils.showToast('Vui lòng nhập tên danh mục', 'warning');
                return;
            }

            const payload = {
                name: name,
                description: description || null,
                status: status
            };

            btnSaveCategory.disabled = true;
            btnSaveCategory.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span> Đang lưu...';

            try {
                let res;
                if (id) {
                    // Update
                    res = await ApiClient.patch(`/api/admin/categories/${id}`, payload);
                } else {
                    // Create
                    res = await ApiClient.post('/api/admin/categories', payload);
                }

                if (res && res.success) {
                    Utils.showToast(id ? 'Cập nhật danh mục thành công' : 'Tạo danh mục mới thành công', 'success');
                    closeModal();
                    loadCategories();
                } else {
                    Utils.showToast(res?.error?.message || 'Không thể lưu danh mục', 'error');
                }
            } catch (error) {
                Utils.showToast(error.message || 'Lỗi hệ thống khi lưu danh mục', 'error');
            } finally {
                btnSaveCategory.disabled = false;
                btnSaveCategory.innerHTML = '<i class="fas fa-save mr-1"></i> Lưu thông tin';
            }
        });
    }

    searchInput?.addEventListener('input', renderCategories);
    statusFilter?.addEventListener('change', renderCategories);
    btnReload?.addEventListener('click', loadCategories);
    btnOpenCreate?.addEventListener('click', openCreateModal);

    loadCategories();
});
