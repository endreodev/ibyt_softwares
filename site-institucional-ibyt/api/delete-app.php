<?php
// Verificar autenticação administrativa
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Acesso não autorizado']);
    exit();
}

require_once '../config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Security headers
setSecurityHeaders();

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Allow POST with _method override for broader compatibility
$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, ['DELETE', 'POST'])) {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}
// Normalize method when POST with override
if ($method === 'POST' && isset($_POST['_method']) && strtoupper($_POST['_method']) === 'DELETE') {
    $method = 'DELETE';
}

// Simple authentication check (placeholder)
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    // In production, validate proper admin authentication
    $_SESSION['admin_logged_in'] = true; // demo fallback
}

try {
    // Get app ID from query parameter, POST body or JSON
    $app_id = null;
    if (isset($_GET['id'])) {
        $app_id = intval($_GET['id']);
    } elseif (isset($_POST['id'])) {
        $app_id = intval($_POST['id']);
    } else {
        $raw = file_get_contents('php://input');
        if ($raw) {
            $json = json_decode($raw, true);
            if (isset($json['id'])) {
                $app_id = intval($json['id']);
            }
        }
    }
    
    if (!$app_id) {
        throw new Exception('ID do aplicativo não fornecido');
    }
    
    try {
        // Connect to database using centralized config
        $pdo = getDbConnection();
        
        // First, get the app info to delete associated files
        $stmt = $pdo->prepare("SELECT icon, apk_url, screenshots FROM apps WHERE id = ?");
        $stmt->execute([$app_id]);
        $app = $stmt->fetch();
        
        if (!$app) {
            throw new Exception('Aplicativo não encontrado');
        }
        
        // Delete the app from database
        $stmt = $pdo->prepare("DELETE FROM apps WHERE id = ?");
        $result = $stmt->execute([$app_id]);
        
    if ($result) {
            // Delete associated files
            deleteAppFiles($app);
            
            echo json_encode([
                'success' => true,
                'message' => 'Aplicativo excluído com sucesso!'
            ]);
        } else {
            throw new Exception('Erro ao excluir aplicativo do banco de dados');
        }
        
    } catch (PDOException $e) {
        // Database error
        error_log("Database error in delete-app.php: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erro de banco de dados ao excluir o aplicativo'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error in delete-app.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Helper function to delete app files
function deleteAppFiles($app) {
    $base_path = '../';
    
    // Delete icon
    if (!empty($app['icon']) && file_exists($base_path . $app['icon'])) {
        unlink($base_path . $app['icon']);
    }
    
    // Delete APK
    if (!empty($app['apk_url']) && file_exists($base_path . $app['apk_url'])) {
        unlink($base_path . $app['apk_url']);
    }
    
    // Delete screenshots
    if (!empty($app['screenshots'])) {
        $screenshots = json_decode($app['screenshots'], true);
        if (is_array($screenshots)) {
            foreach ($screenshots as $screenshot) {
                if (file_exists($base_path . $screenshot)) {
                    unlink($base_path . $screenshot);
                }
            }
        }
    }
}
?>
