<?php
session_start();
header('Content-Type: application/json');

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../vendor/autoload.php';

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'listar_dispositivos_disponiveis';
        
        switch ($action) {
            case 'listar_dispositivos_disponiveis':
                listarDispositivosDisponiveis();
                break;
                
            case 'listar_reservatorios_sem_dispositivo':
                listarReservatoriosSemDispositivo();
                break;
                
            default:
                throw new Exception('Ação não encontrada');
        }
        
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'vincular_dispositivo_reservatorio':
                vincularDispositivoReservatorio($input);
                break;
                
            case 'desvincular_dispositivo':
                desvincularDispositivo($input);
                break;
                
            default:
                throw new Exception('Ação não encontrada');
        }
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Listar dispositivos que não estão vinculados a nenhum reservatório
 */
function listarDispositivosDisponiveis() {
    try {
        $sql = "
            SELECT d.*, c.nome_fantasia as cliente_nome
            FROM dispositivos d
            LEFT JOIN clientes c ON d.cliente_id = c.id
            LEFT JOIN reservatorios r ON d.id = r.dispositivo_id
            WHERE r.dispositivo_id IS NULL 
              AND d.status = 'ativo'
            ORDER BY d.created_at DESC
        ";
        
        $stmt = \CoffeeCode\DataLayer\Connect::getInstance()->prepare($sql);
        $stmt->execute();
        $dispositivos = $stmt->fetchAll(\PDO::FETCH_OBJ);
        
        echo json_encode([
            'success' => true,
            'data' => $dispositivos
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Erro ao listar dispositivos disponíveis: ' . $e->getMessage());
    }
}

/**
 * Listar reservatórios sem dispositivo vinculado
 */
function listarReservatoriosSemDispositivo() {
    try {
        $sql = "
            SELECT r.*, c.nome_fantasia as cliente_nome
            FROM reservatorios r
            LEFT JOIN clientes c ON r.cliente_id = c.id
            WHERE r.dispositivo_id IS NULL 
              AND r.ativo = 1
            ORDER BY r.created_at DESC
        ";
        
        $stmt = \CoffeeCode\DataLayer\Connect::getInstance()->prepare($sql);
        $stmt->execute();
        $reservatorios = $stmt->fetchAll(\PDO::FETCH_OBJ);
        
        echo json_encode([
            'success' => true,
            'data' => $reservatorios
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Erro ao listar reservatórios sem dispositivo: ' . $e->getMessage());
    }
}

/**
 * Vincular dispositivo a reservatório
 */
function vincularDispositivoReservatorio($input) {
    try {
        $dispositivoId = $input['dispositivo_id'] ?? null;
        $reservatorioId = $input['reservatorio_id'] ?? null;
        
        if (!$dispositivoId || !$reservatorioId) {
            throw new Exception('Dispositivo e reservatório são obrigatórios');
        }
        
        // Verificar se dispositivo existe e está disponível
        $sqlDispositivo = "
            SELECT d.*, r.id as reservatorio_vinculado
            FROM dispositivos d
            LEFT JOIN reservatorios r ON d.id = r.dispositivo_id
            WHERE d.id = :dispositivo_id
        ";
        
        $stmt = \CoffeeCode\DataLayer\Connect::getInstance()->prepare($sqlDispositivo);
        $stmt->bindValue(':dispositivo_id', $dispositivoId, \PDO::PARAM_INT);
        $stmt->execute();
        $dispositivo = $stmt->fetch(\PDO::FETCH_OBJ);
        
        if (!$dispositivo) {
            throw new Exception('Dispositivo não encontrado');
        }
        
        if ($dispositivo->reservatorio_vinculado) {
            throw new Exception('Dispositivo já está vinculado a outro reservatório');
        }
        
        // Verificar se reservatório existe e está disponível
        $sqlReservatorio = "
            SELECT * FROM reservatorios 
            WHERE id = :reservatorio_id 
              AND dispositivo_id IS NULL 
              AND ativo = 1
        ";
        
        $stmt = \CoffeeCode\DataLayer\Connect::getInstance()->prepare($sqlReservatorio);
        $stmt->bindValue(':reservatorio_id', $reservatorioId, \PDO::PARAM_INT);
        $stmt->execute();
        $reservatorio = $stmt->fetch(\PDO::FETCH_OBJ);
        
        if (!$reservatorio) {
            throw new Exception('Reservatório não encontrado ou já possui dispositivo vinculado');
        }
        
        // Verificar se são do mesmo cliente
        if ($dispositivo->cliente_id != $reservatorio->cliente_id) {
            throw new Exception('Dispositivo e reservatório devem ser do mesmo cliente');
        }
        
        // Fazer a vinculação
        $sqlVincular = "
            UPDATE reservatorios 
            SET dispositivo_id = :dispositivo_id, 
                updated_at = NOW() 
            WHERE id = :reservatorio_id
        ";
        
        $stmt = \CoffeeCode\DataLayer\Connect::getInstance()->prepare($sqlVincular);
        $stmt->bindValue(':dispositivo_id', $dispositivoId, \PDO::PARAM_INT);
        $stmt->bindValue(':reservatorio_id', $reservatorioId, \PDO::PARAM_INT);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Dispositivo vinculado ao reservatório com sucesso',
            'data' => [
                'dispositivo_id' => $dispositivoId,
                'reservatorio_id' => $reservatorioId
            ]
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Erro ao vincular dispositivo: ' . $e->getMessage());
    }
}

/**
 * Desvincular dispositivo do reservatório
 */
function desvincularDispositivo($input) {
    try {
        $reservatorioId = $input['reservatorio_id'] ?? null;
        
        if (!$reservatorioId) {
            throw new Exception('ID do reservatório é obrigatório');
        }
        
        $sql = "
            UPDATE reservatorios 
            SET dispositivo_id = NULL, 
                updated_at = NOW() 
            WHERE id = :reservatorio_id
        ";
        
        $stmt = \CoffeeCode\DataLayer\Connect::getInstance()->prepare($sql);
        $stmt->bindValue(':reservatorio_id', $reservatorioId, \PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Dispositivo desvinculado com sucesso'
            ]);
        } else {
            throw new Exception('Reservatório não encontrado');
        }
        
    } catch (Exception $e) {
        throw new Exception('Erro ao desvincular dispositivo: ' . $e->getMessage());
    }
}
?>
