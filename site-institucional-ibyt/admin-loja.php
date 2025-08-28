<?php
// Verificar autenticação administrativa
require_once 'admin-auth.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - IBYT Store | Gerenciamento de Aplicativos</title>
    
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
    <link rel="stylesheet" href="assets/css/admin-store.css">
</head>

<body>
    <!-- Fixed Header -->
    <header class="professional-header" id="header">
            <div class="header-content">
                <div class="logo-section">
                    <img src="assets/img/logo.png" alt="IBYT Software" width="110" height="auto">
                    <span class="logo-text">IBYT Store Admin</span>
                </div>
                
                <nav class="main-navigation" id="navigation">
                    <a href="loja.php" class="nav-link">
                        <span class="material-icons-round">storefront</span>
                        Loja
                    </a>
                    <a href="#dashboard" class="nav-link active">Dashboard</a>
                    <a href="#apps" class="nav-link">Aplicativos</a>
                    <a href="#analytics" class="nav-link">Analytics</a>
                    <a href="index.php" class="nav-link">
                        <span class="material-icons-round">home</span>
                        Site Principal
                    </a>
                </nav>

                <div class="header-actions">
                    <div class="admin-user-info">
                        <span class="material-icons-round">admin_panel_settings</span>
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['admin_user']); ?></span>
                    </div>
                    <a href="logout.php" class="logout-btn" title="Sair">
                        <span class="material-icons-round">logout</span>
                        <span class="logout-text">Sair</span>
                    </a>
                    <button class="mobile-menu-toggle" id="mobile-menu-toggle" aria-label="Alternar menu">
                        <span class="material-icons-round">menu</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Mobile Navigation -->
    <nav class="mobile-navigation" id="mobile-navigation">
        <div class="mobile-nav-content">
            <div class="mobile-nav-header">
                <span class="logo-text">IBYT Admin</span>
                <button class="mobile-nav-close" id="mobile-nav-close">
                    <span class="material-icons-round">close</span>
                </button>
            </div>
            <div class="mobile-nav-links">
                <a href="#dashboard" class="mobile-nav-link">Dashboard</a>
                <a href="#apps" class="mobile-nav-link">Aplicativos</a>
                <a href="#analytics" class="mobile-nav-link">Analytics</a>
                <a href="loja.php" class="mobile-nav-link">
                    <span class="material-icons-round">store</span>
                    Loja
                </a>
                <a href="index.php" class="mobile-nav-link">
                    <span class="material-icons-round">home</span>
                    Site Principal
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Dashboard Overview -->
        <section id="dashboard" class="dashboard-section">
            <div class="container-professional">
                <div class="dashboard-header">
                    <h1>
                        <span class="material-icons-round">dashboard</span>
                        Dashboard
                    </h1>
                    <button class="add-app-btn" onclick="showAddAppModal()">
                        <span class="material-icons-round">add</span>
                        Adicionar Aplicativo
                    </button>
                </div>
                
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <span class="material-icons-round">apps</span>
                        </div>
                        <div class="stat-content">
                            <h3 id="totalApps">0</h3>
                            <p>Total de Apps</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <span class="material-icons-round">download</span>
                        </div>
                        <div class="stat-content">
                            <h3 id="totalDownloads">0</h3>
                            <p>Downloads</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <span class="material-icons-round">star</span>
                        </div>
                        <div class="stat-content">
                            <h3 id="avgRating">0.0</h3>
                            <p>Avaliação Média</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <span class="material-icons-round">trending_up</span>
                        </div>
                        <div class="stat-content">
                            <h3 id="activeApps">0</h3>
                            <p>Apps Ativos</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Apps Management -->
        <section id="apps" class="apps-management-section">
            <div class="container-professional">
                <div class="section-header">
                    <h2>Gerenciar Aplicativos</h2>
                    <div class="management-actions">
                        <div class="search-box">
                            <span class="material-icons-round">search</span>
                            <input type="text" id="adminSearchInput" placeholder="Buscar aplicativos...">
                        </div>
                        <select id="statusFilter" class="filter-select">
                            <option value="all">Todos os Status</option>
                            <option value="active">Ativo</option>
                            <option value="draft">Rascunho</option>
                            <option value="inactive">Inativo</option>
                        </select>
                    </div>
                </div>
                
                <!-- Apps Table -->
                <div class="apps-table-container">
                    <table class="apps-table">
                        <thead>
                            <tr>
                                <th>Aplicativo</th>
                                <th>Categoria</th>
                                <th>Versão</th>
                                <th>Status</th>
                                <th>Downloads</th>
                                <th>Avaliação</th>
                                <th>Última Atualização</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="appsTableBody">
                            <!-- Apps will be loaded here -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Empty State -->
                <div id="adminEmptyState" class="empty-state" style="display: none;">
                    <span class="material-icons-round">inbox</span>
                    <h3>Nenhum aplicativo encontrado</h3>
                    <p>Adicione seu primeiro aplicativo para começar</p>
                    <button class="add-app-btn" onclick="showAddAppModal()">
                        <span class="material-icons-round">add</span>
                        Adicionar Primeiro App
                    </button>
                </div>
            </div>
        </section>
    </main>

    <!-- Add/Edit App Modal -->
    <div id="appFormModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h2 id="formModalTitle">Adicionar Aplicativo</h2>
                <button class="close-btn" onclick="closeAppFormModal()">
                    <span class="material-icons-round">close</span>
                </button>
            </div>
            
            <form id="appForm" class="app-form" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <!-- Basic Info -->
                    <div class="form-section">
                        <h3>Informações Básicas</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="appName">Nome do Aplicativo *</label>
                                <input type="text" id="appName" name="name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="appCategory">Categoria *</label>
                                <select id="appCategory" name="category" required>
                                    <option value="">Selecione uma categoria</option>
                                    <option value="monitoramento">Monitoramento</option>
                                    <option value="gestao">Gestão</option>
                                    <option value="utilidades">Utilidades</option>
                                    <option value="comunicacao">Comunicação</option>
                                    <option value="produtividade">Produtividade</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="appDescription">Descrição *</label>
                            <textarea id="appDescription" name="description" rows="4" required></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="appVersion">Versão *</label>
                                <input type="text" id="appVersion" name="version" placeholder="1.0.0" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="appDeveloper">Desenvolvedor</label>
                                <input type="text" id="appDeveloper" name="developer" value="IBYT Software">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Files -->
                    <div class="form-section">
                        <h3>Arquivos</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="appIcon">Ícone do App *</label>
                                <div class="file-upload">
                                    <input type="file" id="appIcon" name="icon" accept="image/*" required>
                                    <div class="file-upload-placeholder">
                                        <span class="material-icons-round">image</span>
                                        <span>Clique para selecionar ícone (512x512px)</span>
                                    </div>
                                    <div class="file-preview" id="iconPreview"></div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="appApk">Arquivo APK *</label>
                                <div class="file-upload">
                                    <input type="file" id="appApk" name="apk" accept=".apk" required>
                                    <div class="file-upload-placeholder">
                                        <span class="material-icons-round">android</span>
                                        <span>Clique para selecionar arquivo APK</span>
                                    </div>
                                    <div class="file-preview" id="apkPreview"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="appScreenshots">Screenshots</label>
                            <div class="file-upload">
                                <input type="file" id="appScreenshots" name="screenshots[]" accept="image/*" multiple>
                                <div class="file-upload-placeholder">
                                    <span class="material-icons-round">photo_library</span>
                                    <span>Clique para selecionar screenshots (máx. 5 imagens)</span>
                                </div>
                                <div class="file-preview" id="screenshotsPreview"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Additional Info -->
                    <div class="form-section">
                        <h3>Informações Adicionais</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="appCompatibility">Compatibilidade</label>
                                <input type="text" id="appCompatibility" name="compatibility" placeholder="Android 6.0+">
                            </div>
                            
                            <div class="form-group">
                                <label for="appStatus">Status</label>
                                <select id="appStatus" name="status">
                                    <option value="draft">Rascunho</option>
                                    <option value="active">Ativo</option>
                                    <option value="inactive">Inativo</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="appPrice">Preço (R$)</label>
                                <input type="number" id="appPrice" name="price" min="0" step="0.01" placeholder="0.00">
                            </div>
                            
                            <div class="form-group">
                                <label for="appFeatured">
                                    <input type="checkbox" id="appFeatured" name="featured">
                                    Aplicativo em destaque
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="appTags">Tags (separadas por vírgula)</label>
                            <input type="text" id="appTags" name="tags" placeholder="monitoramento, iot, sensores">
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeAppFormModal()">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-primary">
                        <span class="material-icons-round">save</span>
                        Salvar Aplicativo
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="modal">
        <div class="modal-content small">
            <div class="modal-header">
                <h2>Confirmar Exclusão</h2>
                <button class="close-btn" onclick="closeDeleteModal()">
                    <span class="material-icons-round">close</span>
                </button>
            </div>
            
            <div class="modal-body">
                <div class="delete-warning">
                    <span class="material-icons-round">warning</span>
                    <p>Tem certeza que deseja excluir este aplicativo?</p>
                    <p><strong id="deleteAppName">Nome do App</strong></p>
                    <p class="warning-text">Esta ação não pode ser desfeita.</p>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeDeleteModal()">
                    Cancelar
                </button>
                <button type="button" class="btn-danger" onclick="confirmDelete()">
                    <span class="material-icons-round">delete</span>
                    Excluir
                </button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/professional.js"></script>
    <script src="assets/js/enhanced-interactions.js"></script>
    <script src="assets/js/admin-store.js"></script>
</body>
</html>
