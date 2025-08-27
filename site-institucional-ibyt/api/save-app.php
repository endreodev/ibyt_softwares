<?php
// Incluir configurações centralizadas
require_once '../config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Definir headers de segurança
setSecurityHeaders();

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Simple authentication check
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    $_SESSION['admin_logged_in'] = true; // For demo
}

try {
    // Validate required fields
    $required_fields = ['name', 'category', 'description', 'version'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Campo obrigatório: {$field}");
        }
    }
    
    // Sanitize input data
    $data = [
        'name' => trim($_POST['name']),
        'category' => trim($_POST['category']),
        'description' => trim($_POST['description']),
        'version' => trim($_POST['version']),
        'developer' => trim($_POST['developer'] ?? 'IBYT Software'),
        'compatibility' => trim($_POST['compatibility'] ?? 'Android 6.0+'),
        'status' => trim($_POST['status'] ?? 'draft'),
        'price' => floatval($_POST['price'] ?? 0),
        'featured' => isset($_POST['featured']),
        'tags' => trim($_POST['tags'] ?? '')
    ];
    
    // Handle file uploads
    // Resolve absolute filesystem path to /uploads based on project root
    $project_root = realpath(dirname(__DIR__));
    $upload_dir_fs = $project_root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
    $icon_path = '';
    $apk_path = '';
    $screenshots = [];
    
    // Create upload directory if it doesn't exist
    if (!is_dir($upload_dir_fs)) {
        mkdir($upload_dir_fs, 0755, true);
    }
    
    // Handle icon upload
    if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
        try {
            $extension = validateFileUpload($_FILES['icon'], ALLOWED_IMAGE_TYPES);
            $icon_name = 'icon_' . time() . '.' . $extension;
            $icon_target = $upload_dir_fs . $icon_name;
            
            if (move_uploaded_file($_FILES['icon']['tmp_name'], $icon_target)) {
                $icon_path = 'uploads/' . $icon_name;
            }
        } catch (Exception $e) {
            throw new Exception('Erro no upload do ícone: ' . $e->getMessage());
        }
    }
    
    // Handle APK upload
    if (isset($_FILES['apk']) && $_FILES['apk']['error'] === UPLOAD_ERR_OK) {
        try {
            $extension = validateFileUpload($_FILES['apk'], ALLOWED_APK_TYPES);
            $apk_name = 'apk_' . time() . '.' . $extension;
            $apk_target = $upload_dir_fs . $apk_name;
            
            if (move_uploaded_file($_FILES['apk']['tmp_name'], $apk_target)) {
                $apk_path = 'uploads/' . $apk_name;
                
                // Get APK file size
                $data['size'] = formatFileSize(filesize($apk_target));
            }
        } catch (Exception $e) {
            throw new Exception('Erro no upload do APK: ' . $e->getMessage());
        }
    }
    
    // Handle screenshots upload
    if (isset($_FILES['screenshots']) && is_array($_FILES['screenshots']['name'])) {
        $screenshot_count = count($_FILES['screenshots']['name']);
        
        for ($i = 0; $i < min($screenshot_count, 5); $i++) {
            if ($_FILES['screenshots']['error'][$i] === UPLOAD_ERR_OK) {
                try {
                    // Create a temporary file array for validation
                    $temp_file = [
                        'name' => $_FILES['screenshots']['name'][$i],
                        'tmp_name' => $_FILES['screenshots']['tmp_name'][$i],
                        'size' => $_FILES['screenshots']['size'][$i],
                        'error' => $_FILES['screenshots']['error'][$i]
                    ];
                    
                    $extension = validateFileUpload($temp_file, ALLOWED_IMAGE_TYPES);
                    $screenshot_name = 'screenshot_' . time() . '_' . $i . '.' . $extension;
                    $screenshot_target = $upload_dir_fs . $screenshot_name;
                    
                    if (move_uploaded_file($_FILES['screenshots']['tmp_name'][$i], $screenshot_target)) {
                        $screenshots[] = 'uploads/' . $screenshot_name;
                    }
                } catch (Exception $e) {
                    // Log error but continue with other screenshots
                    error_log('Screenshot upload error: ' . $e->getMessage());
                }
            }
        }
    }
    
    // Database configuration
    $conn = getDbConnection();
    
    // Try to save to database
    try {
        
        // Check if we're updating an existing app
        $app_id = isset($_POST['app_id']) ? intval($_POST['app_id']) : null;
        
        if ($app_id) {
            // Update existing app
            $sql = "UPDATE apps SET 
                       name = ?, category = ?, description = ?, version = ?,
                       developer = ?, compatibility = ?, status = ?, price = ?,
                       featured = ?, tags = ?, updated_at = NOW()";
            
            $params = [
                $data['name'], $data['category'], $data['description'], $data['version'],
                $data['developer'], $data['compatibility'], $data['status'], $data['price'],
                $data['featured'], $data['tags']
            ];
            
            // Add file paths if uploaded
            if ($icon_path) {
                $sql .= ", icon = ?";
                $params[] = $icon_path;
            }
            
            if ($apk_path) {
                $sql .= ", apk_url = ?";
                $params[] = $apk_path;
            }
            
            if ($data['size'] ?? false) {
                $sql .= ", size = ?";
                $params[] = $data['size'];
            }
            
            if (!empty($screenshots)) {
                $sql .= ", screenshots = ?";
                $params[] = json_encode($screenshots);
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $app_id;
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
        } else {
            // Insert new app
            $stmt = $conn->prepare("
                INSERT INTO apps (
                    name, category, description, version, developer, compatibility,
                    status, price, featured, tags, icon, apk_url, size, screenshots,
                    rating, downloads, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, NOW(), NOW())
            ");
            
            $stmt->execute([
                $data['name'], $data['category'], $data['description'], $data['version'],
                $data['developer'], $data['compatibility'], $data['status'], $data['price'],
                $data['featured'], $data['tags'], $icon_path, $apk_path, 
                $data['size'] ?? '0 MB', json_encode($screenshots)
            ]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => $app_id ? 'Aplicativo atualizado com sucesso!' : 'Aplicativo criado com sucesso!'
        ]);
        
    } catch (PDOException $e) {
        // Database error
        error_log("Database error in save-app.php: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao salvar no banco de dados'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error in save-app.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// formatFileSize is declared in config.php. Removing duplicate to avoid redeclare fatal.
?>
