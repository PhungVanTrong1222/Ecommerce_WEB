/**
 * TechShop - Authentication Page JavaScript (Login & Register)
 * Quản lý đăng nhập, đăng ký, toggle mật khẩu, điền demo accounts
 */

document.addEventListener('DOMContentLoaded', function () {
    const escapeHtml = (s) => {
        if (window.Utils && typeof window.Utils.escapeHtml === 'function') {
            return window.Utils.escapeHtml(s);
        }
        return String(s || '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    };

    const showToast = (msg, type = 'info') => {
        if (window.Utils && typeof window.Utils.showToast === 'function') {
            window.Utils.showToast(msg, type);
        } else {
            alert(msg);
        }
    };

    const api = {
        async post(url, body) {
            if (window.ApiClient && typeof window.ApiClient.post === 'function') {
                return window.ApiClient.post(url, body);
            }
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                credentials: 'same-origin',
                body: JSON.stringify(body)
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                const err = new Error(data?.error?.message || 'Yêu cầu không thành công.');
                err.status = res.status;
                err.code = data?.error?.code;
                err.details = data?.error?.details;
                throw err;
            }
            return data;
        }
    };

    const urlParams = new URLSearchParams(window.location.search);
    const redirectUrl = urlParams.get('redirect') || null;

    // Toggle Ẩn/Hiện mật khẩu
    document.querySelectorAll('.btn-toggle-pwd').forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (!input) return;

            const icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                if (icon) {
                    icon.classList.remove('fa-eye', 'bi-eye');
                    icon.classList.add('fa-eye-slash', 'bi-eye-slash');
                }
            } else {
                input.type = 'password';
                if (icon) {
                    icon.classList.remove('fa-eye-slash', 'bi-eye-slash');
                    icon.classList.add('fa-eye', 'bi-eye');
                }
            }
        });
    });

    // Điền tài khoản demo
    document.querySelectorAll('.btn-fill-demo').forEach(btn => {
        btn.addEventListener('click', function () {
            const emailInput = document.getElementById('login-email');
            const pwdInput = document.getElementById('login-password');
            if (emailInput && pwdInput) {
                emailInput.value = this.getAttribute('data-email');
                pwdInput.value = this.getAttribute('data-pwd');
                showToast('Đã điền tài khoản mẫu. Nhấn Đăng nhập để tiếp tục.', 'info');
            }
        });
    });

    // 1. Xử lý Form Đăng nhập
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        const btnSubmit = document.getElementById('btn-login-submit');
        const alertEl = document.getElementById('auth-alert');

        loginForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            alertEl.classList.add('d-none');
            alertEl.textContent = '';

            const emailInput = document.getElementById('login-email');
            const pwdInput = document.getElementById('login-password');

            let isValid = true;
            emailInput.classList.remove('is-invalid');
            pwdInput.classList.remove('is-invalid');

            const email = emailInput.value.trim();
            const password = pwdInput.value;

            if (!email) {
                emailInput.classList.add('is-invalid');
                isValid = false;
            }
            if (!password) {
                pwdInput.classList.add('is-invalid');
                isValid = false;
            }

            if (!isValid) return;

            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Đang đăng nhập...';

            try {
                const res = await api.post('/api/auth/login', { email, password });
                const user = res?.data || res;

                showToast('Đăng nhập thành công!', 'success');

                // Chuyển hướng
                setTimeout(() => {
                    if (redirectUrl) {
                        window.location.href = redirectUrl;
                    } else if (user && user.role === 'admin') {
                        window.location.href = '/admin';
                    } else {
                        window.location.href = '/';
                    }
                }, 500);
            } catch (error) {
                console.error('Lỗi đăng nhập:', error);
                const msg = error?.message || error?.error?.message || 'Email hoặc mật khẩu không chính xác.';
                alertEl.textContent = msg;
                alertEl.classList.remove('d-none');
                btnSubmit.disabled = false;
                btnSubmit.textContent = 'Đăng nhập';
            }
        });
    }

    // 2. Xử lý Form Đăng ký
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        const btnSubmit = document.getElementById('btn-register-submit');
        const alertEl = document.getElementById('auth-alert');

        registerForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            alertEl.classList.add('d-none');
            alertEl.textContent = '';

            const fullnameInput = document.getElementById('reg-fullname');
            const emailInput = document.getElementById('reg-email');
            const phoneInput = document.getElementById('reg-phone');
            const pwdInput = document.getElementById('reg-password');
            const confirmInput = document.getElementById('reg-password-confirmation');

            // Reset validation
            [fullnameInput, emailInput, phoneInput, pwdInput, confirmInput].forEach(el => {
                if (el) el.classList.remove('is-invalid');
            });

            const fullname = fullnameInput.value.trim();
            const email = emailInput.value.trim();
            const phone = phoneInput ? phoneInput.value.trim() : '';
            const password = pwdInput.value;
            const passwordConfirmation = confirmInput.value;

            let isValid = true;
            if (!fullname) {
                fullnameInput.classList.add('is-invalid');
                isValid = false;
            }
            if (!email) {
                emailInput.classList.add('is-invalid');
                isValid = false;
            }
            if (!password || password.length < 8) {
                pwdInput.classList.add('is-invalid');
                isValid = false;
            }
            if (password !== passwordConfirmation) {
                confirmInput.classList.add('is-invalid');
                isValid = false;
            }

            if (!isValid) return;

            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Đang tạo tài khoản...';

            try {
                const payload = {
                    fullname,
                    email,
                    phone: phone || null,
                    password,
                    password_confirmation: passwordConfirmation
                };

                const res = await api.post('/api/auth/register', payload);

                showToast('Đăng ký tài khoản thành công!', 'success');
                setTimeout(() => {
                    window.location.href = redirectUrl || '/';
                }, 500);
            } catch (error) {
                console.error('Lỗi đăng ký:', error);

                let msg = error?.message || error?.error?.message || 'Đăng ký không thành công. Vui lòng kiểm tra lại thông tin.';

                // Lấy chi tiết lỗi validation
                if (error?.details && typeof error.details === 'object') {
                    const detailMsgs = Object.values(error.details).flat().join(' ');
                    if (detailMsgs) msg = detailMsgs;
                }

                alertEl.textContent = msg;
                alertEl.classList.remove('d-none');
                btnSubmit.disabled = false;
                btnSubmit.textContent = 'Đăng ký tài khoản';
            }
        });
    }
});
