<?php

require_once '../config/database.php';
require_once '../config/helpers.php';

use Model\Assinatura;
use Model\Plano;
use Model\Cliente;
use Model\Fatura;

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
        case 'listar_assinaturas':
            $limite = (int)($_GET['limite'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);
            $status = $_GET['status'] ?? '';
            
            $assinatura = new Assinatura();
            $assinaturas = $assinatura->listarTodas($limite, $offset, $status);
            $total = $assinatura->find($status ? "status = '{$status}'" : "")->count();
            
            echo json_encode([
                'success' => true,
                'data' => $assinaturas,
                'total' => $total,
                'limite' => $limite,
                'offset' => $offset
            ]);
            break;

        case 'buscar_assinatura':
            $clienteId = $_GET['cliente_id'] ?? null;
            $id = $_GET['id'] ?? null;
            
            $assinatura = new Assinatura();
            
            if ($clienteId) {
                $result = $assinatura->buscarAssinaturaAtiva($clienteId);
            } elseif ($id) {
                $result = $assinatura->findById($id);
                if ($result) {
                    $result = $assinatura->enriquecerDados($result);
                }
            } else {
                throw new Exception('Parâmetro de busca obrigatório');
            }
            
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            break;

        case 'listar_planos':
            $plano = new Plano();
            $planos = $plano->listarAtivos();
            
            echo json_encode([
                'success' => true,
                'data' => $planos
            ]);
            break;

        case 'estatisticas_assinaturas':
            $assinatura = new Assinatura();
            $estatisticas = $assinatura->contarPorStatus();
            $receitaMensal = $assinatura->getReceitaMensal();
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'por_status' => $estatisticas,
                    'receita_mensal' => $receitaMensal,
                    'receita_formatada' => 'R$ ' . number_format($receitaMensal, 2, ',', '.')
                ]
            ]);
            break;

        case 'assinaturas_vencidas':
            $assinatura = new Assinatura();
            $vencidas = $assinatura->verificarVencidas();
            
            echo json_encode([
                'success' => true,
                'data' => $vencidas
            ]);
            break;

        case 'listar_faturas':
            $clienteId = $_GET['cliente_id'] ?? null;
            $limite = (int)($_GET['limite'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);
            $status = $_GET['status'] ?? '';
            
            $fatura = new Fatura();
            
            if ($clienteId) {
                $faturas = $fatura->listarPorCliente($clienteId, $limite, $offset);
                $total = $fatura->find("cliente_id = {$clienteId}")->count();
            } else {
                $faturas = $fatura->listarTodas($limite, $offset, $status);
                $total = $fatura->find($status ? "status = '{$status}'" : "")->count();
            }
            
            echo json_encode([
                'success' => true,
                'data' => $faturas,
                'total' => $total,
                'limite' => $limite,
                'offset' => $offset
            ]);
            break;

        case 'faturas_vencidas':
            $fatura = new Fatura();
            $vencidas = $fatura->listarVencidas();
            
            echo json_encode([
                'success' => true,
                'data' => $vencidas
            ]);
            break;

        case 'estatisticas_faturas':
            $fatura = new Fatura();
            $estatisticas = $fatura->getEstatisticas();
            
            echo json_encode([
                'success' => true,
                'data' => $estatisticas
            ]);
            break;

        case 'receita_periodo':
            $dataInicio = $_GET['data_inicio'] ?? date('Y-m-01', strtotime('-6 months'));
            $dataFim = $_GET['data_fim'] ?? date('Y-m-d');
            
            $fatura = new Fatura();
            $receita = $fatura->getReceitaPorPeriodo($dataInicio, $dataFim);
            
            echo json_encode([
                'success' => true,
                'data' => $receita
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
        case 'criar_assinatura':
            if (empty($data['cliente_id']) || empty($data['plano_id'])) {
                throw new Exception('Cliente e plano são obrigatórios');
            }
            
            $assinatura = new Assinatura();
            $dataInicio = $data['data_inicio'] ?? date('Y-m-d');
            $diaVencimento = $data['dia_vencimento'] ?? 1;
            
            if ($assinatura->criarAssinatura($data['cliente_id'], $data['plano_id'], $dataInicio, $diaVencimento)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Assinatura criada com sucesso',
                    'data' => ['id' => $assinatura->id]
                ]);
            } else {
                throw new Exception($assinatura->fail->getMessage());
            }
            break;

        case 'gerar_fatura':
            if (empty($data['assinatura_id'])) {
                throw new Exception('ID da assinatura é obrigatório');
            }
            
            $fatura = new Fatura();
            if ($fatura->gerarFatura($data['assinatura_id'])) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Fatura gerada com sucesso',
                    'data' => ['id' => $fatura->id, 'numero' => $fatura->numero_fatura]
                ]);
            } else {
                throw new Exception($fatura->fail->getMessage());
            }
            break;

        case 'marcar_fatura_paga':
            if (empty($data['fatura_id'])) {
                throw new Exception('ID da fatura é obrigatório');
            }
            
            $fatura = new Fatura();
            $metodoPagamento = $data['metodo_pagamento'] ?? '';
            $observacoes = $data['observacoes'] ?? '';
            
            if ($fatura->marcarComoPaga($data['fatura_id'], $metodoPagamento, $observacoes)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Fatura marcada como paga'
                ]);
            } else {
                throw new Exception($fatura->fail->getMessage());
            }
            break;

        case 'cancelar_fatura':
            if (empty($data['fatura_id'])) {
                throw new Exception('ID da fatura é obrigatório');
            }
            
            $fatura = new Fatura();
            $motivo = $data['motivo'] ?? '';
            
            if ($fatura->cancelar($data['fatura_id'], $motivo)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Fatura cancelada'
                ]);
            } else {
                throw new Exception($fatura->fail->getMessage());
            }
            break;

        case 'processar_faturas_vencidas':
            $fatura = new Fatura();
            $totalMarcadas = $fatura->marcarVencidas();
            
            echo json_encode([
                'success' => true,
                'message' => "{$totalMarcadas} faturas marcadas como vencidas",
                'data' => ['total_marcadas' => $totalMarcadas]
            ]);
            break;

        default:
            throw new Exception('Ação não especificada');
    }
}

function handlePut($action)
{
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'alterar_plano_assinatura':
            if (empty($data['assinatura_id']) || empty($data['novo_plano_id'])) {
                throw new Exception('ID da assinatura e novo plano são obrigatórios');
            }
            
            $assinatura = new Assinatura();
            if ($assinatura->alterarPlano($data['assinatura_id'], $data['novo_plano_id'])) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Plano da assinatura alterado com sucesso'
                ]);
            } else {
                throw new Exception($assinatura->fail->getMessage());
            }
            break;

        case 'suspender_assinatura':
            if (empty($data['assinatura_id'])) {
                throw new Exception('ID da assinatura é obrigatório');
            }
            
            $assinatura = new Assinatura();
            $motivo = $data['motivo'] ?? '';
            
            if ($assinatura->suspender($data['assinatura_id'], $motivo)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Assinatura suspensa'
                ]);
            } else {
                throw new Exception($assinatura->fail->getMessage());
            }
            break;

        case 'reativar_assinatura':
            if (empty($data['assinatura_id'])) {
                throw new Exception('ID da assinatura é obrigatório');
            }
            
            $assinatura = new Assinatura();
            if ($assinatura->reativar($data['assinatura_id'])) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Assinatura reativada'
                ]);
            } else {
                throw new Exception($assinatura->fail->getMessage());
            }
            break;

        case 'cancelar_assinatura':
            if (empty($data['assinatura_id'])) {
                throw new Exception('ID da assinatura é obrigatório');
            }
            
            $assinatura = new Assinatura();
            $motivo = $data['motivo'] ?? '';
            
            if ($assinatura->cancelar($data['assinatura_id'], $motivo)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Assinatura cancelada'
                ]);
            } else {
                throw new Exception($assinatura->fail->getMessage());
            }
            break;

        default:
            throw new Exception('Ação não especificada');
    }
}
