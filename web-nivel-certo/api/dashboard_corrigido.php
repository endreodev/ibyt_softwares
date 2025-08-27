<?php
// API Dashboard simplificada - versão corrigida
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

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
    
    $action = $_GET['action'] ?? 'admin';

    if ($action === 'admin') {
        
        // Inicializar contadores
        $totalClientes = 0;
        $totalDispositivos = 0;
        $totalReservatorios = 0;
        $medicoesHoje = 0;
        
        // Contar clientes (com tratamento de erro)
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM clientes WHERE ativo = 1");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalClientes = $result ? (int)$result['total'] : 0;
        } catch (Exception $e) {
            // Se a tabela não existir, retorna 0
            $totalClientes = 0;
        }
        
        // Contar dispositivos
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM dispositivos WHERE status = 'ativo'");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalDispositivos = $result ? (int)$result['total'] : 0;
        } catch (Exception $e) {
            $totalDispositivos = 0;
        }
        
        // Contar reservatórios
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM reservatorios");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalReservatorios = $result ? (int)$result['total'] : 0;
        } catch (Exception $e) {
            $totalReservatorios = 0;
        }
        
        // Contar medições de hoje
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM medicoes WHERE DATE(timestamp_medicao) = CURDATE()");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $medicoesHoje = $result ? (int)$result['total'] : 0;
        } catch (Exception $e) {
            $medicoesHoje = 0;
        }
        
        // Dados dos gráficos (opcionais)
        $medicoesPorDia = [];
        $clientesPorCidade = [];
        $dispositivosPorStatus = [];
        $ultimasMedicoes = [];
        $alertas = [];
        
        // Tentar buscar dados para gráficos
        try {
            $stmt = $pdo->query("
                SELECT DATE(timestamp_medicao) as dia, COUNT(*) as total 
                FROM medicoes 
                WHERE timestamp_medicao >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                GROUP BY DATE(timestamp_medicao) 
                ORDER BY dia DESC
                LIMIT 7
            ");
            $medicoesPorDia = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Ignore se falhar
        }
        
        try {
            $stmt = $pdo->query("
                SELECT cidade, COUNT(*) as total 
                FROM clientes 
                WHERE ativo = 1 AND cidade IS NOT NULL 
                GROUP BY cidade 
                ORDER BY total DESC 
                LIMIT 5
            ");
            $clientesPorCidade = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Ignore se falhar
        }
        
        try {
            $stmt = $pdo->query("
                SELECT m.id, m.dispositivo_id, m.nivel_agua, m.timestamp_medicao, 
                       COALESCE(d.identificador, CONCAT('Device_', m.dispositivo_id)) as identificador,
                       COALESCE(c.nome_fantasia, 'Cliente não identificado') as nome_fantasia
                FROM medicoes m
                LEFT JOIN dispositivos d ON m.dispositivo_id = d.id
                LEFT JOIN clientes c ON d.cliente_id = c.id
                ORDER BY m.timestamp_medicao DESC
                LIMIT 10
            ");
            $ultimasMedicoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Ignore se falhar
        }
        
        returnJson([
            'success' => true,
            'data' => [
                'estatisticas' => [
                    'total_clientes' => $totalClientes,
                    'total_dispositivos' => $totalDispositivos,
                    'total_reservatorios' => $totalReservatorios,
                    'medicoes_hoje' => $medicoesHoje
                ],
                'graficos' => [
                    'medicoes_por_dia' => $medicoesPorDia,
                    'clientes_por_cidade' => $clientesPorCidade,
                    'dispositivos_por_status' => $dispositivosPorStatus
                ],
                'listas' => [
                    'ultimas_medicoes' => $ultimasMedicoes,
                    'alertas' => $alertas
                ]
            ]
        ]);
        
    } else {
        throw new Exception('Ação não suportada');
    }

} catch (Exception $e) {
    http_response_code(500);
    returnJson([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => true,
        'data' => [
            'estatisticas' => [
                'total_clientes' => 0,
                'total_dispositivos' => 0,
                'total_reservatorios' => 0,
                'medicoes_hoje' => 0
            ],
            'graficos' => [
                'medicoes_por_dia' => [],
                'clientes_por_cidade' => [],
                'dispositivos_por_status' => []
            ],
            'listas' => [
                'ultimas_medicoes' => [],
                'alertas' => []
            ]
        ]
    ]);
}
?>
