/**
 * Webting V4 - Rewards Engine
 */
window.RewardsEngine = (function() {
    'use strict';

    function initDashboard() {
        fetchDashboard();
        fetchCatalog();
        
        document.getElementById('btnRewardHistory').addEventListener('click', showHistory);
    }
    
    async function fetchDashboard() {
        try {
            const res = await fetch('php/rewards_api.php?action=dashboard');
            const json = await res.json();
            
            if (json.success) {
                document.getElementById('userTierName').innerText = `Nivel ${json.data.tier}`;
                document.getElementById('userPoints').innerText = json.data.points;
                document.getElementById('userTierProgress').style.width = `${json.data.progress}%`;
            }
        } catch (err) {
            console.error(err);
        }
    }
    
    async function fetchCatalog() {
        const container = document.getElementById('rewardsGrid');
        container.innerHTML = '<p>Cargando catálogo...</p>';
        
        try {
            const res = await fetch('php/rewards_api.php?action=catalog');
            const json = await res.json();
            
            if (json.success) {
                container.innerHTML = '';
                json.data.forEach(item => {
                    const el = document.createElement('div');
                    el.className = 'game-card';
                    el.style.display = 'flex';
                    el.style.flexDirection = 'column';
                    el.style.justifyContent = 'space-between';
                    
                    el.innerHTML = `
                        <div>
                            <div class="game-cover" style="background-image: url('${item.image_url || 'https://via.placeholder.com/300x150?text=Recompensa'}')"></div>
                            <div class="game-info">
                                <h3 class="game-title">${item.title}</h3>
                                <p style="font-size:0.8rem; margin:10px 0; color:#aaa;">${item.description}</p>
                            </div>
                        </div>
                        <button class="btn btn-primary" onclick="window.RewardsEngine.redeem(${item.id})" style="margin: 10px;">Canjear por ${item.cost_points} Pts</button>
                    `;
                    container.appendChild(el);
                });
            } else {
                container.innerHTML = `<p>Inicia sesión para ver las recompensas.</p>`;
            }
        } catch (err) {
            container.innerHTML = '<p>Error cargando catálogo.</p>';
        }
    }
    
    async function redeem(rewardId) {
        if (!confirm('¿Seguro que deseas canjear esta recompensa?')) return;
        
        try {
            const res = await fetch('php/rewards_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=redeem&reward_id=${rewardId}`
            });
            const json = await res.json();
            
            if (json.success) {
                WebtingApp.showToast(json.message, 'success');
                fetchDashboard();
            } else {
                WebtingApp.showToast(json.message, 'error');
            }
        } catch (err) {
            WebtingApp.showToast('Error al canjear', 'error');
        }
    }
    
    async function showHistory() {
        try {
            const res = await fetch('php/rewards_api.php?action=history');
            const json = await res.json();
            
            if (json.success) {
                let html = '<ul>';
                json.data.forEach(tx => {
                    const color = tx.type === 'earn' ? '#00FF00' : '#FF0000';
                    const sign = tx.type === 'earn' ? '+' : '-';
                    html += `<li style="margin-bottom: 10px;">
                        <span style="color:${color}; font-weight:bold;">${sign}${tx.amount} Pts</span> - ${tx.description} 
                        <br><small style="color:#666;">${tx.created_at}</small>
                    </li>`;
                });
                html += '</ul>';
                WebtingApp.openDocument('Historial de Puntos', html);
            }
        } catch (err) {
            WebtingApp.showToast('Error cargando historial', 'error');
        }
    }

    return {
        initDashboard,
        redeem
    };
})();
