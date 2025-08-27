<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IBYT Store - Aplicativos Profissionais | IBYT Software</title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="Loja oficial de aplicativos IBYT Software. Baixe nossos apps profissionais para monitoramento, controle e gestão empresarial.">
    <meta name="keywords" content="aplicativos, apps, IBYT, loja, download, APK, monitoramento, gestão empresarial">
    <meta name="author" content="IBYT Software">
    
    <!-- Open Graph -->
    <meta property="og:title" content="IBYT Store - Aplicativos Profissionais">
    <meta property="og:description" content="Baixe aplicativos profissionais desenvolvidos pela IBYT Software">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://www.ibyt.com.br/loja.php">
    <meta property="og:image" content="assets/img/og-image.jpg">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="assets/img/favicon-32x32.png">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="assets/css/enhanced-professional.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <link rel="stylesheet" href="assets/css/store-fixed.css">
</head>

<body>
    <!-- Fixed Header -->
    <header class="professional-header" id="header">
        <div class="header-content">
            <div class="logo-section">
                <img src="assets/img/logo.png" alt="IBYT Software" width="110" height="auto">
                <span class="logo-text">IBYT Store</span>
            </div>
                
                <nav class="main-navigation" id="navigation">
                    <a href="index.php" class="nav-link">
                        <span class="material-icons-round">home</span>
                        Início
                    </a>
                    <a href="index.php#about" class="nav-link">Sobre</a>
                    <a href="index.php#services" class="nav-link">Serviços</a>
                    <a href="nivel-certo.php" class="nav-link">Nível Certo</a>
                    <a href="loja.php" class="nav-link active">Loja</a>
                    <a href="admin-loja.php" class="nav-link admin-link">
                        <span class="material-icons-round">admin_panel_settings</span>
                        Admin
                    </a>
                    <a href="index.php#contact" class="nav-link">Contato</a>
                </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Hero Section -->
        <section class="hero-store">
            <div class="container">
                <div class="hero-content">
                    <h1>
                        <span class="material-icons-round hero-icon">smartphone</span>
                        IBYT Store
                    </h1>
                    <p>Aplicativos profissionais desenvolvidos especialmente para sua empresa</p>
                    
                    <!-- Search Bar -->
                    <div class="search-container">
                        <div class="search-box">
                            <span class="material-icons-round">search</span>
                            <input type="text" id="searchInput" placeholder="Buscar aplicativos...">
                        </div>
                        
                        <!-- Filter Buttons -->
                        <div class="filter-buttons">
                            <button class="filter-btn active" data-category="all">
                                <span class="material-icons-round">apps</span>
                                Todos
                            </button>
                            <button class="filter-btn" data-category="monitoramento">
                                <span class="material-icons-round">monitoring</span>
                                Monitoramento
                            </button>
                            <button class="filter-btn" data-category="gestao">
                                <span class="material-icons-round">business</span>
                                Gestão
                            </button>
                            <button class="filter-btn" data-category="utilidades">
                                <span class="material-icons-round">build</span>
                                Utilidades
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Apps Grid -->
        <section class="apps-section">
            <div class="container">
                <div class="section-header">
                    <h2>Aplicativos Disponíveis</h2>
                    <div class="view-toggle">
                        <button class="view-btn active" data-view="grid">
                            <span class="material-icons-round">grid_view</span>
                        </button>
                        <button class="view-btn" data-view="list">
                            <span class="material-icons-round">view_list</span>
                        </button>
                    </div>
                </div>
                
                <!-- Loading -->
                <div id="loadingApps" class="loading-container">
                    <div class="loading-spinner"></div>
                    <p>Carregando aplicativos...</p>
                </div>
                
                <!-- Apps Container -->
                <div id="appsContainer" class="apps-grid" style="display: none;">
                    <!-- Apps will be loaded here via JavaScript -->
                </div>
                
                <!-- Empty State -->
                <div id="emptyState" class="empty-state" style="display: none;">
                    <span class="material-icons-round">search_off</span>
                    <h3>Nenhum aplicativo encontrado</h3>
                    <p>Tente ajustar os filtros ou termos de busca</p>
                </div>
            </div>
        </section>

        <!-- Featured Apps -->
        <section class="featured-section">
            <div class="container">
                <h2>
                    <span class="material-icons-round">star</span>
                    Aplicativos em Destaque
                </h2>
                <div class="featured-grid">
                    <div class="featured-app">
                        <div class="app-icon">
                            <span class="material-icons-round">water_drop</span>
                        </div>
                        <div class="app-info">
                            <h3>Nível Certo App</h3>
                            <p>Monitore seus reservatórios em tempo real</p>
                            <div class="app-rating">
                                <span class="stars">★★★★★</span>
                                <span class="rating-text">4.9 (127 avaliações)</span>
                            </div>
                        </div>
                        <button class="download-btn" onclick="showAppDetails('nivel-certo')">
                            <span class="material-icons-round">download</span>
                            Baixar
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- App Details Modal -->
    <div id="appModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Detalhes do Aplicativo</h2>
                <button class="close-btn" onclick="closeModal()">
                    <span class="material-icons-round">close</span>
                </button>
            </div>
            
            <div class="modal-body">
                <div class="app-details">
                    <div class="app-icon-large">
                        <img id="modalIcon" src="" alt="App Icon">
                    </div>
                    
                    <div class="app-info-detailed">
                        <h3 id="modalAppName">Nome do App</h3>
                        <p id="modalDeveloper">IBYT Software</p>
                        
                        <div class="app-rating-detailed">
                            <span id="modalRating" class="stars">★★★★★</span>
                            <span id="modalRatingText" class="rating-text">4.9</span>
                            <span class="downloads">1.000+ downloads</span>
                        </div>
                        
                        <div class="app-tags">
                            <span id="modalCategory" class="tag">Monitoramento</span>
                            <span class="tag">Gratuito</span>
                        </div>
                    </div>
                </div>
                
                <div class="app-screenshots">
                    <h4>Capturas de Tela</h4>
                    <div id="screenshotsContainer" class="screenshots-grid">
                        <!-- Screenshots will be loaded here -->
                    </div>
                </div>
                
                <div class="app-description">
                    <h4>Descrição</h4>
                    <p id="modalDescription">Descrição do aplicativo...</p>
                </div>
                
                <div class="app-details-grid">
                    <div class="detail-item">
                        <span class="label">Versão:</span>
                        <span id="modalVersion" class="value">1.0.0</span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Tamanho:</span>
                        <span id="modalSize" class="value">5.2 MB</span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Atualizado:</span>
                        <span id="modalUpdated" class="value">20/08/2025</span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Compatibilidade:</span>
                        <span id="modalCompatibility" class="value">Android 6.0+</span>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button id="downloadBtn" class="download-btn-large">
                    <span class="material-icons-round">download</span>
                    Baixar APK
                </button>
                <button class="share-btn" onclick="shareApp()">
                    <span class="material-icons-round">share</span>
                    Compartilhar
                </button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer-professional">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-logo">
                        <img src="assets/img/logo.png" alt="IBYT Software" width="110" height="auto">
                        <span>IBYT Software</span>
                    </div>
                    <p>Soluções profissionais em tecnologia da informação</p>
                </div>
                
                <div class="footer-section">
                    <h4>IBYT Store</h4>
                    <ul>
                        <li><a href="#aplicativos">Aplicativos</a></li>
                        <li><a href="#categorias">Categorias</a></li>
                        <li><a href="#desenvolvedores">Para Desenvolvedores</a></li>
                        <li><a href="admin-loja.php">Área Admin</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Suporte</h4>
                    <ul>
                        <li><a href="#ajuda">Central de Ajuda</a></li>
                        <li><a href="#contato">Contato</a></li>
                        <li><a href="privacy.php">Política de Privacidade</a></li>
                        <li><a href="#termos">Termos de Uso</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Contato</h4>
                    <div class="contact-info">
                        <p><span class="material-icons-round">email</span> contato@ibyt.com.br</p>
                        <p><span class="material-icons-round">phone</span> (65) 98171-9837</p>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 IBYT Software. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <!-- WhatsApp Button -->
    <a href="https://wa.me/5565981719837?text=Olá! Tenho interesse nos aplicativos da IBYT Store." 
       class="whatsapp-button whatsapp-blink" target="_blank" rel="noopener noreferrer">
        <span class="material-icons-round">chat</span>
    </a>

    <!-- Scripts -->
    
    <script src="assets/js/professional.js"></script>
    <script src="assets/js/enhanced-interactions.js"></script>
    <script src="assets/js/store.js"></script>
</body>
</html>
