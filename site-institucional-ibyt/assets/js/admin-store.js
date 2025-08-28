// IBYT Store Admin JavaScript

// State Management
let adminApps = [];
let filteredAdminApps = [];
let editingAppId = null;

// DOM Elements
const appsTableBody = document.getElementById('appsTableBody');
const adminEmptyState = document.getElementById('adminEmptyState');
const adminSearchInput = document.getElementById('adminSearchInput');
const statusFilter = document.getElementById('statusFilter');
const appFormModal = document.getElementById('appFormModal');
const deleteConfirmModal = document.getElementById('deleteConfirmModal');
const appForm = document.getElementById('appForm');

// Initialize Admin
document.addEventListener('DOMContentLoaded', function() {
    loadAdminApps();
    setupAdminEventListeners();
    updateStats();
});

// Setup Event Listeners
function setupAdminEventListeners() {
    // Search
    if (adminSearchInput) {
        adminSearchInput.addEventListener('input', handleAdminSearch);
    }
    
    // Status Filter
    if (statusFilter) {
        statusFilter.addEventListener('change', handleStatusFilter);
    }
    
    // Form Submit
    if (appForm) {
        appForm.addEventListener('submit', handleAppFormSubmit);
    }
    
    // File Uploads
    setupFileUploads();
    
    // Modal Close Events
    window.addEventListener('click', function(event) {
        if (event.target === appFormModal) {
            closeAppFormModal();
        }
        if (event.target === deleteConfirmModal) {
            closeDeleteModal();
        }
    });
}

// Load Admin Apps
async function loadAdminApps() {
    try {
        // Simulate API call - replace with actual endpoint
        const response = await fetch('api/admin-apps.php', {
            credentials: 'same-origin' // Include session cookies
        });
        
        if (response.status === 401) {
            // Authentication error - redirect to login
            showAdminNotification('Sessão expirada. Redirecionando para login...', 'error');
            setTimeout(() => {
                window.location.href = 'login-admin.php';
            }, 2000);
            return;
        }
        
        if (response.ok) {
            adminApps = await response.json();
        } else {
            // Fallback data for demo
            adminApps = await loadDemoAdminApps();
        }
        
        filteredAdminApps = [...adminApps];
        renderAdminApps();
        updateStats();
        
    } catch (error) {
        console.error('Error loading admin apps:', error);
        // Load demo data on error
        adminApps = await loadDemoAdminApps();
        filteredAdminApps = [...adminApps];
        renderAdminApps();
        updateStats();
    }
}

// Demo Admin Apps Data
async function loadDemoAdminApps() {
    return [
        {
            id: 1,
            name: 'Nível Certo',
            developer: 'IBYT Software',
            category: 'monitoramento',
            description: 'Sistema inteligente de monitoramento de reservatórios em tempo real.',
            version: '2.1.0',
            size: '5.2 MB',
            rating: 4.9,
            downloads: 1250,
            price: 0,
            featured: true,
            status: 'active',
            icon: 'assets/img/app-icons/nivel-certo.png',
            screenshots: ['screenshot1.jpg', 'screenshot2.jpg'],
            apkUrl: 'downloads/nivel-certo-v2.1.0.apk',
            compatibility: 'Android 6.0+',
            updatedAt: '2025-08-20',
            createdAt: '2025-01-15',
            tags: ['iot', 'sensores', 'monitoramento', 'água']
        },
        {
            id: 2,
            name: 'IBYT Monitor',
            developer: 'IBYT Software',
            category: 'gestao',
            description: 'Ferramenta completa para gestão e monitoramento de sistemas empresariais.',
            version: '1.5.2',
            size: '8.7 MB',
            rating: 4.7,
            downloads: 890,
            price: 0,
            featured: false,
            status: 'active',
            icon: 'assets/img/app-icons/ibyt-monitor.png',
            screenshots: ['monitor1.jpg'],
            apkUrl: 'downloads/ibyt-monitor-v1.5.2.apk',
            compatibility: 'Android 7.0+',
            updatedAt: '2025-08-15',
            createdAt: '2025-02-10',
            tags: ['gestão', 'dashboard', 'métricas']
        },
        {
            id: 3,
            name: 'App Demo',
            developer: 'IBYT Software',
            category: 'utilidades',
            description: 'Aplicativo em desenvolvimento para testes.',
            version: '0.1.0',
            size: '2.1 MB',
            rating: 0,
            downloads: 0,
            price: 0,
            featured: false,
            status: 'draft',
            icon: '',
            screenshots: [],
            apkUrl: '',
            compatibility: 'Android 6.0+',
            updatedAt: '2025-08-25',
            createdAt: '2025-08-25',
            tags: ['teste', 'desenvolvimento']
        }
    ];
}

// Handle Admin Search
function handleAdminSearch(event) {
    const query = event.target.value.toLowerCase().trim();
    const status = statusFilter ? statusFilter.value : 'all';
    
    filteredAdminApps = adminApps.filter(app => {
        const matchesSearch = query === '' || 
            app.name.toLowerCase().includes(query) ||
            app.category.toLowerCase().includes(query);
        
        const matchesStatus = status === 'all' || app.status === status;
        
        return matchesSearch && matchesStatus;
    });
    
    renderAdminApps();
}

// Handle Status Filter
function handleStatusFilter(event) {
    const status = event.target.value;
    const query = adminSearchInput ? adminSearchInput.value.toLowerCase().trim() : '';
    
    filteredAdminApps = adminApps.filter(app => {
        const matchesSearch = query === '' || 
            app.name.toLowerCase().includes(query) ||
            app.category.toLowerCase().includes(query);
        
        const matchesStatus = status === 'all' || app.status === status;
        
        return matchesSearch && matchesStatus;
    });
    
    renderAdminApps();
}

// Render Admin Apps Table
function renderAdminApps() {
    if (!appsTableBody) return;
    
    if (filteredAdminApps.length === 0) {
        appsTableBody.innerHTML = '';
        if (adminEmptyState) adminEmptyState.style.display = 'block';
        return;
    }
    
    if (adminEmptyState) adminEmptyState.style.display = 'none';
    
    appsTableBody.innerHTML = filteredAdminApps.map(app => createAppTableRow(app)).join('');
}

// Create App Table Row
function createAppTableRow(app) {
    const statusClass = `status-${app.status}`;
    const ratingStars = app.rating > 0 ? '★'.repeat(Math.floor(app.rating)) : '-';
    
    return `
        <tr>
            <td>
                <div class="app-table-info">
                    <div class="app-table-icon">
                        ${app.icon ? `<img src="${app.icon}" alt="${app.name}">` : `<span class="material-icons-round">apps</span>`}
                    </div>
                    <div class="app-table-details">
                        <h4>${app.name}</h4>
                        <p>${app.developer}</p>
                    </div>
                </div>
            </td>
            <td>${getCategoryName(app.category)}</td>
            <td>${app.version}</td>
            <td><span class="status-badge ${statusClass}">${getStatusName(app.status)}</span></td>
            <td>${app.downloads.toLocaleString()}</td>
            <td>
                <div class="rating-display">
                    <span class="rating-stars">${ratingStars}</span>
                    <span class="rating-value">${app.rating > 0 ? app.rating : '-'}</span>
                </div>
            </td>
            <td>${formatDate(app.updatedAt)}</td>
            <td>
                <div class="table-actions">
                    <button class="action-btn view" onclick="viewApp(${app.id})" title="Visualizar">
                        <span class="material-icons-round">visibility</span>
                    </button>
                    <button class="action-btn edit" onclick="editApp(${app.id})" title="Editar">
                        <span class="material-icons-round">edit</span>
                    </button>
                    <button class="action-btn delete" onclick="showDeleteConfirm(${app.id})" title="Excluir">
                        <span class="material-icons-round">delete</span>
                    </button>
                </div>
            </td>
        </tr>
    `;
}

// Get Category Name
function getCategoryName(category) {
    const categories = {
        'monitoramento': 'Monitoramento',
        'gestao': 'Gestão',
        'utilidades': 'Utilidades',
        'comunicacao': 'Comunicação',
        'produtividade': 'Produtividade'
    };
    return categories[category] || category;
}

// Get Status Name
function getStatusName(status) {
    const statuses = {
        'active': 'Ativo',
        'draft': 'Rascunho',
        'inactive': 'Inativo'
    };
    return statuses[status] || status;
}

// Update Stats
function updateStats() {
    const totalApps = adminApps.length;
    const totalDownloads = adminApps.reduce((sum, app) => sum + app.downloads, 0);
    const avgRating = adminApps.length > 0 ? 
        adminApps.reduce((sum, app) => sum + app.rating, 0) / adminApps.length : 0;
    const activeApps = adminApps.filter(app => app.status === 'active').length;
    
    // Update DOM elements
    const totalAppsEl = document.getElementById('totalApps');
    const totalDownloadsEl = document.getElementById('totalDownloads');
    const avgRatingEl = document.getElementById('avgRating');
    const activeAppsEl = document.getElementById('activeApps');
    
    if (totalAppsEl) totalAppsEl.textContent = totalApps;
    if (totalDownloadsEl) totalDownloadsEl.textContent = totalDownloads.toLocaleString();
    if (avgRatingEl) avgRatingEl.textContent = avgRating.toFixed(1);
    if (activeAppsEl) activeAppsEl.textContent = activeApps;
}

// Show Add App Modal
function showAddAppModal() {
    editingAppId = null;
    document.getElementById('formModalTitle').textContent = 'Adicionar Aplicativo';
    
    // Reset form
    if (appForm) appForm.reset();
    resetFileUploads();
    
    // Show modal
    if (appFormModal) {
        appFormModal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

// Edit App
function editApp(appId) {
    const app = adminApps.find(a => a.id === appId);
    if (!app) return;
    
    editingAppId = appId;
    document.getElementById('formModalTitle').textContent = 'Editar Aplicativo';
    
    // Populate form
    document.getElementById('appName').value = app.name;
    document.getElementById('appCategory').value = app.category;
    document.getElementById('appDescription').value = app.description;
    document.getElementById('appVersion').value = app.version;
    document.getElementById('appDeveloper').value = app.developer;
    document.getElementById('appCompatibility').value = app.compatibility;
    document.getElementById('appStatus').value = app.status;
    document.getElementById('appPrice').value = app.price;
    document.getElementById('appFeatured').checked = app.featured;
    document.getElementById('appTags').value = app.tags.join(', ');
    
    // Show modal
    if (appFormModal) {
        appFormModal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

// View App
function viewApp(appId) {
    // Redirect to store with app details
    window.open(`loja.php#app-${appId}`, '_blank');
}

// Close App Form Modal
function closeAppFormModal() {
    if (appFormModal) {
        appFormModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    editingAppId = null;
}

// Handle App Form Submit
async function handleAppFormSubmit(event) {
    event.preventDefault();
    
    const formData = new FormData(appForm);
    
    // Validate required fields
    if (!formData.get('name') || !formData.get('category') || !formData.get('description')) {
        showAdminNotification('Preencha todos os campos obrigatórios', 'error');
        return;
    }
    
    try {
        // Show loading
        const submitBtn = appForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="material-icons-round">hourglass_empty</span> Salvando...';
        
        // Simulate API call
    const response = await fetch('api/save-app.php', {
            method: 'POST',
            body: formData
        });
    // Try to read JSON response
    let data = null;
    try { data = await response.json(); } catch (_) {}

    if (response.ok && data && data.success) {
            showAdminNotification(
                editingAppId ? 'Aplicativo atualizado com sucesso!' : 'Aplicativo criado com sucesso!',
                'success'
            );
            
            // Update local data (in real app, reload from server)
            if (editingAppId) {
                updateLocalApp(editingAppId, formData);
            } else {
                addLocalApp(formData);
            }
            
            closeAppFormModal();
            renderAdminApps();
            updateStats();
        } else {
            const msg = (data && data.message) ? data.message : 'Erro ao salvar aplicativo';
            throw new Error(msg);
        }
        
        // Restore button
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        
    } catch (error) {
        console.error('Error saving app:', error);
        showAdminNotification('Erro ao salvar aplicativo. Tente novamente.', 'error');
        
        // Restore button
        const submitBtn = appForm.querySelector('button[type="submit"]');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<span class="material-icons-round">save</span> Salvar Aplicativo';
    }
}

// Update Local App (demo function)
function updateLocalApp(appId, formData) {
    const appIndex = adminApps.findIndex(a => a.id === appId);
    if (appIndex !== -1) {
        adminApps[appIndex] = {
            ...adminApps[appIndex],
            name: formData.get('name'),
            category: formData.get('category'),
            description: formData.get('description'),
            version: formData.get('version'),
            developer: formData.get('developer'),
            compatibility: formData.get('compatibility'),
            status: formData.get('status'),
            price: parseFloat(formData.get('price') || 0),
            featured: formData.get('featured') === 'on',
            tags: formData.get('tags').split(',').map(tag => tag.trim()),
            updatedAt: new Date().toISOString().split('T')[0]
        };
    }
}

// Add Local App (demo function)
function addLocalApp(formData) {
    const newApp = {
        id: Math.max(...adminApps.map(a => a.id)) + 1,
        name: formData.get('name'),
        category: formData.get('category'),
        description: formData.get('description'),
        version: formData.get('version') || '1.0.0',
        developer: formData.get('developer') || 'IBYT Software',
        compatibility: formData.get('compatibility') || 'Android 6.0+',
        status: formData.get('status') || 'draft',
        price: parseFloat(formData.get('price') || 0),
        featured: formData.get('featured') === 'on',
        tags: formData.get('tags').split(',').map(tag => tag.trim()),
        rating: 0,
        downloads: 0,
        size: '0 MB',
        icon: '',
        screenshots: [],
        apkUrl: '',
        createdAt: new Date().toISOString().split('T')[0],
        updatedAt: new Date().toISOString().split('T')[0]
    };
    
    adminApps.push(newApp);
    filteredAdminApps = [...adminApps];
}

// Show Delete Confirmation
function showDeleteConfirm(appId) {
    const app = adminApps.find(a => a.id === appId);
    if (!app) return;
    
    document.getElementById('deleteAppName').textContent = app.name;
    
    // Store app ID for deletion
    window.deleteAppId = appId;
    
    if (deleteConfirmModal) {
        deleteConfirmModal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

// Close Delete Modal
function closeDeleteModal() {
    if (deleteConfirmModal) {
        deleteConfirmModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    window.deleteAppId = null;
}

// Confirm Delete
async function confirmDelete() {
    const appId = window.deleteAppId;
    if (!appId) return;
    
    try {
        // Prefer POST with JSON for wider compatibility
        const response = await fetch('api/delete-app.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ _method: 'DELETE', id: appId })
        });
        
        if (response.ok) {
            // Optional: read JSON for message
            let data = {};
            try { data = await response.json(); } catch (_) {}
            // Remove from local data
            adminApps = adminApps.filter(a => a.id !== appId);
            filteredAdminApps = filteredAdminApps.filter(a => a.id !== appId);
            
            renderAdminApps();
            updateStats();
            closeDeleteModal();
            showAdminNotification(((data && data.message) || 'Aplicativo excluído com sucesso!'), 'success');
        } else {
            throw new Error('Erro ao excluir aplicativo');
        }
    } catch (error) {
        console.error('Error deleting app:', error);
        showAdminNotification(`Erro ao excluir aplicativo: ${error.message}`, 'error');
    }
}

// Setup File Uploads
function setupFileUploads() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', handleFileUpload);
    });
}

// Handle File Upload
function handleFileUpload(event) {
    const input = event.target;
    const files = input.files;
    const previewId = input.id + 'Preview';
    const preview = document.getElementById(previewId);
    
    if (!preview) return;
    
    preview.innerHTML = '';
    
    if (files.length === 0) {
        preview.classList.remove('active');
        return;
    }
    
    preview.classList.add('active');
    
    Array.from(files).forEach((file, index) => {
        const previewItem = document.createElement('div');
        previewItem.className = 'preview-item';
        
        const icon = getFileIcon(file.type);
        const size = formatFileSize(file.size);
        
        previewItem.innerHTML = `
            <div class="preview-icon">
                <span class="material-icons-round">${icon}</span>
            </div>
            <div class="preview-info">
                <div class="preview-name">${file.name}</div>
                <div class="preview-size">${size}</div>
            </div>
            <button type="button" class="remove-file" onclick="removeFile('${input.id}', ${index})">
                <span class="material-icons-round">close</span>
            </button>
        `;
        
        preview.appendChild(previewItem);
    });
}

// Get File Icon
function getFileIcon(mimeType) {
    if (mimeType.startsWith('image/')) return 'image';
    if (mimeType === 'application/vnd.android.package-archive') return 'android';
    return 'description';
}

// Format File Size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Remove File
function removeFile(inputId, fileIndex) {
    const input = document.getElementById(inputId);
    const dt = new DataTransfer();
    
    Array.from(input.files).forEach((file, index) => {
        if (index !== fileIndex) {
            dt.items.add(file);
        }
    });
    
    input.files = dt.files;
    handleFileUpload({ target: input });
}

// Reset File Uploads
function resetFileUploads() {
    const previews = document.querySelectorAll('.file-preview');
    previews.forEach(preview => {
        preview.innerHTML = '';
        preview.classList.remove('active');
    });
}

// Format Date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
}

// Show Admin Notification
function showAdminNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `admin-notification admin-notification-${type}`;
    
    const bgColor = type === 'success' ? '#4caf50' : type === 'error' ? '#f44336' : '#2196f3';
    const icon = type === 'success' ? 'check_circle' : type === 'error' ? 'error' : 'info';
    
    notification.innerHTML = `
        <span class="material-icons-round">${icon}</span>
        <span>${message}</span>
        <button onclick="this.parentElement.remove()">
            <span class="material-icons-round">close</span>
        </button>
    `;
    
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: ${bgColor};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        z-index: 10000;
        animation: slideInRight 0.3s ease-out;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        max-width: 400px;
    `;
    
    const closeBtn = notification.querySelector('button');
    closeBtn.style.cssText = `
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        padding: 0;
        margin-left: auto;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    }, 5000);
}

// Global functions
window.showAddAppModal = showAddAppModal;
window.editApp = editApp;
window.viewApp = viewApp;
window.showDeleteConfirm = showDeleteConfirm;
window.confirmDelete = confirmDelete;
window.closeAppFormModal = closeAppFormModal;
window.closeDeleteModal = closeDeleteModal;
window.removeFile = removeFile;
