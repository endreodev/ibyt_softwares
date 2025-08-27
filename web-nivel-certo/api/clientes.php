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
    
    $cliente = new \Model\Cliente();
    
    switch ($method) {
        case 'GET':
            // Listar clientes ou buscar por ID
            if (isset($_GET['id']) && !empty($_GET['id'])) {
                $id = (int)$_GET['id'];
                $clienteData = $cliente->findById($id);
                
                if ($clienteData) {
                    echo json_encode([
                        'success' => true,
                        'data' => $clienteData->data()
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Cliente não encontrado'
                    ]);
                }
            } else {
                // Listar todos os clientes com paginação
                $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 20;
                $offset = ($page - 1) * $limit;
                $search = isset($_GET['search']) ? trim($_GET['search']) : '';
                
                $clientes = $cliente->listar($limit, $offset, $search);
                $total = $cliente->contar($search);
                $totalPages = ceil($total / $limit);
                
                echo json_encode([
                    'success' => true,
                    'data' => $clientes,
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
            // Criar novo cliente
            if (empty($input['cnpj']) || empty($input['nome'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'CNPJ e nome são obrigatórios'
                ]);
                break;
            }
            
            // Limpar formatação do CNPJ
            $cliente->cnpj = $cliente->limparCnpj(trim($input['cnpj']));
            $cliente->ie = trim($input['ie'] ?? '');
            $cliente->nome = trim($input['nome']);
            $cliente->fantas = trim($input['fantas'] ?? '');
            $cliente->endereco = trim($input['endereco'] ?? '');
            $cliente->numero = trim($input['numero'] ?? '');
            $cliente->bairro = trim($input['bairro'] ?? '');
            $cliente->cidade = trim($input['cidade'] ?? '');
            $cliente->estado = trim($input['estado'] ?? '');
            $cliente->cep = trim($input['cep'] ?? '');
            $cliente->telefone = trim($input['telefone'] ?? '');
            $cliente->email = trim($input['email'] ?? '');
            
            if ($cliente->save()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Cliente criado com sucesso',
                    'data' => $cliente->data()
                ]);
            } else {
                http_response_code(400);
                $errorMessage = 'Erro ao criar cliente';
                if ($cliente->fail()) {
                    $errorMessage = $cliente->fail()->getMessage();
                }
                
                echo json_encode([
                    'success' => false,
                    'message' => $errorMessage
                ]);
            }
            break;
            
        case 'PUT':
            // Atualizar cliente
            if (empty($input['clienteid'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID do cliente é obrigatório'
                ]);
                break;
            }
            
            $id = (int)$input['clienteid'];
            $clienteToUpdate = $cliente->findById($id);
            
            if (!$clienteToUpdate) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Cliente não encontrado'
                ]);
                break;
            }
            
            // Atualizar campos
            if (!empty($input['cnpj'])) {
                $clienteToUpdate->cnpj = $clienteToUpdate->limparCnpj(trim($input['cnpj']));
            }
            if (isset($input['ie'])) {
                $clienteToUpdate->ie = trim($input['ie']);
            }
            if (!empty($input['nome'])) {
                $clienteToUpdate->nome = trim($input['nome']);
            }
            if (isset($input['fantas'])) {
                $clienteToUpdate->fantas = trim($input['fantas']);
            }
            if (isset($input['endereco'])) {
                $clienteToUpdate->endereco = trim($input['endereco']);
            }
            if (isset($input['numero'])) {
                $clienteToUpdate->numero = trim($input['numero']);
            }
            if (isset($input['bairro'])) {
                $clienteToUpdate->bairro = trim($input['bairro']);
            }
            if (isset($input['cidade'])) {
                $clienteToUpdate->cidade = trim($input['cidade']);
            }
            if (isset($input['estado'])) {
                $clienteToUpdate->estado = trim($input['estado']);
            }
            if (isset($input['cep'])) {
                $clienteToUpdate->cep = trim($input['cep']);
            }
            if (isset($input['telefone'])) {
                $clienteToUpdate->telefone = trim($input['telefone']);
            }
            if (isset($input['email'])) {
                $clienteToUpdate->email = trim($input['email']);
            }
            
            if ($clienteToUpdate->save()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Cliente atualizado com sucesso',
                    'data' => $clienteToUpdate->data()
                ]);
            } else {
                http_response_code(400);
                $errorMessage = 'Erro ao atualizar cliente';
                if ($clienteToUpdate->fail()) {
                    $errorMessage = $clienteToUpdate->fail()->getMessage();
                }
                
                echo json_encode([
                    'success' => false,
                    'message' => $errorMessage
                ]);
            }
            break;
            
        case 'DELETE':
            // Deletar cliente
            $id = null;
            
            // Pode vir do corpo da requisição ou da URL
            if (isset($input['clienteid'])) {
                $id = (int)$input['clienteid'];
            } elseif (isset($_GET['id'])) {
                $id = (int)$_GET['id'];
            }
            
            if (empty($id)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID do cliente é obrigatório'
                ]);
                break;
            }
            
            $clienteToDelete = $cliente->findById($id);
            
            if (!$clienteToDelete) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Cliente não encontrado'
                ]);
                break;
            }
            
            if ($clienteToDelete->destroy()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Cliente deletado com sucesso'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro ao deletar cliente'
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
