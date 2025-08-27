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
    $input = json_decode(file_get_contents('php://input'), true);
    
    $reservatorio = new \Model\Reservatorio();
    
    switch ($method) {
        case 'GET':
            // Verificar se é para buscar dados auxiliares
            if (isset($_GET['action'])) {
                switch ($_GET['action']) {
                    case 'dispositivos-disponiveis':
                        $dispositivos = $reservatorio->buscarDispositivosDisponiveis();
                        echo json_encode([
                            'success' => true,
                            'data' => $dispositivos
                        ]);
                        break;
                        
                    case 'clientes':
                        $clientes = $reservatorio->buscarClientes();
                        echo json_encode([
                            'success' => true,
                            'data' => $clientes
                        ]);
                        break;
                        
                    default:
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'message' => 'Ação não reconhecida'
                        ]);
                }
                break;
            }
            
            // Listar reservatórios ou buscar por ID
            if (isset($_GET['id']) && !empty($_GET['id'])) {
                $id = (int)$_GET['id'];
                $reservatorioData = $reservatorio->findById($id);
                
                if ($reservatorioData) {
                    echo json_encode([
                        'success' => true,
                        'data' => $reservatorioData->data()
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Reservatório não encontrado'
                    ]);
                }
            } else {
                // Listar todos os reservatórios com paginação
                $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 20;
                $offset = ($page - 1) * $limit;
                $search = isset($_GET['search']) ? trim($_GET['search']) : '';
                
                $reservatorios = $reservatorio->listar($limit, $offset, $search);
                $total = $reservatorio->contar($search);
                $totalPages = ceil($total / $limit);
                
                echo json_encode([
                    'success' => true,
                    'data' => $reservatorios,
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => $totalPages,
                        'total_records' => $total,
                        'per_page' => $limit
                    ]
                ]);
            }
            break;
            
        case 'POST':
            // Criar novo reservatório
            if (empty($input['cliente_id']) || empty($input['nome'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Cliente e nome são obrigatórios'
                ]);
                break;
            }
            
            $reservatorio->cliente_id = (int)$input['cliente_id'];
            if (!empty($input['dispositivo_id'])) {
                $reservatorio->dispositivo_id = (int)$input['dispositivo_id'];
            }
            $reservatorio->nome = trim($input['nome']);
            $reservatorio->capacidade_total = $input['capacidade_total'] ?? 0;
            $reservatorio->altura_total = $input['altura_total'] ?? 0;
            $reservatorio->tipo = $input['tipo'] ?? 'cilindrico';
            $reservatorio->localizacao = $input['localizacao'] ?? null;
            $reservatorio->observacoes = $input['observacoes'] ?? null;
            
            if ($reservatorio->save()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Reservatório criado com sucesso',
                    'data' => $reservatorio->data()
                ]);
            } else {
                http_response_code(400);
                $errorMessage = 'Erro ao criar reservatório';
                if ($reservatorio->fail()) {
                    $errorMessage = $reservatorio->fail()->getMessage();
                }
                
                echo json_encode([
                    'success' => false,
                    'message' => $errorMessage
                ]);
            }
            break;
            
        case 'PUT':
            // Atualizar reservatório
            if (empty($input['id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID do reservatório é obrigatório'
                ]);
                break;
            }
            
            $id = (int)$input['id'];
            $reservatorioToUpdate = $reservatorio->findById($id);
            
            if (!$reservatorioToUpdate) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Reservatório não encontrado'
                ]);
                break;
            }
            
            // Atualizar campos
            if (!empty($input['cliente_id'])) {
                $reservatorioToUpdate->cliente_id = (int)$input['cliente_id'];
            }
            if (!empty($input['dispositivo_id'])) {
                $reservatorioToUpdate->dispositivo_id = (int)$input['dispositivo_id'];
            }
            if (!empty($input['nome'])) {
                $reservatorioToUpdate->nome = trim($input['nome']);
            }
            
            if ($reservatorioToUpdate->save()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Reservatório atualizado com sucesso',
                    'data' => $reservatorioToUpdate->data()
                ]);
            } else {
                http_response_code(400);
                $errorMessage = 'Erro ao atualizar reservatório';
                if ($reservatorioToUpdate->fail()) {
                    $errorMessage = $reservatorioToUpdate->fail()->getMessage();
                }
                
                echo json_encode([
                    'success' => false,
                    'message' => $errorMessage
                ]);
            }
            break;
            
        case 'DELETE':
            // Deletar reservatório
            $id = null;
            
            // Pode vir do corpo da requisição ou da URL
            if (isset($input['id'])) {
                $id = (int)$input['id'];
            } elseif (isset($_GET['id'])) {
                $id = (int)$_GET['id'];
            }
            
            if (empty($id)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID do reservatório é obrigatório'
                ]);
                break;
            }
            
            $reservatorioToDelete = $reservatorio->findById($id);
            
            if (!$reservatorioToDelete) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Reservatório não encontrado'
                ]);
                break;
            }
            
            if ($reservatorioToDelete->destroy()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Reservatório deletado com sucesso'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro ao deletar reservatório'
                ]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Método não permitido'
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno: ' . $e->getMessage()
    ]);
}
