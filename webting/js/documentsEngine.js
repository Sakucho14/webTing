/**
 * Webting V4 - Documents Engine
 */
window.DocumentsEngine = (function() {
    'use strict';

    let docs = [];
    
    function init() {
        document.getElementById('docCategoryFilter').addEventListener('change', loadDocuments);
        
        document.getElementById('btnViewCards').addEventListener('click', (e) => {
            e.target.classList.add('active');
            document.getElementById('btnViewList').classList.remove('active');
            document.getElementById('documentsList').className = 'documents-grid cards-view';
        });
        
        document.getElementById('btnViewList').addEventListener('click', (e) => {
            e.target.classList.add('active');
            document.getElementById('btnViewCards').classList.remove('active');
            document.getElementById('documentsList').className = 'documents-grid list-view';
        });
        
        // Setup admin upload form
        document.getElementById('uploadDocForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            formData.append('action', 'upload');
            
            try {
                const response = await fetch('php/documents_api.php', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                if (res.success) {
                    WebtingApp.showToast(res.message, 'success');
                    document.getElementById('uploadDocModal').style.display = 'none';
                    form.reset();
                    loadDocuments();
                } else {
                    WebtingApp.showToast(res.message, 'error');
                }
            } catch (err) {
                WebtingApp.showToast('Error al subir documento', 'error');
            }
        });
        
        checkAdminStatus();
    }
    
    function checkAdminStatus() {
        fetch('php/auth.php?action=check')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data.role === 'admin') {
                    const btnUpload = document.getElementById('btnUploadDoc');
                    if (btnUpload) {
                        btnUpload.style.display = 'block';
                        btnUpload.addEventListener('click', () => {
                            document.getElementById('uploadDocModal').style.display = 'flex';
                        });
                    }
                }
            })
            .catch(() => {});
    }

    async function loadDocuments() {
        const cat = document.getElementById('docCategoryFilter').value;
        const container = document.getElementById('documentsList');
        container.innerHTML = '<p>Cargando documentos...</p>';
        
        try {
            const res = await fetch(`php/documents_api.php?action=list&category=${cat}`);
            const json = await res.json();
            
            if (json.success) {
                docs = json.data;
                renderDocuments();
            } else {
                container.innerHTML = `<p>Error: ${json.message}</p>`;
            }
        } catch (err) {
            container.innerHTML = '<p>Error de conexión</p>';
        }
    }
    
    function renderDocuments() {
        const container = document.getElementById('documentsList');
        container.innerHTML = '';
        
        if (docs.length === 0) {
            container.innerHTML = '<p>No hay documentos disponibles.</p>';
            return;
        }
        
        docs.forEach(doc => {
            const el = document.createElement('div');
            el.className = 'document-card';
            el.innerHTML = `
                <div class="doc-icon">${doc.file_type.includes('pdf') ? '📄' : (doc.file_type.includes('spreadsheet') ? '📊' : '📝')}</div>
                <div class="doc-info">
                    <h4>${doc.title}</h4>
                    <span class="doc-meta">v${doc.version} | ${doc.category}</span>
                </div>
                <div class="doc-actions">
                    <button class="btn btn-primary btn-sm" onclick="window.open('php/documents_api.php?action=download&id=${doc.id}')">Descargar</button>
                </div>
            `;
            container.appendChild(el);
        });
    }

    // Initialize events when DOM is ready
    document.addEventListener('DOMContentLoaded', init);
    
    return {
        loadDocuments
    };
})();
