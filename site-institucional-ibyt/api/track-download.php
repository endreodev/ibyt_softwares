<?php
require_once '../config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

setSecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

try {
    // Read JSON or form payload
    $data = [];
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $json = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $data = $json;
        }
    }
    if (empty($data) && !empty($_POST)) {
        $data = $_POST;
    }

    $appId = isset($data['id']) ? (int)$data['id'] : 0;
    if ($appId <= 0) {
        throw new Exception('ID do aplicativo inválido');
    }

    $pdo = getDbConnection();
    $pdo->beginTransaction();

    // Insert download record (best-effort)
    $stmt = $pdo->prepare("INSERT INTO downloads (app_id, ip_address, user_agent) VALUES (?, ?, ?)");
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $stmt->execute([$appId, $ip, $ua]);

    // Increment counter on apps table
    $stmt = $pdo->prepare("UPDATE apps SET downloads = downloads + 1, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$appId]);

    // Read back new count
    $stmt = $pdo->prepare("SELECT downloads FROM apps WHERE id = ?");
    $stmt->execute([$appId]);
    $row = $stmt->fetch();

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'downloads' => isset($row['downloads']) ? (int)$row['downloads'] : null
    ]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('track-download error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao registrar download']);
}
?>
