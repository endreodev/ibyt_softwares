// IBYT Store JavaScript

// State Management
let apps = [];
let filteredApps = [];
let currentFilter = 'all';
let currentView = 'grid';

// DOM Elements
const appsContainer = document.getElementById('appsContainer');
const loadingApps = document.getElementById('loadingApps');
const emptyState = document.getElementById('emptyState');
const searchInput = document.getElementById('searchInput');
const filterButtons = document.querySelectorAll('.filter-btn');
const viewButtons = document.querySelectorAll('.view-btn');
const appModal = document.getElementById('appModal');

// Initialize Store
document.addEventListener('DOMContentLoaded', function() {
    loadApps();
    setupEventListeners();
});

// Setup Event Listeners
function setupEventListeners() {
    // Search
    if (searchInput) {
        searchInput.addEventListener('input', handleSearch);
    }
    
    // Filters
    filterButtons.forEach(btn => {
        btn.addEventListener('click', handleFilter);
    });
    
    // View Toggle
    viewButtons.forEach(btn => {
        btn.addEventListener('click', handleViewToggle);
    });
    
    // Modal Close
    window.addEventListener('click', function(event) {
        if (event.target === appModal) {
            closeModal();
        }
    });
    
    // Keyboard Navigation
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });
}

// Load Apps from Server
async function loadApps() {
    try {
        showLoading();
        
        // Simulate API call - replace with actual endpoint
        const response = await fetch('api/apps.php');
        
        if (response.ok) {
            apps = await response.json();
        } else {
            // Fallback data for demo
            apps = await loadDemoApps();
        }
        
        filteredApps = [...apps];
        renderApps();
        
    } catch (error) {
        console.error('Error loading apps:', error);
        // Load demo data on error
        apps = await loadDemoApps();
        filteredApps = [...apps];
        renderApps();
    }
}

// Demo Apps Data
async function loadDemoApps() {
    return [
        {
            id: 1,
            name: 'Nível Certo',
            developer: 'IBYT Software',
            category: 'monitoramento',
            description: 'Sistema inteligente de monitoramento de reservatórios em tempo real. Receba alertas instantâneos e mantenha o controle total dos seus recursos hídricos.',
            version: '2.1.0',
            size: '5.2 MB',
            rating: 4.9,
            downloads: 1250,
            price: 0,
            featured: true,
            status: 'active',
            icon: 'assets/img/app-icons/nivel-certo.png',
            screenshots: [
                'assets/img/screenshots/nivel-certo-1.jpg',
                'assets/img/screenshots/nivel-certo-2.jpg'
            ],
            apkUrl: 'downloads/nivel-certo-v2.1.0.apk',
            compatibility: 'Android 6.0+',
            updatedAt: '2025-08-20',
            tags: ['iot', 'sensores', 'monitoramento', 'água']
        },
        {
            id: 2,
            name: 'IBYT Monitor',
            developer: 'IBYT Software',
            category: 'gestao',
            description: 'Ferramenta completa para gestão e monitoramento de sistemas empresariais. Dashboard em tempo real com métricas avançadas.',
            version: '1.5.2',
            size: '8.7 MB',
            rating: 4.7,
            downloads: 890,
            price: 0,
            featured: false,
            status: 'active',
            icon: 'assets/img/app-icons/ibyt-monitor.png',
            screenshots: [
                'assets/img/screenshots/monitor-1.jpg',
                'assets/img/screenshots/monitor-2.jpg'
            ],
            apkUrl: 'downloads/ibyt-monitor-v1.5.2.apk',
            compatibility: 'Android 7.0+',
            updatedAt: '2025-08-15',
            tags: ['gestão', 'dashboard', 'métricas', 'empresarial']
        },
        {
            id: 3,
            name: 'Sensor Config',
            developer: 'IBYT Software',
            category: 'utilidades',
            description: 'Utilitário para configuração rápida e fácil de sensores IoT. Interface intuitiva para setup de dispositivos.',
            version: '1.0.8',
            size: '3.1 MB',
            rating: 4.5,
            downloads: 450,
            price: 0,
            featured: false,
            status: 'active',
            icon: 'assets/img/app-icons/sensor-config.png',
            screenshots: [
                'assets/img/screenshots/config-1.jpg'
            ],
            apkUrl: 'downloads/sensor-config-v1.0.8.apk',
            compatibility: 'Android 6.0+',
            updatedAt: '2025-08-10',
            tags: ['configuração', 'sensores', 'iot', 'setup']
        }
    ];
}

// Show Loading State
function showLoading() {
    if (loadingApps) loadingApps.style.display = 'block';
    if (appsContainer) appsContainer.style.display = 'none';
    if (emptyState) emptyState.style.display = 'none';
}

// Hide Loading State
function hideLoading() {
    if (loadingApps) loadingApps.style.display = 'none';
}

// Handle Search
function handleSearch(event) {
    const query = event.target.value.toLowerCase().trim();
    
    filteredApps = apps.filter(app => {
        const matchesSearch = query === '' || 
            app.name.toLowerCase().includes(query) ||
            app.description.toLowerCase().includes(query) ||
            app.tags.some(tag => tag.toLowerCase().includes(query));
        
        const matchesCategory = currentFilter === 'all' || app.category === currentFilter;
        
        return matchesSearch && matchesCategory;
    });
    
    renderApps();
}

// Handle Filter
function handleFilter(event) {
    const filter = event.target.dataset.category;
    currentFilter = filter;
    
    // Update active filter button
    filterButtons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Apply filter
    const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
    
    filteredApps = apps.filter(app => {
        const matchesSearch = query === '' || 
            app.name.toLowerCase().includes(query) ||
            app.description.toLowerCase().includes(query) ||
            app.tags.some(tag => tag.toLowerCase().includes(query));
        
        const matchesCategory = filter === 'all' || app.category === filter;
        
        return matchesSearch && matchesCategory;
    });
    
    renderApps();
}

// Handle View Toggle
function handleViewToggle(event) {
    const view = event.target.dataset.view;
    currentView = view;
    
    // Update active view button
    viewButtons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Update container class
    if (appsContainer) {
        appsContainer.className = view === 'list' ? 'apps-grid list-view' : 'apps-grid';
    }
}

// Render Apps
function renderApps() {
    hideLoading();
    
    if (!appsContainer) return;
    
    if (filteredApps.length === 0) {
        appsContainer.style.display = 'none';
        if (emptyState) emptyState.style.display = 'block';
        return;
    }
    
    if (emptyState) emptyState.style.display = 'none';
    appsContainer.style.display = 'grid';
    
    appsContainer.innerHTML = filteredApps.map(app => createAppCard(app)).join('');
}

// Create App Card HTML
function createAppCard(app) {
    const stars = '★'.repeat(Math.floor(app.rating)) + '☆'.repeat(5 - Math.floor(app.rating));
    const priceText = app.price === 0 ? 'Gratuito' : `R$ ${app.price.toFixed(2)}`;
    
    return `
        <div class="app-card" onclick="showAppDetails(${app.id})">
            <div class="app-card-header">
                <div class="app-icon">
                    ${app.icon ? `<img src="${app.icon}" alt="${app.name}">` : `<span class="material-icons-round">apps</span>`}
                </div>
                <div class="app-info">
                    <h3>${app.name}</h3>
                    <div class="app-developer">${app.developer}</div>
                    <div class="app-rating">
                        <span class="stars">${stars}</span>
                        <span class="rating-text">${app.rating} (${app.downloads}+ downloads)</span>
                    </div>
                    <div class="app-tags">
                        <span class="tag category-${app.category}">${getCategoryName(app.category)}</span>
                        <span class="tag">${priceText}</span>
                        ${app.featured ? '<span class="tag" style="background: #ffc107; color: #000;">⭐ Destaque</span>' : ''}
                    </div>
                </div>
            </div>
            <div class="app-description">
                ${app.description.length > 120 ? app.description.substring(0, 120) + '...' : app.description}
            </div>
            <div class="app-card-footer">
                <div class="app-meta">
                    <span>${app.version}</span>
                    <span>${app.size}</span>
                    <span>${app.compatibility}</span>
                </div>
                <button class="download-btn" onclick="event.stopPropagation(); downloadApp(${app.id})">
                    <span class="material-icons-round">download</span>
                    Baixar
                </button>
            </div>
        </div>
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

// Show App Details Modal
function showAppDetails(appId) {
    const app = apps.find(a => a.id === appId);
    if (!app) return;
    
    // Populate modal content
    document.getElementById('modalTitle').textContent = 'Detalhes do Aplicativo';
    document.getElementById('modalAppName').textContent = app.name;
    document.getElementById('modalDeveloper').textContent = app.developer;
    document.getElementById('modalDescription').textContent = app.description;
    document.getElementById('modalVersion').textContent = app.version;
    document.getElementById('modalSize').textContent = app.size;
    document.getElementById('modalCompatibility').textContent = app.compatibility;
    document.getElementById('modalCategory').textContent = getCategoryName(app.category);
    document.getElementById('modalUpdated').textContent = formatDate(app.updatedAt);
    
    // Rating
    const stars = '★'.repeat(Math.floor(app.rating)) + '☆'.repeat(5 - Math.floor(app.rating));
    document.getElementById('modalRating').textContent = stars;
    document.getElementById('modalRatingText').textContent = `${app.rating} (${app.downloads}+ downloads)`;
    
    // Icon
    const modalIcon = document.getElementById('modalIcon');
    if (app.icon) {
        modalIcon.src = app.icon;
        modalIcon.style.display = 'block';
    } else {
        modalIcon.style.display = 'none';
    }
    
    // Screenshots
    const screenshotsContainer = document.getElementById('screenshotsContainer');
    if (app.screenshots && app.screenshots.length > 0) {
        screenshotsContainer.innerHTML = app.screenshots.map(screenshot => `
            <div class="screenshot">
                <img src="${screenshot}" alt="Screenshot" onclick="openImageModal('${screenshot}')">
            </div>
        `).join('');
    } else {
        screenshotsContainer.innerHTML = '<p style="color: #6c757d; text-align: center;">Nenhuma captura de tela disponível</p>';
    }
    
    // Download button
    const downloadBtn = document.getElementById('downloadBtn');
    downloadBtn.onclick = () => downloadApp(app.id);
    
    // Show modal
    appModal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

// Close Modal
function closeModal() {
    if (appModal) {
        appModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Download App
function downloadApp(appId) {
    const app = apps.find(a => a.id === appId);
    if (!app) return;
    
    // Create download link
    const link = document.createElement('a');
    link.href = app.apkUrl;
    link.download = `${app.name.replace(/\s+/g, '-').toLowerCase()}-v${app.version}.apk`;
    
    // Trigger download
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Show download notification
    showNotification(`Download de ${app.name} iniciado!`, 'success');
    
    // Update download count (in real app, this would be sent to server)
    app.downloads++;
}

// Share App
function shareApp() {
    const app = apps.find(a => a.id === getCurrentAppId());
    if (!app) return;
    
    if (navigator.share) {
        navigator.share({
            title: app.name,
            text: app.description,
            url: window.location.href
        });
    } else {
        // Fallback - copy to clipboard
        const text = `${app.name} - ${app.description}\n\nBaixe em: ${window.location.href}`;
        navigator.clipboard.writeText(text).then(() => {
            showNotification('Link copiado para a área de transferência!', 'success');
        });
    }
}

// Get Current App ID from Modal
function getCurrentAppId() {
    // This would be stored when opening the modal
    // For now, return the first app ID
    return apps.length > 0 ? apps[0].id : null;
}

// Format Date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
}

// Show Notification
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <span class="material-icons-round">${type === 'success' ? 'check_circle' : 'info'}</span>
        <span>${message}</span>
    `;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: ${type === 'success' ? '#4caf50' : '#2196f3'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        z-index: 10000;
        animation: slideInRight 0.3s ease-out;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    
    // Add animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
    
    // Add to page
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Open Image Modal (for screenshots)
function openImageModal(imageSrc) {
    const imageModal = document.createElement('div');
    imageModal.className = 'image-modal';
    imageModal.innerHTML = `
        <div class="image-modal-content">
            <img src="${imageSrc}" alt="Screenshot">
            <button class="image-modal-close" onclick="this.parentElement.parentElement.remove()">
                <span class="material-icons-round">close</span>
            </button>
        </div>
    `;
    
    imageModal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        cursor: pointer;
    `;
    
    const content = imageModal.querySelector('.image-modal-content');
    content.style.cssText = `
        position: relative;
        max-width: 90vw;
        max-height: 90vh;
        cursor: default;
    `;
    
    const img = content.querySelector('img');
    img.style.cssText = `
        max-width: 100%;
        max-height: 100%;
        border-radius: 8px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.3);
    `;
    
    const closeBtn = content.querySelector('.image-modal-close');
    closeBtn.style.cssText = `
        position: absolute;
        top: -40px;
        right: 0;
        background: rgba(255,255,255,0.2);
        border: none;
        color: white;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    `;
    
    imageModal.addEventListener('click', (e) => {
        if (e.target === imageModal) {
            imageModal.remove();
        }
    });
    
    document.body.appendChild(imageModal);
}

// Global function for featured apps
window.showAppDetails = showAppDetails;
