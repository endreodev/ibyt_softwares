<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Configurar fuso horário para Cuiabá
date_default_timezone_set('America/Cuiaba');

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../vendor/autoload.php';

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'listar';
        
        switch ($action) {
            case 'listar':
                listarReservatorios();
                break;
                
            case 'buscar':
                buscarReservatorio($_GET['id'] ?? null);
                break;
                
            case 'dispositivos_disponiveis':
                listarDispositivosDisponiveis();
                break;
                
            case 'clientes':
                listarClientes();
                break;
                
            default:
                throw new Exception('Ação não encontrada');
        }
        
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'criar':
                criarReservatorio($input);
                break;
                
            case 'vincular_dispositivo':
                vincularDispositivo($input);
                break;
                
            case 'desvincular_dispositivo':
                desvincularDispositivo($input);
                break;
                
            default:
                throw new Exception('Ação não encontrada');
        }
        
    } elseif ($method === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true);
        atualizarReservatorio($input);
        
    } elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        deletarReservatorio($id);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Listar reservatórios com informações completas
 */
function listarReservatorios() {
    try {
        $limite = (int)($_GET['limite'] ?? 50);
        $offset = (int)($_GET['offset'] ?? 0);
        $cliente_id = $_GET['cliente_id'] ?? null;
        
        $whereClause = "WHERE r.ativo = 1";
        $params = [];
        
        if ($cliente_id) {
            $whereClause .= " AND r.cliente_id = :cliente_id";
            $params[':cliente_id'] = $cliente_id;
        }
        
        $sql = "
            SELECT 
                r.*,
                c.nome_fantasia as cliente_nome,
                d.id as dispositivo_id,
                d.identificador as dispositivo_identificador,
                d.status as dispositivo_status,
                m.nivel_agua as nivel_atual,
                m.percentual as percentual_atual,
                m.timestamp_medicao as ultima_medicao,
                CASE 
                    WHEN m.nivel_agua IS NULL THEN 'SEM_DADOS'
                    WHEN m.nivel_agua <= r.altura_minima THEN 'CRITICO'
                    WHEN m.nivel_agua <= (r.altura_minima * 1.5) THEN 'BAIXO'
                    WHEN m.nivel_agua >= (r.altura_total * 0.9) THEN 'ALTO'
                    ELSE 'NORMAL'
                END as status_nivel
            FROM reservatorios r
            LEFT JOIN clientes c ON r.cliente_id = c.id
            LEFT JOIN dispositivos d ON r.dispositivo_id = d.id
            LEFT JOIN (
                SELECT dispositivo_id, nivel_agua, percentual, timestamp_medicao,
                       ROW_NUMBER() OVER (PARTITION BY dispositivo_id ORDER BY timestamp_medicao DESC) as rn
                FROM medicoes
            ) m ON d.id = m.dispositivo_id AND m.rn = 1
            $whereClause
            ORDER BY r.created_at DESC
            LIMIT :limite OFFSET :offset
        ";
        
        $stmt = \CoffeeCode\DataLayer\Connect::getInstance()->prepare($sql);
        $stmt->bindValue(':limite', $limite, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $reservatorios = $stmt->fetchAll(\PDO::FETCH_OBJ);
        
        // Contar total
        $sqlCount = "SELECT COUNT(*) FROM reservatorios r $whereClause";
        $stmtCount = \CoffeeCode\DataLayer\Connect::getInstance()->prepare($sqlCount);
        foreach ($params as $key => $value) {
            $stmtCount->bindValue($key, $value);
        }
        $stmtCount->execute();
        $total = $stmtCount->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'data' => $reservatorios,
            'total' => (int)$total,
            'limite' => $limite,
            'offset' => $offset
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Erro ao listar reservatórios: ' . $e->getMessage());
    }
}

/**
 * Buscar reservatório específico
 */
function buscarReservatorio($id) {
    try {
        if (!$id) {
            throw new Exception('ID do reservatório é obrigatório');
        }
        
        $sql = "
            SELECT 
                r.*,
                c.nome_fantasia as cliente_nome,
                d.id as dispositivo_id,
                d.identificador as dispositivo_identificador,
                d.status as dispositivo_status
            FROM reservatorios r
            LEFT JOIN clientes c ON r.cliente_id = c.id
            LEFT JOIN dispositivos d ON r.dispositivo_id = d.id
            WHERE r.id = :id
        ";
        
        $stmt = \CoffeeCode\DataLayer\Connect::getInstance()->prepare($sql);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        $reservatorio = $stmt->fetch(\PDO::FETCH_OBJ);
        
        if (!$reservatorio) {
            throw new Exception('Reservatório não encontrado');
        }
        
        echo json_encode([
            'success' => true,
            'data' => $reservatorio
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Erro ao buscar reservatório: ' . $e->getMessage());
    }
}

/**
 * Criar novo reservatório
 */
function criarReservatorio($data) {
    try {
        $nome = $data['nome'] ?? null;
        $cliente_id = $data['cliente_id'] ?? null;
        $capacidade = $data['capacidade'] ?? null;
        $altura_total = $data['altura_total'] ?? null;
        $altura_minima = $data['altura_minima'] ?? 10.00;
        $altura_maxima = $data['altura_maxima'] ?? null;
        $tipo = $data['tipo'] ?? 'cilindrico';
        $localizacao = $data['localizacao'] ?? null;
        $descricao = $data['descricao'] ?? null;
        
        if (!$nome) {
            throw new Exception('Nome do reservatório é obrigatório');
        }
        
        // Se altura_maxima não foi definida, usar 95% da altura total
        if ($altura_total && !$altura_maxima) {
            $altura_maxima = $altura_total * 0.95;
        }
        
        $sql = "
            INSERT INTO reservatorios 
            (cliente_id, nome, capacidade, altura_total, altura_minima, altura_maxima, tipo, localizacao, descricao)
            VALUES 
            (:cliente_id, :nome, :capacidade, :altura_total, :altura_minima, :altura_maxima, :tipo, :localizacao, :descricao)
        ";
        
        $stmt = \CoffeeCode\DataLayer\Connect::getInstance()->prepare($sql);
        $stmt->bindValue(':cliente_id', $cliente_id, \PDO::PARAM_INT);
        $stmt->bindValue(':nome', $nome);
        $stmt->bindValue(':capacidade', $capacidade);
        $stmt->bindValue(':altura_total', $altura_total);
        $stmt->bindValue(':altura_minima', $altura_minima);
        $stmt->bindValue(':altura_maxima', $altura_maxima);
        $stmt->bindValue(':tipo', $tipo);
        $stmt->bindValue(':localizacao', $localizacao);
        $stmt->bindValue(':descricao', $descricao);
        
        if ($stmt->execute()) {
            $id = \CoffeeCode\DataLayer\Connect::getInstance()->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Reservatório criado com sucesso',
                'data' => ['id' => $id]
            ]);
        } else {
            throw new Exception('Erro ao inserir reservatório no banco de dados');
        }
        
    } catch (Exception $e) {
        throw new Exception('Erro ao criar reservatório: ' . $e->getMessage());
    }
}

/**
 * Atualizar reservatório
 */
function atualizarReservatorio($data) {
    try {
        $id = $data['id'] ?? null;
        
        if (!$id) {
            throw new Exception('ID do reservatório é obrigatório');
        }
        
        $campos = [];
        $params = [':id' => $id];
        
        $camposPermitidos = [
            'nome', 'cliente_id', 'capacidade', 'altura_total', 'altura_minima', 
            'altura_maxima', 'tipo', 'localizacao', 'descricao', 'ativo'
        ];
        
        foreach ($camposPermitidos as $campo) {
            if (isset($data[$campo])) {
                $campos[] = "$campo = :$campo";
                $params[":$campo"] = $data[$campo];
            }
        }
        
        if (empty($campos)) {
            throw new Exception('Nenhum campo para atualizar');
        }
        
        $sql = "UPDATE reservatorios SET " . implode(', ', $campos) . " WHERE id = :id";
        
        $stmt = \CoffeeCode\DataLayer\Connect::getInstance()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Reservatório atualizado com sucesso'
            ]);
        } else {
            throw new Exception('Erro ao atualizar reservatório');
        }
        
    } catch (Exception $e) {
        throw new Exception('Erro ao atualizar reservatório: ' . $e->getMessage());
    }
}

/**
 * Deletar reservatório (soft delete)
 */
function deletarReservatorio($id) {
    try {
        if (!$id) {
            throw new Exception('ID do reservatório é obrigatório');
        }
        
        $sql = "UPDATE reservatorios SET ativo = 0 WHERE id = :id";
        $stmt = \CoffeeCode\DataLayer\Connect::getInstance()->prepare($sql);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Reservatório removido com sucesso'
            ]);
        } else {
            throw new Exception('Erro ao remover reservatório');
        }
        
    } catch (Exception $e) {
        throw new Exception('Erro ao deletar reservatório: ' . $e->getMessage());
    }
}

/**
 * Listar dispositivos disponíveis (sem reservatório vinculado)
 */
function listarDispositivosDisponiveis() {
    try {
        $sql = "
            SELECT d.*, c.nome_fantasia as cliente_nome
            FROM dispositivos d
            LEFT JOIN clientes c ON d.cliente_id = c.id
            LEFT JOIN reservatorios r ON d.id = r.dispositivo_id AND r.ativo = 1
            WHERE r.dispositivo_id IS NULL 
              AND d.status = 'ativo'
            ORDER BY d.id ASC
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
 * Listar clientes
 */
function listarClientes() {
    try {
        $sql = "SELECT id, nome_fantasia, razao_social, email, telefone FROM clientes WHERE ativo = 1 ORDER BY nome_fantasia";
        $stmt = \CoffeeCode\DataLayer\Connect::getInstance()->prepare($sql);
        $stmt->execute();
        $clientes = $stmt->fetchAll(\PDO::FETCH_OBJ);
        
        echo json_encode([
            'success' => true,
            'data' => $clientes
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Erro ao listar clientes: ' . $e->getMessage());
    }
}

/**
 * Vincular dispositivo a reservatório
 */
function vincularDispositivo($data) {
    try {
        $reservatorio_id = $data['reservatorio_id'] ?? null;
        $dispositivo_id = $data['dispositivo_id'] ?? null;
        
        if (!$reservatorio_id || !$dispositivo_id) {
            throw new Exception('ID do reservatório e dispositivo são obrigatórios');
        }
        
        // Verificar se dispositivo não está vinculado a outro reservatório
        $sqlCheck = "SELECT id FROM reservatorios WHERE dispositivo_id = :dispositivo_id AND ativo = 1 AND id != :reservatorio_id";
        $stmtCheck = \CoffeeCode\DataLayer\Connect::getInstance()->prepare($sqlCheck);
        $stmtCheck->bindValue(':dispositivo_id', $dispositivo_id, \PDO::PARAM_INT);
        $stmtCheck->bindValue(':reservatorio_id', $reservatorio_id, \PDO::PARAM_INT);
        $stmtCheck->execute();
        
        if ($stmtCheck->fetch()) {
            throw new Exception('Dispositivo já está vinculado a outro reservatório');
        }
        
        // Vincular dispositivo
        $sql = "UPDATE reservatorios SET dispositivo_id = :dispositivo_id WHERE id = :reservatorio_id";
        $stmt = \CoffeeCode\DataLayer\Connect::getInstance()->prepare($sql);
        $stmt->bindValue(':dispositivo_id', $dispositivo_id, \PDO::PARAM_INT);
        $stmt->bindValue(':reservatorio_id', $reservatorio_id, \PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Dispositivo vinculado ao reservatório com sucesso'
            ]);
        } else {
            throw new Exception('Erro ao vincular dispositivo');
        }
        
    } catch (Exception $e) {
        throw new Exception('Erro ao vincular dispositivo: ' . $e->getMessage());
    }
}

/**
 * Desvincular dispositivo de reservatório
 */
function desvincularDispositivo($data) {
    try {
        $reservatorio_id = $data['reservatorio_id'] ?? null;
        
        if (!$reservatorio_id) {
            throw new Exception('ID do reservatório é obrigatório');
        }
        
        $sql = "UPDATE reservatorios SET dispositivo_id = NULL WHERE id = :reservatorio_id";
        $stmt = \CoffeeCode\DataLayer\Connect::getInstance()->prepare($sql);
        $stmt->bindValue(':reservatorio_id', $reservatorio_id, \PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Dispositivo desvinculado com sucesso'
            ]);
        } else {
            throw new Exception('Erro ao desvincular dispositivo');
        }
        
    } catch (Exception $e) {
        throw new Exception('Erro ao desvincular dispositivo: ' . $e->getMessage());
    }
}
?>
