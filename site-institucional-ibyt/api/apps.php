<?php
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

try {
    // Conectar ao banco de dados usando a configuração centralizada
    $pdo = getDbConnection();
    
    // Get apps from database
    $stmt = $pdo->prepare("
        SELECT 
            id, name, developer, category, description, version, size,
            rating, downloads, price, featured, status, icon, screenshots,
            apk_url, compatibility, updated_at, tags
        FROM apps 
        WHERE status = 'active'
        ORDER BY featured DESC, downloads DESC, name ASC
    ");
    
    $stmt->execute();
    $apps = $stmt->fetchAll();
    
    // Determine request scheme safely (fallback to http)
    $scheme = 'http';
    if (!empty($_SERVER['REQUEST_SCHEME'])) {
        $scheme = $_SERVER['REQUEST_SCHEME'];
    } elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $scheme = 'https';
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'];
    }

    // Process screenshots and tags
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
        
        // Ensure URLs are absolute
        if (!empty($app['icon']) && !filter_var($app['icon'], FILTER_VALIDATE_URL)) {
            $app['icon'] = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/' . ltrim($app['icon'], '/');
        }
        
        if (!empty($app['apk_url']) && !filter_var($app['apk_url'], FILTER_VALIDATE_URL)) {
            $app['apk_url'] = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/' . ltrim($app['apk_url'], '/');
        }
        
        // Process screenshot URLs
        foreach ($app['screenshots'] as &$screenshot) {
            if (!filter_var($screenshot, FILTER_VALIDATE_URL)) {
                $screenshot = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/' . ltrim($screenshot, '/');
            }
        }
    }
    
    // Return apps
    echo json_encode($apps);
    
} catch (PDOException $e) {
    // Database connection failed, return demo data
    error_log("Database error in apps.php: " . $e->getMessage());
    
    $demo_apps = [
        [
            'id' => 1,
            'name' => 'Nível Certo',
            'developer' => 'IBYT Software',
            'category' => 'monitoramento',
            'description' => 'Sistema inteligente de monitoramento de reservatórios em tempo real. Receba alertas instantâneos e mantenha o controle total dos seus recursos hídricos.',
            'version' => '2.1.0',
            'size' => '5.2 MB',
            'rating' => 4.9,
            'downloads' => 1250,
            'price' => 0.0,
            'featured' => true,
            'status' => 'active',
            'icon' => 'assets/img/app-icons/nivel-certo.png',
            'screenshots' => [
                'assets/img/screenshots/nivel-certo-1.jpg',
                'assets/img/screenshots/nivel-certo-2.jpg'
            ],
            'apk_url' => 'downloads/nivel-certo-v2.1.0.apk',
            'compatibility' => 'Android 6.0+',
            'updated_at' => '2025-08-20',
            'tags' => ['iot', 'sensores', 'monitoramento', 'água']
        ],
        [
            'id' => 2,
            'name' => 'IBYT Monitor',
            'developer' => 'IBYT Software',
            'category' => 'gestao',
            'description' => 'Ferramenta completa para gestão e monitoramento de sistemas empresariais. Dashboard em tempo real com métricas avançadas.',
            'version' => '1.5.2',
            'size' => '8.7 MB',
            'rating' => 4.7,
            'downloads' => 890,
            'price' => 0.0,
            'featured' => false,
            'status' => 'active',
            'icon' => 'assets/img/app-icons/ibyt-monitor.png',
            'screenshots' => [
                'assets/img/screenshots/monitor-1.jpg',
                'assets/img/screenshots/monitor-2.jpg'
            ],
            'apk_url' => 'downloads/ibyt-monitor-v1.5.2.apk',
            'compatibility' => 'Android 7.0+',
            'updated_at' => '2025-08-15',
            'tags' => ['gestão', 'dashboard', 'métricas', 'empresarial']
        ],
        [
            'id' => 3,
            'name' => 'Sensor Config',
            'developer' => 'IBYT Software',
            'category' => 'utilidades',
            'description' => 'Utilitário para configuração rápida e fácil de sensores IoT. Interface intuitiva para setup de dispositivos.',
            'version' => '1.0.8',
            'size' => '3.1 MB',
            'rating' => 4.5,
            'downloads' => 450,
            'price' => 0.0,
            'featured' => false,
            'status' => 'active',
            'icon' => 'assets/img/app-icons/sensor-config.png',
            'screenshots' => [
                'assets/img/screenshots/config-1.jpg'
            ],
            'apk_url' => 'downloads/sensor-config-v1.0.8.apk',
            'compatibility' => 'Android 6.0+',
            'updated_at' => '2025-08-10',
            'tags' => ['configuração', 'sensores', 'iot', 'setup']
        ]
    ];
    
    echo json_encode($demo_apps);
    
} catch (Exception $e) {
    // General error
    error_log("General error in apps.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor']);
}
?>
