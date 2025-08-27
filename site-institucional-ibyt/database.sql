-- IBYT Store Database Schema
-- Execute este script para criar a estrutura do banco de dados

-- Usar o banco de dados correto
USE u454452574_ibyt;

-- Tabela de aplicativos
CREATE TABLE IF NOT EXISTS apps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    developer VARCHAR(255) DEFAULT 'IBYT Software',
    category ENUM('monitoramento', 'gestao', 'utilidades', 'comunicacao', 'produtividade') NOT NULL,
    description TEXT NOT NULL,
    version VARCHAR(50) NOT NULL,
    size VARCHAR(20) DEFAULT '0 MB',
    rating DECIMAL(2,1) DEFAULT 0.0,
    downloads INT DEFAULT 0,
    price DECIMAL(10,2) DEFAULT 0.00,
    featured BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'draft', 'inactive') DEFAULT 'draft',
    icon VARCHAR(500) DEFAULT '',
    screenshots JSON,
    apk_url VARCHAR(500) DEFAULT '',
    compatibility VARCHAR(100) DEFAULT 'Android 6.0+',
    tags TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_category (category),
    INDEX idx_status (status),
    INDEX idx_featured (featured),
    INDEX idx_downloads (downloads),
    INDEX idx_rating (rating)
);

-- Inserir dados de exemplo
INSERT INTO apps (
    name, category, description, version, size, rating, downloads, 
    price, featured, status, icon, screenshots, apk_url, 
    compatibility, tags
) VALUES 
(
    'Nível Certo',
    'monitoramento',
    'Sistema inteligente de monitoramento de reservatórios em tempo real. Receba alertas instantâneos e mantenha o controle total dos seus recursos hídricos.',
    '2.1.0',
    '5.2 MB',
    4.9,
    1250,
    0.00,
    TRUE,
    'active',
    'assets/img/app-icons/nivel-certo.png',
    JSON_ARRAY('assets/img/screenshots/nivel-certo-1.jpg', 'assets/img/screenshots/nivel-certo-2.jpg'),
    'downloads/nivel-certo-v2.1.0.apk',
    'Android 6.0+',
    'iot,sensores,monitoramento,água'
),
(
    'IBYT Monitor',
    'gestao',
    'Ferramenta completa para gestão e monitoramento de sistemas empresariais. Dashboard em tempo real com métricas avançadas.',
    '1.5.2',
    '8.7 MB',
    4.7,
    890,
    0.00,
    FALSE,
    'active',
    'assets/img/app-icons/ibyt-monitor.png',
    JSON_ARRAY('assets/img/screenshots/monitor-1.jpg', 'assets/img/screenshots/monitor-2.jpg'),
    'downloads/ibyt-monitor-v1.5.2.apk',
    'Android 7.0+',
    'gestão,dashboard,métricas,empresarial'
),
(
    'Sensor Config',
    'utilidades',
    'Utilitário para configuração rápida e fácil de sensores IoT. Interface intuitiva para setup de dispositivos.',
    '1.0.8',
    '3.1 MB',
    4.5,
    450,
    0.00,
    FALSE,
    'active',
    'assets/img/app-icons/sensor-config.png',
    JSON_ARRAY('assets/img/screenshots/config-1.jpg'),
    'downloads/sensor-config-v1.0.8.apk',
    'Android 6.0+',
    'configuração,sensores,iot,setup'
);

-- Tabela de downloads (para estatísticas)
CREATE TABLE IF NOT EXISTS downloads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    app_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    download_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (app_id) REFERENCES apps(id) ON DELETE CASCADE,
    INDEX idx_app_id (app_id),
    INDEX idx_download_date (download_date)
);

-- Tabela de avaliações
CREATE TABLE IF NOT EXISTS ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    app_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    user_name VARCHAR(255),
    user_email VARCHAR(255),
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (app_id) REFERENCES apps(id) ON DELETE CASCADE,
    INDEX idx_app_id (app_id),
    INDEX idx_rating (rating)
);

-- Tabela de categorias (para futuras expansões)
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(100),
    color VARCHAR(7) DEFAULT '#007bff',
    sort_order INT DEFAULT 0,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inserir categorias padrão
INSERT INTO categories (name, display_name, description, icon, color, sort_order) VALUES
('monitoramento', 'Monitoramento', 'Aplicativos para monitoramento e controle de sistemas', 'monitoring', '#007bff', 1),
('gestao', 'Gestão', 'Ferramentas de gestão empresarial e produtividade', 'business', '#28a745', 2),
('utilidades', 'Utilidades', 'Utilitários e ferramentas auxiliares', 'build', '#ffc107', 3),
('comunicacao', 'Comunicação', 'Aplicativos de comunicação e colaboração', 'chat', '#17a2b8', 4),
('produtividade', 'Produtividade', 'Ferramentas para aumentar a produtividade', 'trending_up', '#6f42c1', 5);

-- Tabela de administradores (para autenticação)
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255),
    role ENUM('admin', 'editor') DEFAULT 'editor',
    active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inserir usuário admin padrão (senha: admin123)
INSERT INTO admin_users (username, email, password_hash, full_name, role) VALUES
('admin', 'admin@ibyt.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador IBYT', 'admin');

-- View para estatísticas
CREATE OR REPLACE VIEW app_stats AS
SELECT 
    a.id,
    a.name,
    a.downloads,
    a.rating,
    COUNT(d.id) as total_downloads_today,
    COUNT(r.id) as total_ratings,
    AVG(r.rating) as avg_rating_calculated
FROM apps a
LEFT JOIN downloads d ON a.id = d.app_id AND DATE(d.download_date) = CURDATE()
LEFT JOIN ratings r ON a.id = r.app_id
GROUP BY a.id, a.name, a.downloads, a.rating;

-- Índices adicionais para performance
CREATE INDEX idx_apps_featured_status ON apps(featured, status);
CREATE INDEX idx_apps_category_status ON apps(category, status);
CREATE INDEX idx_downloads_date ON downloads(download_date);
CREATE INDEX idx_ratings_app_date ON ratings(app_id, created_at);
