/**
 * Webting v3.0 - Motor de Emulación de Juegos y Sistema de Puntuación Real
 * Administra el emulador iframe, temporizador de puntos y marcadores.
 */

window.GameEngine = (function() {
    'use strict';

    let state = {
        currentGame: null,
        scoreTimer: null,
        sessionSeconds: 0,
        currentPoints: 0
    };

    let dom = {};

    function init() {
        cacheDOM();
        bindEvents();
    }

    function cacheDOM() {
        dom.gameModal = document.getElementById('gameModal');
        dom.gameTitle = document.getElementById('gameModalTitle');
        dom.gameIframe = document.getElementById('gameIframe');
        dom.liveScoreBar = document.getElementById('liveScoreBar');
        dom.liveScoreValue = document.getElementById('liveScoreValue');
        dom.scoresList = document.getElementById('gameScoresList');
        dom.controlsList = document.getElementById('gameControlsList');
        dom.btnCloseGame = document.getElementById('btnCloseGame');
    }

    function bindEvents() {
        if (dom.btnCloseGame) {
            dom.btnCloseGame.addEventListener('click', closeGame);
        }
    }

    function openGame(gameId) {
        const games = WebtingApp.getGames();
        const game = games.find(g => g.id === gameId);

        if (!game) {
            WebtingApp.showToast('No se encontró el juego', 'error');
            return;
        }

        state.currentGame = game;
        state.sessionSeconds = 0;
        state.currentPoints = 0;

        // Configurar título
        if (dom.gameTitle) dom.gameTitle.textContent = game.title;

        // Configurar iframe según preferencias avanzadas
        if (dom.gameIframe) {
            dom.gameIframe.src = game.iframeUrl || game.iframe_url;
            dom.gameIframe.scrolling = game.iframeScroll || game.iframe_scroll ? 'yes' : 'no';
            
            if (game.iframeSandbox || game.iframe_sandbox) {
                dom.gameIframe.setAttribute('sandbox', game.iframeSandbox || game.iframe_sandbox);
            } else {
                dom.gameIframe.removeAttribute('sandbox');
            }

            if (game.iframeFullscreen || game.iframe_fullscreen) {
                dom.gameIframe.setAttribute('allowfullscreen', 'true');
            } else {
                dom.gameIframe.removeAttribute('allowfullscreen');
            }
        }

        // Configurar sistema de puntos real si está habilitado para el juego
        const isScoringEnabled = game.scoringEnabled ?? game.scoring_enabled ?? true;
        if (isScoringEnabled) {
            if (dom.liveScoreBar) dom.liveScoreBar.style.display = 'flex';
            startPointAccumulator();
        } else {
            if (dom.liveScoreBar) dom.liveScoreBar.style.display = 'none';
        }

        // Cargar controles y tabla de puntuaciones
        renderControls(game.controls || []);
        loadLeaderboard(game.id);

        // Incrementar contador de jugadas en backend
        fetch('php/games.php?action=increment_play', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: game.id })
        }).catch(() => {});

        // Abrir Modal
        if (dom.gameModal) dom.gameModal.classList.add('active');
    }

    function startPointAccumulator() {
        stopPointAccumulator();
        if (dom.liveScoreValue) dom.liveScoreValue.textContent = '0 PTS';

        state.scoreTimer = setInterval(() => {
            state.sessionSeconds++;

            // Puntuación Real: 10 Puntos cada 30 segundos
            if (state.sessionSeconds % 30 === 0) {
                state.currentPoints += 10;
                WebtingApp.showToast('+10 PUNTOS por tiempo activo', 'success');
            }

            // Bonus especial: 50 Puntos al alcanzar 5 minutos (300s)
            if (state.sessionSeconds === 300) {
                state.currentPoints += 50;
                WebtingApp.showToast('BONUS DE TIEMPO: +50 PUNTOS EXTRA', 'success');
            }

            if (dom.liveScoreValue) {
                dom.liveScoreValue.textContent = `${state.currentPoints} PTS (${state.sessionSeconds}s)`;
            }
        }, 1000);
    }

    function stopPointAccumulator() {
        if (state.scoreTimer) {
            clearInterval(state.scoreTimer);
            state.scoreTimer = null;
        }
    }

    function closeGame() {
        // Si acumulo puntos, registrarlos automáticamente
        if (state.currentPoints > 0 && state.currentGame) {
            saveScore(state.currentGame.id, state.currentPoints);
        }

        stopPointAccumulator();

        if (dom.gameIframe) dom.gameIframe.src = '';
        if (dom.gameModal) dom.gameModal.classList.remove('active');

        state.currentGame = null;
    }

    function saveScore(gameId, score) {
        const user = AuthEngine.getCurrentUser();
        const playerName = user ? user.username : 'Jugador Anónimo';

        fetch('php/scores.php?action=add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ gameId, playerName, score })
        }).then(() => {
            WebtingApp.showToast(`Puntaje de ${score} registrado para ${playerName}`, 'success');
        }).catch(() => {});
    }

    function loadLeaderboard(gameId) {
        if (!dom.scoresList) return;
        dom.scoresList.innerHTML = '<div style="font-size:0.85rem; color:#666;">Cargando puntuaciones...</div>';

        fetch(`php/scores.php?action=get&game_id=${gameId}`)
            .then(res => res.json())
            .then(res => {
                if (res.success && Array.isArray(res.data) && res.data.length > 0) {
                    renderLeaderboard(res.data);
                } else {
                    dom.scoresList.innerHTML = '<div style="font-size:0.85rem; color:#666;">No hay puntuaciones registradas aún.</div>';
                }
            })
            .catch(() => {
                dom.scoresList.innerHTML = '<div style="font-size:0.85rem; color:#666;">Puntuaciones locales no disponibles.</div>';
            });
    }

    function renderLeaderboard(scores) {
        dom.scoresList.innerHTML = scores.map((s, index) => {
            const rankClass = index === 0 ? 'rank-1' : index === 1 ? 'rank-2' : index === 2 ? 'rank-3' : '';
            return `
                <div class="score-item ${rankClass}">
                    <span class="score-player">#${index + 1} ${s.player_name}</span>
                    <span class="score-points">${s.score} PTS</span>
                </div>
            `;
        }).join('');
    }

    function renderControls(controls) {
        if (!dom.controlsList) return;

        if (!controls || controls.length === 0) {
            dom.controlsList.innerHTML = '<div style="font-size:0.85rem; color:#666;">No se especificaron controles.</div>';
            return;
        }

        dom.controlsList.innerHTML = controls.map(c => `
            <div class="control-card">
                <span class="key-cap">${c.key || c.key_name}</span>
                <span class="action-desc">${c.action || c.action_desc}</span>
            </div>
        `).join('');
    }

    return {
        init,
        openGame,
        closeGame
    };
})();

document.addEventListener('DOMContentLoaded', GameEngine.init);
