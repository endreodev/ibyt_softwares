<?php
session_start();
header('Content-Type: application/json');

// Configurar fuso horário para Cuiabá
date_default_timezone_set('America/Cuiaba');

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../vendor/autoload.php';

// Verificar se está logado
if (!isset($_SESSION['logged_in'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Não autorizado'
    ]);
    exit;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        $pdo = \CoffeeCode\DataLayer\Connect::getInstance();
        
        // Buscar reservatórios com último nível
        $sqlReservatorios = "
            SELECT 
                r.reservatorioid,
                r.clienteid,
                r.dispositivo,
                r.nome,
                r.created_at,
                c.nome as cliente_nome,
                c.fantas as cliente_fantasia,
                c.cnpj as cliente_cnpj,
                m.nivel as nivel_atual,
                m.created_at as ultima_medicao
            FROM reservatorios r
            INNER JOIN clientes c ON r.clienteid = c.clienteid
            LEFT JOIN (
                SELECT 
                    dispositivo,
                    nivel,
                    created_at,
                    ROW_NUMBER() OVER (PARTITION BY dispositivo ORDER BY created_at DESC) as rn
                FROM medicoes
            ) m ON r.dispositivo = m.dispositivo AND m.rn = 1
            ORDER BY r.nome ASC
        ";
        
        $stmt = $pdo->prepare($sqlReservatorios);
        $stmt->execute();
        $reservatorios = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Buscar dispositivos que não estão configurados em reservatórios
        $sqlDispositivosNaoConfigurados = "
            SELECT DISTINCT m.dispositivo
            FROM medicoes m
            LEFT JOIN reservatorios r ON m.dispositivo = r.dispositivo
            WHERE r.dispositivo IS NULL
            ORDER BY CAST(m.dispositivo AS UNSIGNED)
        ";
        
        $stmt = $pdo->prepare($sqlDispositivosNaoConfigurados);
        $stmt->execute();
        $dispositivosNaoConfigurados = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        
        // Buscar lista de clientes únicos para filtro
        $sqlClientes = "
            SELECT DISTINCT c.nome, c.fantas
            FROM clientes c
            INNER JOIN reservatorios r ON c.clienteid = r.clienteid
            ORDER BY c.nome ASC
        ";
        
        $stmt = $pdo->prepare($sqlClientes);
        $stmt->execute();
        $clientes = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'reservatorios' => $reservatorios,
                'dispositivos_nao_configurados' => $dispositivosNaoConfigurados,
                'clientes' => $clientes
            ]
        ]);
        
    } else {
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
?>
