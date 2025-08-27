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
    
    $usuario = new \Model\Usuario();
    
    switch ($method) {
        case 'GET':
            // Listar usuários ou buscar por ID
            if (isset($_GET['id']) && !empty($_GET['id'])) {
                $id = (int)$_GET['id'];
                $user = $usuario->findById($id);
                
                if ($user) {
                    $data = $user->data();
                    unset($data->senha); // Remover senha do retorno
                    
                    echo json_encode([
                        'success' => true,
                        'data' => $data
                    ]);

                } else {

                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Usuário não encontrado'
                    ]);
                }
                
            } else {
                // Listar todos os usuários com paginação
                $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 20;
                $offset = ($page - 1) * $limit;
                $search = isset($_GET['search']) ? trim($_GET['search']) : '';
                
                $usuarios = $usuario->listar($limit, $offset, $search);
                $total = $usuario->contar($search);
                $totalPages = ceil($total / $limit);
                
                echo json_encode([
                    'success' => true,
                    'data' => $usuarios,
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
            // Criar novo usuário
            if (empty($input['usuario']) || empty($input['senha'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Usuário e senha são obrigatórios'
                ]);
                break;
            }
            
            $usuario->usuario = trim($input['usuario']);
            $usuario->senha = $input['senha'];
            $usuario->nome = trim($input['nome'] ?? '');
            $usuario->email = trim($input['email'] ?? '');
            
            if ($usuario->save()) {
                $data = $usuario->data();
                unset($data->senha);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Usuário criado com sucesso',
                    'data' => $data
                ]);
            } else {
                http_response_code(400);
                $errorMessage = 'Erro ao criar usuário';
                if ($usuario->fail()) {
                    $errorMessage = $usuario->fail()->getMessage();
                }
                
                echo json_encode([
                    'success' => false,
                    'message' => $errorMessage
                ]);
            }
            break;
            
        case 'PUT':
            // Atualizar usuário
            if (empty($input['usuarioid'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID do usuário é obrigatório'
                ]);
                break;
            }
            
            $id = (int)$input['usuarioid'];
            $userToUpdate = $usuario->findById($id);
            
            if (!$userToUpdate) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Usuário não encontrado'
                ]);
                break;
            }
            
            // Atualizar campos
            if (!empty($input['usuario'])) {
                $userToUpdate->usuario = trim($input['usuario']);
            }
            if (!empty($input['senha'])) {
                $userToUpdate->senha = $input['senha'];
            }
            if (isset($input['nome'])) {
                $userToUpdate->nome = trim($input['nome']);
            }
            if (isset($input['email'])) {
                $userToUpdate->email = trim($input['email']);
            }
            
            if ($userToUpdate->save()) {
                $data = $userToUpdate->data();
                unset($data->senha);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Usuário atualizado com sucesso',
                    'data' => $data
                ]);
            } else {
                http_response_code(400);
                $errorMessage = 'Erro ao atualizar usuário';
                if ($userToUpdate->fail()) {
                    $errorMessage = $userToUpdate->fail()->getMessage();
                }
                
                echo json_encode([
                    'success' => false,
                    'message' => $errorMessage
                ]);
            }
            break;
            
        case 'DELETE':
            // Deletar usuário
            $id = null;
            
            // Pode vir do corpo da requisição ou da URL
            if (isset($input['usuarioid'])) {
                $id = (int)$input['usuarioid'];
            } elseif (isset($_GET['id'])) {
                $id = (int)$_GET['id'];
            }
            
            if (empty($id)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID do usuário é obrigatório'
                ]);
                break;
            }
            
            $userToDelete = $usuario->findById($id);
            
            if (!$userToDelete) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Usuário não encontrado'
                ]);
                break;
            }
            
            // Verificar se não é o usuário logado
            if ($userToDelete->usuario === $_SESSION['user']) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Você não pode deletar seu próprio usuário'
                ]);
                break;
            }
            
            if ($userToDelete->destroy()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Usuário deletado com sucesso'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro ao deletar usuário'
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
