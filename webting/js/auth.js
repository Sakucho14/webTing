/**
 * Webting v3.0 - Módulo de Autenticación
 * Inicio de sesión especial //admin// / loger2010 y verificación de correo Gmail.
 */

window.AuthEngine = (function() {
    'use strict';

    let state = {
        currentUser: null,
        pendingEmailVerification: null
    };

    let dom = {};

    function init() {
        cacheDOM();
        bindEvents();
        checkSession();
    }

    function cacheDOM() {
        dom.authModal = document.getElementById('authModal');
        dom.authForm = document.getElementById('authForm');
        dom.tabLogin = document.getElementById('tabLogin');
        dom.tabRegister = document.getElementById('tabRegister');
        dom.authTitle = document.getElementById('authTitle');
        dom.emailGroup = document.getElementById('emailGroup');
        dom.verificationSection = document.getElementById('verificationSection');
        dom.verificationCodeInput = document.getElementById('verificationCodeInput');
        dom.btnSubmitAuth = document.getElementById('btnSubmitAuth');
        dom.btnVerifyCode = document.getElementById('btnVerifyCode');
        dom.btnLoginNav = document.getElementById('btnLoginNav');
        dom.userBadge = document.getElementById('userBadge');
        dom.adminBadge = document.getElementById('adminBadge');
        dom.btnAdminPanel = document.getElementById('btnAdminPanel');
        dom.btnLogout = document.getElementById('btnLogout');
        dom.usernameInput = document.getElementById('authUsername');
        dom.passwordInput = document.getElementById('authPassword');
        dom.emailInput = document.getElementById('authEmail');
    }

    function bindEvents() {
        if (dom.tabLogin) dom.tabLogin.addEventListener('click', () => switchTab('login'));
        if (dom.tabRegister) dom.tabRegister.addEventListener('click', () => switchTab('register'));

        if (dom.btnLoginNav) dom.btnLoginNav.addEventListener('click', openAuthModal);
        if (dom.btnLogout) dom.btnLogout.addEventListener('click', logout);

        if (dom.authForm) {
            dom.authForm.addEventListener('submit', (e) => {
                e.preventDefault();
                handleAuthSubmit();
            });
        }

        if (dom.btnVerifyCode) {
            dom.btnVerifyCode.addEventListener('click', handleEmailVerification);
        }
    }

    function openAuthModal() {
        if (!dom.authModal) return;
        switchTab('login');
        dom.authModal.classList.add('active');
    }

    function closeAuthModal() {
        if (!dom.authModal) return;
        dom.authModal.classList.remove('active');
        resetForm();
    }

    function switchTab(mode) {
        if (mode === 'login') {
            dom.tabLogin.classList.add('active');
            dom.tabRegister.classList.remove('active');
            dom.authTitle.textContent = 'INICIAR SESIÓN';
            dom.emailGroup.style.display = 'none';
            dom.verificationSection.style.display = 'none';
            dom.btnSubmitAuth.textContent = 'ENTRAR';
            dom.authForm.dataset.mode = 'login';
        } else {
            dom.tabRegister.classList.add('active');
            dom.tabLogin.classList.remove('active');
            dom.authTitle.textContent = 'CREAR CUENTA';
            dom.emailGroup.style.display = 'flex';
            dom.verificationSection.style.display = 'none';
            dom.btnSubmitAuth.textContent = 'REGISTRARSE';
            dom.authForm.dataset.mode = 'register';
        }
    }

    function handleAuthSubmit() {
        const mode = dom.authForm.dataset.mode;
        const username = dom.usernameInput.value.trim();
        const password = dom.passwordInput.value.trim();

        if (mode === 'login') {
            // Intento de login
            fetch('php/auth.php?action=login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, password })
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    setSessionUser(res.data);
                    closeAuthModal();
                    WebtingApp.showToast('Bienvenido ' + res.data.username, 'success');
                } else {
                    // Fallback local si no hay PHP
                    if (username === '//admin//' && password === 'loger2010') {
                        const adminUser = { username: '//admin//', role: 'admin', email: 'admin@webting.com' };
                        setSessionUser(adminUser);
                        closeAuthModal();
                        WebtingApp.showToast('Sesión de Administrador iniciada', 'success');
                    } else {
                        WebtingApp.showToast(res.message || 'Usuario o contraseña incorrectos', 'error');
                    }
                }
            })
            .catch(() => {
                if (username === '//admin//' && password === 'loger2010') {
                    const adminUser = { username: '//admin//', role: 'admin', email: 'admin@webting.com' };
                    setSessionUser(adminUser);
                    closeAuthModal();
                    WebtingApp.showToast('Sesión de Administrador iniciada (Modo Local)', 'success');
                } else {
                    WebtingApp.showToast('Error de conexión', 'error');
                }
            });

        } else {
            // Registro con correo Gmail
            const email = dom.emailInput.value.trim();

            if (!email.toLowerCase().endsWith('@gmail.com')) {
                WebtingApp.showToast('Debe ingresar un correo Gmail (@gmail.com)', 'error');
                return;
            }

            fetch('php/auth.php?action=register', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, email, password })
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    state.pendingEmailVerification = res.data.email;
                    dom.verificationSection.style.display = 'flex';
                    dom.btnSubmitAuth.style.display = 'none';
                    WebtingApp.showToast('Código de verificación: ' + (res.data.verification_code || '123456'), 'success');
                } else {
                    WebtingApp.showToast(res.message || 'Error en el registro', 'error');
                }
            })
            .catch(() => {
                // Simulación local de verificación
                state.pendingEmailVerification = email;
                dom.verificationSection.style.display = 'flex';
                dom.btnSubmitAuth.style.display = 'none';
                WebtingApp.showToast('Código de verificación enviado (Simulación)', 'success');
            });
        }
    }

    function handleEmailVerification() {
        const code = dom.verificationCodeInput.value.trim();
        if (!code) {
            WebtingApp.showToast('Ingrese el código de verificación', 'error');
            return;
        }

        fetch('php/auth.php?action=verify_email', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: state.pendingEmailVerification, code })
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                setSessionUser(res.data);
                closeAuthModal();
                WebtingApp.showToast('Cuenta verificada y sesión iniciada', 'success');
            } else {
                WebtingApp.showToast(res.message || 'Código incorrecto', 'error');
            }
        })
        .catch(() => {
            const playerUser = { username: dom.usernameInput.value.trim(), role: 'player', email: state.pendingEmailVerification };
            setSessionUser(playerUser);
            closeAuthModal();
            WebtingApp.showToast('Verificación completada (Modo Local)', 'success');
        });
    }

    function checkSession() {
        fetch('php/auth.php?action=check')
            .then(res => res.json())
            .then(res => {
                if (res.success && res.data) {
                    setSessionUser(res.data);
                } else {
                    checkLocalSession();
                }
            })
            .catch(() => checkLocalSession());
    }

    function checkLocalSession() {
        const local = localStorage.getItem('webting_v3_session');
        if (local) {
            setSessionUser(JSON.parse(local));
        }
    }

    function setSessionUser(user) {
        state.currentUser = user;
        localStorage.setItem('webting_v3_session', JSON.stringify(user));

        if (dom.btnLoginNav) dom.btnLoginNav.style.display = 'none';
        if (dom.userBadge) {
            dom.userBadge.style.display = 'inline-flex';
            dom.userBadge.querySelector('.username-text').textContent = user.username;
        }

        if (user.role === 'admin') {
            if (dom.adminBadge) dom.adminBadge.style.display = 'inline-flex';
            if (dom.btnAdminPanel) dom.btnAdminPanel.style.display = 'inline-flex';
        } else {
            if (dom.adminBadge) dom.adminBadge.style.display = 'none';
            if (dom.btnAdminPanel) dom.btnAdminPanel.style.display = 'none';
        }

        if (dom.btnLogout) dom.btnLogout.style.display = 'inline-flex';
    }

    function logout() {
        fetch('php/auth.php?action=logout').finally(() => {
            state.currentUser = null;
            localStorage.removeItem('webting_v3_session');
            window.location.reload();
        });
    }

    function resetForm() {
        if (dom.authForm) dom.authForm.reset();
        if (dom.verificationSection) dom.verificationSection.style.display = 'none';
        if (dom.btnSubmitAuth) dom.btnSubmitAuth.style.display = 'inline-flex';
    }

    return {
        init,
        getCurrentUser: () => state.currentUser,
        isAdmin: () => state.currentUser && state.currentUser.role === 'admin',
        closeAuthModal
    };
})();

document.addEventListener('DOMContentLoaded', AuthEngine.init);
