/**
 * Webting v3.5 - Motor Principal de la Aplicación
 * Manejo de búsqueda, filtros, Dark Mode, menú móvil y Documentos.
 */

window.WebtingApp = (function() {
    'use strict';

    // SVG Icon Registry (Sin emojis)
    const ICONS = {
        gamepad: '<svg class="icon-svg" viewBox="0 0 24 24"><path fill="currentColor" d="M21 6H3c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-10 7H9v2H7v-2H5v-2h2V9h2v2h2v2zm4.5 1c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm3-3c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>',
        check: '<svg class="icon-svg" viewBox="0 0 24 24"><path fill="currentColor" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>',
        close: '<svg class="icon-svg" viewBox="0 0 24 24"><path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>',
        sun: '<svg class="icon-svg" viewBox="0 0 24 24"><path fill="currentColor" d="M12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5zM2 13h2c.55 0 1-.45 1-1s-.45-1-1-1H2c-.55 0-1 .45-1 1s.45 1 1 1zm18 0h2c.55 0 1-.45 1-1s-.45-1-1-1h-2c-.55 0-1 .45-1 1s.45 1 1 1zM11 2v2c0 .55.45 1 1 1s1-.45 1-1V2c0-.55-.45-1-1-1s-1 .45-1 1zm0 18v2c0 .55.45 1 1 1s1-.45 1-1v-2c0-.55-.45-1-1-1s-1 .45-1 1zM5.99 4.58c-.39-.39-1.03-.39-1.41 0-.39.39-.39 1.03 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0 .39-.39.39-1.03 0-1.41L5.99 4.58zm12.37 12.37c-.39-.39-1.03-.39-1.41 0-.39.39-.39 1.03 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0 .39-.39.39-1.03 0-1.41l-1.06-1.06zm1.06-10.96c.39-.39.39-1.03 0-1.41-.39-.39-1.03-.39-1.41 0l-1.06 1.06c-.39.39-.39 1.03 0 1.41.39.39 1.03.39 1.41 0l1.06-1.06zM7.05 18.36c.39-.39.39-1.03 0-1.41-.39-.39-1.03-.39-1.41 0l-1.06 1.06c-.39.39-.39 1.03 0 1.41.39.39 1.03.39 1.41 0l1.06-1.06z"/></svg>',
        moon: '<svg class="icon-svg" viewBox="0 0 24 24"><path fill="currentColor" d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9c0-.46-.04-.92-.1-1.36-.98 1.37-2.58 2.26-4.4 2.26-3.03 0-5.5-2.47-5.5-5.5 0-1.82.89-3.42 2.26-4.4C12.92 3.04 12.46 3 12 3zm0 16c-3.86 0-7-3.14-7-7s3.14-7 7-7c.2 0 .4.02.6.05-.2.53-.32 1.1-.32 1.69 0 2.76 2.24 5 5 5 .59 0 1.16-.12 1.69-.32.03.2.05.4.05.6 0 3.86-3.14 7-7 7z"/></svg>'
    };

    let state = {
        games: [],
        documents: [],
        filterCategory: 'all',
        filterDifficulty: 'all',
        filterAge: 'all',
        searchQuery: '',
        isDarkMode: false
    };

    let dom = {};

    function init() {
        cacheDOM();
        bindEvents();
        loadSiteSettings();
        initTheme();
        loadGames();
        loadPublicDocuments();
    }

    function cacheDOM() {
        dom.gamesGrid = document.getElementById('gamesGrid');
        dom.searchInput = document.getElementById('searchInput');
        dom.heroTitle = document.getElementById('heroTitle');
        dom.heroSubtitle = document.getElementById('heroSubtitle');
        dom.toastContainer = document.getElementById('toastContainer');
        dom.btnThemeToggle = document.getElementById('btnThemeToggle');
        dom.mobileMenuBtn = document.getElementById('mobileMenuBtn');
        dom.desktopNavElements = document.getElementById('desktopNavElements');
        dom.navDocsLinks = document.getElementById('navDocsLinks');
        dom.documentModal = document.getElementById('documentModal');
        dom.docModalTitle = document.getElementById('docModalTitle');
        dom.docModalBody = document.getElementById('docModalBody');
    }

    function bindEvents() {
        if (dom.searchInput) {
            dom.searchInput.addEventListener('input', (e) => {
                state.searchQuery = e.target.value.toLowerCase().trim();
                renderGames();
            });
        }

        if (dom.btnThemeToggle) {
            dom.btnThemeToggle.addEventListener('click', toggleTheme);
        }

        if (dom.mobileMenuBtn) {
            dom.mobileMenuBtn.addEventListener('click', () => {
                dom.desktopNavElements.classList.toggle('active');
            });
        }

        document.addEventListener('click', (e) => {
            const chip = e.target.closest('.chip');
            if (chip) {
                const filterType = chip.dataset.filterType;
                const filterVal = chip.dataset.filterValue;

                if (filterType === 'category') {
                    state.filterCategory = filterVal;
                    updateChipActiveState('[data-filter-type="category"]', chip);
                } else if (filterType === 'difficulty') {
                    state.filterDifficulty = filterVal;
                    updateChipActiveState('[data-filter-type="difficulty"]', chip);
                } else if (filterType === 'age') {
                    state.filterAge = filterVal;
                    updateChipActiveState('[data-filter-type="age"]', chip);
                }
                renderGames();
            }
        });
    }

    function showView(viewId) {
        document.querySelectorAll('.view-section').forEach(el => el.style.display = 'none');
        document.getElementById(viewId + '-view').style.display = 'block';
        
        document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));
        const activeLink = document.querySelector(`.nav-link[onclick*="${viewId}"]`);
        if (activeLink) activeLink.classList.add('active');

        if (viewId === 'documents' && window.DocumentsEngine) {
            window.DocumentsEngine.loadDocuments();
        } else if (viewId === 'rewards' && window.RewardsEngine) {
            window.RewardsEngine.initDashboard();
        }
    }

    /* --- Temas (Oscuro / Claro) --- */
    function initTheme() {
        const savedTheme = localStorage.getItem('webting_theme');
        if (savedTheme === 'dark') {
            state.isDarkMode = true;
            document.body.classList.add('dark-theme');
        }
        updateThemeBtnIcon();
    }

    function toggleTheme() {
        state.isDarkMode = !state.isDarkMode;
        if (state.isDarkMode) {
            document.body.classList.add('dark-theme');
            localStorage.setItem('webting_theme', 'dark');
        } else {
            document.body.classList.remove('dark-theme');
            localStorage.setItem('webting_theme', 'light');
        }
        updateThemeBtnIcon();
    }

    function updateThemeBtnIcon() {
        if (dom.btnThemeToggle) {
            dom.btnThemeToggle.innerHTML = state.isDarkMode ? ICONS.sun : ICONS.moon;
        }
    }

    /* --- Documentos Públicos --- */
    function loadPublicDocuments() {
        fetch('php/documents.php?action=list_public')
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    state.documents = res.data;
                    renderNavDocs();
                }
            })
            .catch(() => {});
    }

    function renderNavDocs() {
        if (!dom.navDocsLinks) return;
        dom.navDocsLinks.innerHTML = state.documents.map(doc => `
            <a class="nav-doc-link" onclick="WebtingApp.openDocument(${doc.id})">${doc.title}</a>
        `).join('');
    }

    function openDocument(id) {
        fetch(`php/documents.php?action=get&id=${id}`)
            .then(res => res.json())
            .then(res => {
                if (res.success && res.data) {
                    if (dom.docModalTitle) dom.docModalTitle.textContent = res.data.title;
                    if (dom.docModalBody) dom.docModalBody.innerHTML = res.data.content_html;
                    if (dom.documentModal) dom.documentModal.classList.add('active');
                } else {
                    showToast('No se pudo cargar el documento', 'error');
                }
            })
            .catch(() => showToast('Error de conexión', 'error'));
    }

    function closeDocument() {
        if (dom.documentModal) dom.documentModal.classList.remove('active');
    }

    /* --- Funciones Base --- */
    function updateChipActiveState(selector, activeChip) {
        document.querySelectorAll(selector).forEach(c => {
            c.classList.remove('active', 'active-orange');
        });
        activeChip.classList.add(activeChip.dataset.filterType === 'category' ? 'active-orange' : 'active');
    }

    function loadSiteSettings() {
        fetch('php/admin.php?action=get_settings')
            .then(res => res.json())
            .then(res => {
                if (res.success && res.data) applySettings(res.data);
                else loadSettingsFromLocal();
            })
            .catch(() => loadSettingsFromLocal());
    }

    function loadSettingsFromLocal() {
        const local = localStorage.getItem('webting_v3_settings');
        if (local) applySettings(JSON.parse(local));
    }

    function applySettings(settings) {
        if (settings.primary_color) document.documentElement.style.setProperty('--site-primary', settings.primary_color);
        if (settings.secondary_color) document.documentElement.style.setProperty('--site-secondary', settings.secondary_color);
        if (settings.banner_title && dom.heroTitle) dom.heroTitle.textContent = settings.banner_title;
        if (settings.banner_subtitle && dom.heroSubtitle) dom.heroSubtitle.textContent = settings.banner_subtitle;
    }

    function loadGames() {
        fetch('php/games.php?action=list')
            .then(res => res.json())
            .then(res => {
                if (res.success && Array.isArray(res.data)) state.games = res.data;
                else loadGamesFromLocal();
                renderGames();
            })
            .catch(() => {
                loadGamesFromLocal();
                renderGames();
            });
    }

    function loadGamesFromLocal() {
        const local = localStorage.getItem('webting_v3_games');
        state.games = local ? JSON.parse(local) : [];
    }

    function renderGames() {
        if (!dom.gamesGrid) return;

        const filtered = state.games.filter(game => {
            const matchesCat = state.filterCategory === 'all' || game.subject === state.filterCategory;
            const matchesDiff = state.filterDifficulty === 'all' || game.difficulty === state.filterDifficulty;
            const matchesAge = state.filterAge === 'all' || game.ageRange === state.filterAge;
            const matchesSearch = !state.searchQuery || 
                game.title.toLowerCase().includes(state.searchQuery) ||
                game.subject.toLowerCase().includes(state.searchQuery);

            return matchesCat && matchesDiff && matchesAge && matchesSearch;
        });

        if (filtered.length === 0) {
            dom.gamesGrid.innerHTML = `
                <div class="empty-state">
                    <div class="empty-title">NO HAY JUEGOS DISPONIBLES</div>
                    <p class="empty-desc">No existen juegos que coincidan con el filtro seleccionado.</p>
                </div>
            `;
            return;
        }

        dom.gamesGrid.innerHTML = filtered.map(game => `
            <div class="game-card" data-game-id="${game.id}">
                <div class="card-img-wrapper">
                    <img src="${game.logoUrl || 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=600&q=80'}" alt="${game.title}" class="card-img" />
                    <div class="card-hover-overlay">
                        <p class="hover-desc">${game.hoverDescription || 'Sin descripción disponible.'}</p>
                        <button class="btn btn-primary" onclick="GameEngine.openGame('${game.id}')">
                            ${ICONS.gamepad} JUGAR EN VIVO
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="card-subject">${game.subject}</div>
                    <h3 class="card-title">${game.title}</h3>
                    <div class="card-meta">
                        <span class="meta-pill ${game.difficulty.toLowerCase()}">${game.difficulty}</span>
                        <span class="meta-pill">${game.ageRange} Años</span>
                        ${game.scoring_enabled || game.scoringEnabled ? `<span class="meta-pill scoring">PUNTOS REALES</span>` : ''}
                    </div>
                </div>
            </div>
        `).join('');
    }

    function showToast(message, type = 'success') {
        if (!dom.toastContainer) return;
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `<span>${type === 'success' ? ICONS.check : ICONS.close}</span> <span>${message}</span>`;
        dom.toastContainer.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    }

    return {
        init,
        getICONS: () => ICONS,
        getGames: () => state.games,
        loadGames,
        showToast,
        openDocument,
        closeDocument,
        loadPublicDocuments,
        showView
    };
})();

document.addEventListener('DOMContentLoaded', WebtingApp.init);
