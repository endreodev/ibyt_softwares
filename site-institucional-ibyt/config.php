<?php
/**
 * IBYT Store - Configurações do Banco de Dados
 * Arquivo centralizado para configurações do sistema
 */

// Configurações do Banco de Dados
define('DB_HOST', '62.72.62.1');
define('DB_NAME', 'u454452574_ibyt');
define('DB_USER', 'u454452574_ibyt');
define('DB_PASS', 'Terra@157');
define('DB_CHARSET', 'utf8mb4');

// Configurações de Upload
define('UPLOAD_DIR', '../uploads/');
define('DOWNLOAD_DIR', '../downloads/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_APK_TYPES', ['apk']);

// Configurações do Sistema
define('SITE_URL', 'http://localhost');
define('ADMIN_EMAIL', 'endreo.dev@gmail.com');
define('SITE_NAME', 'IBYT Store');
define('COMPANY_NAME', 'IBYT Software');

// Configurações de Segurança
define('ADMIN_SESSION_NAME', 'ibyt_admin_session');
define('SESSION_TIMEOUT', 3600); // 1 hora em segundos

// Função para conectar ao banco de dados
function getDbConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Erro de conexão com o banco de dados");
        }
    }
    
    return $pdo;
}

// Função para verificar se o banco existe e está configurado
function checkDatabaseConnection() {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->query("SELECT COUNT(*) FROM apps LIMIT 1");
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Função para formatar tamanho de arquivo
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// Função para sanitizar dados
function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Função para validar upload de arquivo
function validateFileUpload($file, $allowedTypes) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Erro no upload do arquivo');
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        throw new Exception('Arquivo muito grande. Máximo: ' . formatFileSize(MAX_FILE_SIZE));
    }
    
    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension']);
    
    if (!in_array($extension, $allowedTypes)) {
        throw new Exception('Tipo de arquivo não permitido');
    }
    
    return $extension;
}

// Headers de segurança
function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Verificar se é requisição AJAX
function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}
?>
