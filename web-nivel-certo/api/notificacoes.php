<?php
/**
 * API para gerenciar o sistema de notificações automáticas
 */

session_start();
header('Content-Type: application/json');

// Configurar fuso horário para Cuiabá
date_default_timezone_set('America/Cuiaba');

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/telegram.php';
require __DIR__ . '/../vendor/autoload.php';

// Verificar se está logado
if (!isset($_SESSION['logged_in'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Não autorizado - Sessão não encontrada',
        'debug' => [
            'session_id' => session_id(),
            'session_status' => session_status(),
            'session_data' => $_SESSION ?? 'null'
        ]
    ]);
    exit;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($method) {
        case 'GET':
            $action = $_GET['action'] ?? 'status';
            
            switch ($action) {
                case 'status':
                    // Verificar status das configurações
                    echo json_encode(getNotificationStatus());
                    break;
                    
                case 'test':
                    // Testar configuração do Telegram
                    echo json_encode(testTelegramConfiguration());
                    break;
                    
                case 'diagnose':
                    // Diagnosticar problemas do Telegram
                    echo json_encode([
                        'success' => true,
                        'data' => diagnoseTelegramIssues()
                    ]);
                    break;
                    
                case 'logs':
                    // Buscar logs recentes
                    echo json_encode(getRecentLogs());
                    break;
                    
                case 'alertas':
                    // Buscar alertas enviados recentemente
                    echo json_encode(getRecentAlerts());
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Ação não reconhecida'
                    ]);
            }
            break;
            
        case 'POST':
            $action = $input['action'] ?? '';
            
            switch ($action) {
                case 'test_alert':
                    // Enviar alerta de teste
                    echo json_encode(sendTestAlert());
                    break;
                    
                case 'manual_check':
                    // Executar verificação manual
                    echo json_encode(runManualCheck());
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Ação não reconhecida'
                    ]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Método não permitido'
            ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno: ' . $e->getMessage()
    ]);
}

/**
 * Verificar status das configurações de notificação
 */
function getNotificationStatus() {
    try {
        $pdo = \CoffeeCode\DataLayer\Connect::getInstance();
        
        // Verificar configuração do Telegram
        $telegramConfigured = isTelegramConfigured();
        
        // Buscar estatísticas de alertas
        $alertStats = [];
        try {
            $sql = "
                SELECT 
                    COUNT(*) as total_alertas,
                    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as alertas_hoje,
                    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as alertas_semana
                FROM alertas_enviados
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $alertStats = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Tabela pode não existir ainda
            $alertStats = [
                'total_alertas' => 0,
                'alertas_hoje' => 0,
                'alertas_semana' => 0
            ];
        }
        
        // Buscar reservatórios que precisam de atenção
        $sql = "
            SELECT COUNT(*) as reservatorios_criticos
            FROM reservatorios r
            LEFT JOIN (
                SELECT 
                    dispositivo,
                    nivel,
                    created_at,
                    ROW_NUMBER() OVER (PARTITION BY dispositivo ORDER BY created_at DESC) as rn
                FROM medicoes
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
            ) m ON r.dispositivo = m.dispositivo AND m.rn = 1
            WHERE m.nivel IS NOT NULL 
            AND CAST(m.nivel AS DECIMAL(5,2)) < 80
            AND TIMESTAMPDIFF(MINUTE, m.created_at, NOW()) <= 60
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $criticalCount = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'data' => [
                'telegram_configured' => $telegramConfigured,
                'monitoring_active' => $telegramConfigured,
                'reservatorios_criticos' => $criticalCount['reservatorios_criticos'],
                'alert_stats' => $alertStats,
                'last_check' => getLastCheckTime()
            ]
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Erro ao verificar status: ' . $e->getMessage()
        ];
    }
}

/**
 * Buscar logs recentes
 */
function getRecentLogs() {
    try {
        $logsDir = __DIR__ . '/../scripts/logs/';
        $allLogs = [];
        
        // Buscar logs de hoje e ontem
        $dates = [
            date('Y-m-d'),              // Hoje
            date('Y-m-d', strtotime('-1 day'))  // Ontem
        ];
        
        foreach ($dates as $date) {
            $logFile = $logsDir . 'monitor_' . $date . '.log';
            
            if (file_exists($logFile)) {
                $logs = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if ($logs) {
                    // Adicionar data ao log se não estiver presente
                    foreach ($logs as $log) {
                        if (!empty(trim($log))) {
                            $allLogs[] = $log;
                        }
                    }
                }
            }
        }
        
        // Se não encontrou logs específicos, verificar se há outros arquivos
        if (empty($allLogs)) {
            $logFiles = glob($logsDir . 'monitor_*.log');
            if (!empty($logFiles)) {
                // Pegar o arquivo mais recente
                usort($logFiles, function($a, $b) {
                    return filemtime($b) - filemtime($a);
                });
                
                $latestLog = $logFiles[0];
                $logs = file($latestLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if ($logs) {
                    $fileName = basename($latestLog);
                    foreach ($logs as $log) {
                        if (!empty(trim($log))) {
                            $allLogs[] = "[{$fileName}] " . $log;
                        }
                    }
                }
            }
        }
        
        // Se ainda não tem logs, criar um log de exemplo
        if (empty($allLogs)) {
            $allLogs = [
                '[' . date('Y-m-d H:i:s') . '] Sistema de monitoramento inicializado',
                '[' . date('Y-m-d H:i:s') . '] Aguardando primeira execução...',
                '[' . date('Y-m-d H:i:s') . '] Execute uma verificação manual para gerar logs'
            ];
        }
        
        // Pegar as últimas 30 linhas e reverter ordem (mais recente primeiro)
        $recentLogs = array_slice($allLogs, -30);
        
        return [
            'success' => true,
            'data' => array_reverse($recentLogs)
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Erro ao buscar logs: ' . $e->getMessage()
        ];
    }
}

/**
 * Buscar alertas enviados recentemente
 */
function getRecentAlerts() {
    try {
        $pdo = \CoffeeCode\DataLayer\Connect::getInstance();
        
        $sql = "
            SELECT 
                a.id,
                a.reservatorioid,
                a.nivel,
                a.tipo_alerta,
                a.created_at,
                r.nome as reservatorio_nome,
                c.nome as cliente_nome
            FROM alertas_enviados a
            LEFT JOIN reservatorios r ON a.reservatorioid = r.reservatorioid
            LEFT JOIN clientes c ON r.clienteid = c.clienteid
            ORDER BY a.created_at DESC
            LIMIT 50
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $alerts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'data' => $alerts
        ];
        
    } catch (Exception $e) {
        return [
            'success' => true,
            'data' => []
        ];
    }
}

/**
 * Enviar alerta de teste
 */
function sendTestAlert() {
    if (!isTelegramConfigured()) {
        return [
            'success' => false,
            'message' => 'Telegram não configurado'
        ];
    }
    
    $message = "🧪 <b>TESTE DE ALERTA - NÍVEL CERTO</b>\n\n";
    $message .= "✅ Sistema de notificações funcionando corretamente!\n\n";
    $message .= "📊 Este é um teste do sistema de alertas automáticos.\n";
    $message .= "📅 " . date('d/m/Y H:i:s');
    
    $result = sendTelegramMessage($message);
    
    if ($result && isset($result['ok']) && $result['ok']) {
        return [
            'success' => true,
            'message' => 'Alerta de teste enviado com sucesso!'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Erro ao enviar alerta de teste',
            'details' => $result
        ];
    }
}

/**
 * Executar verificação manual
 */
function runManualCheck() {
    try {
        // Incluir e executar o script de monitoramento
        require_once __DIR__ . '/../scripts/monitor_automatico.php';
        
        $monitor = new MonitorAutomatico();
        
        // Capturar output
        ob_start();
        $monitor->executar();
        $output = ob_get_clean();
        
        return [
            'success' => true,
            'message' => 'Verificação manual executada com sucesso',
            'output' => $output
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Erro na verificação manual: ' . $e->getMessage()
        ];
    }
}

/**
 * Obter timestamp da última verificação
 */
function getLastCheckTime() {
    $logFile = __DIR__ . '/../scripts/logs/monitor_' . date('Y-m-d') . '.log';
    
    if (!file_exists($logFile)) {
        return null;
    }
    
    $logs = file($logFile, FILE_IGNORE_NEW_LINES);
    
    if (empty($logs)) {
        return null;
    }
    
    // Buscar a última linha com timestamp
    for ($i = count($logs) - 1; $i >= 0; $i--) {
        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $logs[$i], $matches)) {
            return $matches[1];
        }
    }
    
    return null;
}
