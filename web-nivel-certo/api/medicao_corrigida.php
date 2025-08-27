<?php
// API de Medições Corrigida
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

date_default_timezone_set('America/Cuiaba');

function returnJson($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

try {
    // Incluir configurações
    $configFile = '../config/database-simples.php';
    if (!file_exists($configFile)) {
        $configFile = '../config/database.php';
    }
    require_once $configFile;
    
    if (!defined('DATA_LAYER_CONFIG')) {
        throw new Exception('Configurações de banco não definidas');
    }
    
    $config = DATA_LAYER_CONFIG;
    $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4", 
                   $config['username'], $config['passwd']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Garantir que a tabela medicoes existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'medicoes'");
    if (!$stmt->fetch()) {
        $sql = "CREATE TABLE medicoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            dispositivo_id INT NOT NULL,
            nivel_agua DECIMAL(5,2) NOT NULL,
            timestamp_medicao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            temperatura DECIMAL(5,2) NULL,
            status VARCHAR(50) DEFAULT 'normal',
            INDEX idx_dispositivo (dispositivo_id),
            INDEX idx_timestamp (timestamp_medicao)
        )";
        $pdo->exec($sql);
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        // Verificar parâmetro 'data' no formato D{id}N{nivel}
        $data = $_GET['data'] ?? '';
        
        if (preg_match('/^D(\d+)N(\d+(?:\.\d+)?)$/', $data, $matches)) {
            $dispositivoId = (int)$matches[1];
            $nivelAgua = (float)$matches[2];
            
            // Validar valores
            if ($dispositivoId < 1 || $dispositivoId > 9999) {
                returnJson([
                    'success' => false,
                    'message' => 'ID do dispositivo inválido (1-9999)',
                    'received' => $data
                ]);
            }
            
            if ($nivelAgua < 0 || $nivelAgua > 100) {
                returnJson([
                    'success' => false,
                    'message' => 'Nível de água inválido (0-100%)',
                    'received' => $data
                ]);
            }
            
            // Inserir medição
            $stmt = $pdo->prepare("
                INSERT INTO medicoes (dispositivo_id, nivel_agua, timestamp_medicao, status) 
                VALUES (?, ?, NOW(), ?)
            ");
            
            $status = 'normal';
            if ($nivelAgua < 20) $status = 'baixo';
            elseif ($nivelAgua > 90) $status = 'alto';
            
            $stmt->execute([$dispositivoId, $nivelAgua, $status]);
            $medicaoId = $pdo->lastInsertId();
            
            returnJson([
                'success' => true,
                'message' => 'Medição registrada com sucesso',
                'data' => [
                    'id' => $medicaoId,
                    'dispositivo_id' => $dispositivoId,
                    'nivel_agua' => $nivelAgua,
                    'status' => $status,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
            
        } else {
            // Verificar se é uma consulta de medições
            $action = $_GET['action'] ?? '';
            
            if ($action === 'list') {
                $limit = min((int)($_GET['limit'] ?? 50), 100);
                $dispositivoId = $_GET['dispositivo_id'] ?? null;
                
                $sql = "SELECT * FROM medicoes";
                $params = [];
                
                if ($dispositivoId) {
                    $sql .= " WHERE dispositivo_id = ?";
                    $params[] = $dispositivoId;
                }
                
                $sql .= " ORDER BY timestamp_medicao DESC LIMIT ?";
                $params[] = $limit;
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $medicoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                returnJson([
                    'success' => true,
                    'data' => $medicoes,
                    'count' => count($medicoes)
                ]);
                
            } else {
                returnJson([
                    'success' => false,
                    'message' => 'Formato inválido. Use: ?data=D{id}N{nivel} (ex: D1N75) ou ?action=list',
                    'examples' => [
                        'Enviar medição: ?data=D1N75.5',
                        'Listar medições: ?action=list',
                        'Filtrar por dispositivo: ?action=list&dispositivo_id=1'
                    ]
                ]);
            }
        }
        
    } elseif ($method === 'POST') {
        // Receber dados via POST JSON
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            returnJson([
                'success' => false,
                'message' => 'Dados JSON inválidos'
            ]);
        }
        
        $dispositivoId = $input['dispositivo_id'] ?? null;
        $nivelAgua = $input['nivel_agua'] ?? null;
        $temperatura = $input['temperatura'] ?? null;
        
        if (!$dispositivoId || $nivelAgua === null) {
            returnJson([
                'success' => false,
                'message' => 'dispositivo_id e nivel_agua são obrigatórios'
            ]);
        }
        
        $status = 'normal';
        if ($nivelAgua < 20) $status = 'baixo';
        elseif ($nivelAgua > 90) $status = 'alto';
        
        $stmt = $pdo->prepare("
            INSERT INTO medicoes (dispositivo_id, nivel_agua, temperatura, timestamp_medicao, status) 
            VALUES (?, ?, ?, NOW(), ?)
        ");
        
        $stmt->execute([$dispositivoId, $nivelAgua, $temperatura, $status]);
        $medicaoId = $pdo->lastInsertId();
        
        returnJson([
            'success' => true,
            'message' => 'Medição registrada via POST',
            'data' => [
                'id' => $medicaoId,
                'dispositivo_id' => $dispositivoId,
                'nivel_agua' => $nivelAgua,
                'temperatura' => $temperatura,
                'status' => $status,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]);
        
    } else {
        returnJson([
            'success' => false,
            'message' => 'Método não suportado. Use GET ou POST'
        ]);
    }
    
} catch (Exception $e) {
    returnJson([
        'success' => false,
        'message' => 'Erro interno: ' . $e->getMessage(),
        'error' => true
    ]);
}
?>
