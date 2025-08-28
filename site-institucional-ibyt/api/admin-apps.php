<?php
// Verificar autenticação administrativa
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Acesso não autorizado']);
    exit();
}

// Incluir configurações centralizadas
require_once '../config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Definir headers de segurança
setSecurityHeaders();

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Simple authentication check (replace with proper authentication)
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    // For demo purposes, we'll skip authentication
    // In production, implement proper authentication
    $_SESSION['admin_logged_in'] = true;
}

try {
    // Conectar ao banco de dados usando a configuração centralizada
    $pdo = getDbConnection();
    
    // Get all apps (including drafts and inactive)
    $stmt = $pdo->prepare("
        SELECT 
            id, name, developer, category, description, version, size,
            rating, downloads, price, featured, status, icon, screenshots,
            apk_url, compatibility, created_at, updated_at, tags
        FROM apps 
        ORDER BY created_at DESC
    ");
    
    $stmt->execute();
    $apps = $stmt->fetchAll();
    
    // Process data
    foreach ($apps as &$app) {
        // Convert screenshots from JSON string to array
        $app['screenshots'] = !empty($app['screenshots']) ? json_decode($app['screenshots'], true) : [];
        
        // Convert tags from comma-separated string to array
        $app['tags'] = !empty($app['tags']) ? explode(',', $app['tags']) : [];
        
        // Convert boolean fields
        $app['featured'] = (bool) $app['featured'];
        
        // Format numeric fields
        $app['rating'] = (float) $app['rating'];
        $app['downloads'] = (int) $app['downloads'];
        $app['price'] = (float) $app['price'];
        
        // Format dates
        $app['created_at'] = date('Y-m-d', strtotime($app['created_at']));
        $app['updated_at'] = date('Y-m-d', strtotime($app['updated_at']));
    }
    
    echo json_encode($apps);
    
} catch (PDOException $e) {
    // Database connection failed, return demo data
    error_log("Database error in admin-apps.php: " . $e->getMessage());
    
    $demo_apps = [
        [
            'id' => 1,
            'name' => 'Nível Certo',
            'developer' => 'IBYT Software',
            'category' => 'monitoramento',
            'description' => 'Sistema inteligente de monitoramento de reservatórios em tempo real.',
            'version' => '2.1.0',
            'size' => '5.2 MB',
            'rating' => 4.9,
            'downloads' => 1250,
            'price' => 0.0,
            'featured' => true,
            'status' => 'active',
            'icon' => 'assets/img/app-icons/nivel-certo.png',
            'screenshots' => ['screenshot1.jpg', 'screenshot2.jpg'],
            'apk_url' => 'downloads/nivel-certo-v2.1.0.apk',
            'compatibility' => 'Android 6.0+',
            'created_at' => '2025-01-15',
            'updated_at' => '2025-08-20',
            'tags' => ['iot', 'sensores', 'monitoramento', 'água']
        ],
        [
            'id' => 2,
            'name' => 'IBYT Monitor',
            'developer' => 'IBYT Software',
            'category' => 'gestao',
            'description' => 'Ferramenta completa para gestão e monitoramento de sistemas empresariais.',
            'version' => '1.5.2',
            'size' => '8.7 MB',
            'rating' => 4.7,
            'downloads' => 890,
            'price' => 0.0,
            'featured' => false,
            'status' => 'active',
            'icon' => 'assets/img/app-icons/ibyt-monitor.png',
            'screenshots' => ['monitor1.jpg'],
            'apk_url' => 'downloads/ibyt-monitor-v1.5.2.apk',
            'compatibility' => 'Android 7.0+',
            'created_at' => '2025-02-10',
            'updated_at' => '2025-08-15',
            'tags' => ['gestão', 'dashboard', 'métricas']
        ],
        [
            'id' => 3,
            'name' => 'App Demo',
            'developer' => 'IBYT Software',
            'category' => 'utilidades',
            'description' => 'Aplicativo em desenvolvimento para testes.',
            'version' => '0.1.0',
            'size' => '2.1 MB',
            'rating' => 0.0,
            'downloads' => 0,
            'price' => 0.0,
            'featured' => false,
            'status' => 'draft',
            'icon' => '',
            'screenshots' => [],
            'apk_url' => '',
            'compatibility' => 'Android 6.0+',
            'created_at' => '2025-08-25',
            'updated_at' => '2025-08-25',
            'tags' => ['teste', 'desenvolvimento']
        ]
    ];
    
    echo json_encode($demo_apps);
    
} catch (Exception $e) {
    error_log("General error in admin-apps.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor']);
}
?>
