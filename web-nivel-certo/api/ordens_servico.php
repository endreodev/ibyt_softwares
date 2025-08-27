<?php

require_once '../config/database.php';
require_once '../config/helpers.php';

use Model\OrdemServico;
use Model\Cliente;
use Model\Usuario;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    switch ($method) {
        case 'GET':
            handleGet($action);
            break;
            
        case 'POST':
            handlePost($action);
            break;
            
        case 'PUT':
            handlePut($action);
            break;
            
        default:
            throw new Exception('Método não permitido');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function handleGet($action)
{
    switch ($action) {
        case 'listar':
            $limite = (int)($_GET['limite'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);
            $status = $_GET['status'] ?? '';
            $busca = $_GET['busca'] ?? '';
            $clienteId = $_GET['cliente_id'] ?? null;
            $tecnicoId = $_GET['tecnico_id'] ?? null;
            
            $ordemServico = new OrdemServico();
            
            if ($clienteId) {
                $ordens = $ordemServico->listarPorCliente($clienteId, $limite, $offset);
                $total = $ordemServico->find("cliente_id = {$clienteId}")->count();
            } elseif ($tecnicoId) {
                $ordens = $ordemServico->listarPorTecnico($tecnicoId, $limite, $offset);
                $total = $ordemServico->find("tecnico_responsavel = {$tecnicoId}")->count();
            } else {
                $ordens = $ordemServico->listarTodas($limite, $offset, $status, $busca);
                $queryCount = "";
                $params = [];
                
                if ($status) {
                    $queryCount .= "status = '{$status}'";
                }
                if ($busca) {
                    $queryCount .= ($queryCount ? " AND " : "") . "(numero_os LIKE '%{$busca}%' OR descricao LIKE '%{$busca}%')";
                }
                
                $total = $ordemServico->find($queryCount)->count();
            }
            
            echo json_encode([
                'success' => true,
                'data' => $ordens,
                'total' => $total,
                'limite' => $limite,
                'offset' => $offset
            ]);
            break;

        case 'buscar':
            $id = $_GET['id'] ?? null;
            $numeroOS = $_GET['numero_os'] ?? null;
            
            $ordemServico = new OrdemServico();
            
            if ($id) {
                $result = $ordemServico->findById($id);
            } elseif ($numeroOS) {
                $result = $ordemServico->find("numero_os = :numero", "numero={$numeroOS}")->fetch();
            } else {
                throw new Exception('Parâmetro de busca obrigatório');
            }
            
            if ($result) {
                // Enriquecer dados
                $data = is_object($result) ? $result->data() : $result;
                
                // Buscar cliente
                $cliente = new Cliente();
                $clienteData = $cliente->findById($data->cliente_id);
                $data->cliente_nome = $clienteData ? $clienteData->nome_fantasia : null;
                
                // Buscar técnico responsável
                if ($data->tecnico_responsavel) {
                    $usuario = new Usuario();
                    $tecnicoData = $usuario->findById($data->tecnico_responsavel);
                    $data->tecnico_nome = $tecnicoData ? $tecnicoData->nome : null;
                }
                
                echo json_encode([
                    'success' => true,
                    'data' => $data
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Ordem de serviço não encontrada'
                ]);
            }
            break;

        case 'estatisticas':
            $ordemServico = new OrdemServico();
            $estatisticas = $ordemServico->getEstatisticas();
            $mediaAvaliacoes = $ordemServico->getMediaAvaliacoes();
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'por_status' => $estatisticas,
                    'media_avaliacoes' => $mediaAvaliacoes
                ]
            ]);
            break;

        case 'tecnicos_disponiveis':
            $usuario = new Usuario();
            $tecnicos = $usuario->find("tipo IN ('admin', 'tecnico') AND ativo = 1")
                ->order("nome ASC")
                ->fetch(true);
            
            $result = [];
            if ($tecnicos) {
                foreach ($tecnicos as $tecnico) {
                    $result[] = [
                        'id' => $tecnico->id,
                        'nome' => $tecnico->nome,
                        'tipo' => $tecnico->tipo
                    ];
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            break;

        case 'clientes_disponiveis':
            $cliente = new Cliente();
            $clientes = $cliente->find("status = 'ativo'")
                ->order("nome_fantasia ASC")
                ->fetch(true);
            
            $result = [];
            if ($clientes) {
                foreach ($clientes as $cli) {
                    $result[] = [
                        'id' => $cli->id,
                        'nome_fantasia' => $cli->nome_fantasia,
                        'cnpj' => $cli->cnpj
                    ];
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            break;

        case 'agenda_tecnico':
            $tecnicoId = $_GET['tecnico_id'] ?? null;
            $dataInicio = $_GET['data_inicio'] ?? date('Y-m-d');
            $dataFim = $_GET['data_fim'] ?? date('Y-m-d', strtotime('+7 days'));
            
            if (!$tecnicoId) {
                throw new Exception('Técnico ID é obrigatório');
            }
            
            $ordemServico = new OrdemServico();
            $agenda = $ordemServico->find(
                "tecnico_responsavel = :tecnico_id AND data_agendamento BETWEEN :data_inicio AND :data_fim",
                "tecnico_id={$tecnicoId}&data_inicio={$dataInicio}&data_fim={$dataFim}"
            )->order("data_agendamento ASC")->fetch(true);
            
            $result = [];
            if ($agenda) {
                foreach ($agenda as $os) {
                    $result[] = $os->data();
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            break;

        default:
            throw new Exception('Ação não especificada');
    }
}

function handlePost($action)
{
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'criar':
            // Validar dados obrigatórios
            if (empty($data['cliente_id'])) {
                throw new Exception('Cliente é obrigatório');
            }
            if (empty($data['tipo'])) {
                throw new Exception('Tipo de serviço é obrigatório');
            }
            if (empty($data['descricao'])) {
                throw new Exception('Descrição é obrigatória');
            }
            
            $ordemServico = new OrdemServico();
            
            $dadosOS = [
                'cliente_id' => $data['cliente_id'],
                'dispositivo_id' => $data['dispositivo_id'] ?? null,
                'reservatorio_id' => $data['reservatorio_id'] ?? null,
                'tipo' => $data['tipo'],
                'prioridade' => $data['prioridade'] ?? 'media',
                'descricao' => $data['descricao'],
                'tecnico_responsavel' => $data['tecnico_responsavel'] ?? null,
                'data_agendamento' => $data['data_agendamento'] ?? null
            ];
            
            if ($ordemServico->criarOS($dadosOS)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Ordem de serviço criada com sucesso',
                    'data' => [
                        'id' => $ordemServico->id,
                        'numero_os' => $ordemServico->numero_os
                    ]
                ]);
            } else {
                throw new Exception($ordemServico->fail->getMessage());
            }
            break;

        case 'atribuir_tecnico':
            if (empty($data['os_id']) || empty($data['tecnico_id'])) {
                throw new Exception('OS ID e Técnico ID são obrigatórios');
            }
            
            $ordemServico = new OrdemServico();
            if ($ordemServico->atribuirTecnico($data['os_id'], $data['tecnico_id'])) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Técnico atribuído com sucesso'
                ]);
            } else {
                throw new Exception($ordemServico->fail->getMessage());
            }
            break;

        case 'avaliar':
            if (empty($data['os_id']) || empty($data['avaliacao'])) {
                throw new Exception('OS ID e avaliação são obrigatórios');
            }
            
            $ordemServico = new OrdemServico();
            $comentario = $data['comentario'] ?? '';
            
            if ($ordemServico->avaliar($data['os_id'], $data['avaliacao'], $comentario)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Avaliação registrada com sucesso'
                ]);
            } else {
                throw new Exception($ordemServico->fail->getMessage());
            }
            break;

        default:
            throw new Exception('Ação não especificada');
    }
}

function handlePut($action)
{
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'alterar_status':
            if (empty($data['os_id']) || empty($data['status'])) {
                throw new Exception('OS ID e status são obrigatórios');
            }
            
            $ordemServico = new OrdemServico();
            $observacoes = $data['observacoes'] ?? '';
            
            if ($ordemServico->alterarStatus($data['os_id'], $data['status'], $observacoes)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Status alterado com sucesso'
                ]);
            } else {
                throw new Exception($ordemServico->fail->getMessage());
            }
            break;

        case 'agendar':
            if (empty($data['os_id']) || empty($data['data_agendamento'])) {
                throw new Exception('OS ID e data de agendamento são obrigatórios');
            }
            
            $ordemServico = new OrdemServico();
            if ($ordemServico->agendar($data['os_id'], $data['data_agendamento'])) {
                echo json_encode([
                    'success' => true,
                    'message' => 'OS agendada com sucesso'
                ]);
            } else {
                throw new Exception($ordemServico->fail->getMessage());
            }
            break;

        case 'atualizar':
            if (empty($data['id'])) {
                throw new Exception('ID da OS é obrigatório');
            }
            
            $ordemServico = new OrdemServico();
            $os = $ordemServico->findById($data['id']);
            
            if (!$os) {
                throw new Exception('Ordem de serviço não encontrada');
            }
            
            // Atualizar campos permitidos
            if (isset($data['tipo'])) $os->tipo = $data['tipo'];
            if (isset($data['prioridade'])) $os->prioridade = $data['prioridade'];
            if (isset($data['descricao'])) $os->descricao = $data['descricao'];
            if (isset($data['tecnico_responsavel'])) $os->tecnico_responsavel = $data['tecnico_responsavel'];
            if (isset($data['data_agendamento'])) $os->data_agendamento = $data['data_agendamento'];
            if (isset($data['observacoes_tecnico'])) $os->observacoes_tecnico = $data['observacoes_tecnico'];
            
            if ($os->save()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Ordem de serviço atualizada com sucesso'
                ]);
            } else {
                throw new Exception($os->fail->getMessage());
            }
            break;

        default:
            throw new Exception('Ação não especificada');
    }
}
