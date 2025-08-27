<?php

require_once '../config/database.php';
require_once '../config/helpers.php';

use CoffeeCode\DataLayer\Connect;
use Model\Cliente;
use Model\Dispositivo;
use Model\Assinatura;
use Model\Fatura;
use Model\OrdemServico;
use Model\Reservatorio;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'admin':
            echo json_encode(getDashboardAdmin());
            break;
        
        case 'cliente':
            $clienteId = $_GET['cliente_id'] ?? null;
            if (!$clienteId) {
                throw new Exception('Cliente ID é obrigatório');
            }
            echo json_encode(getDashboardCliente($clienteId));
            break;
            
        default:
            throw new Exception('Ação não especificada');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function getDashboardAdmin(): array
{
    $cliente = new Cliente();
    $dispositivo = new Dispositivo();
    $assinatura = new Assinatura();
    $fatura = new Fatura();
    $ordemServico = new OrdemServico();

    // Estatísticas gerais
    $totalClientes = $cliente->find()->count();
    $totalDispositivos = $dispositivo->find()->count();
    $totalAssinaturas = $assinatura->find("status = 'ativa'")->count();
    $receitaMensal = $assinatura->getReceitaMensal();

    // Estatísticas de clientes por status
    $clientesAtivos = $cliente->find("status = 'ativo'")->count();
    $clientesSuspensos = $cliente->find("status = 'suspenso'")->count();
    $clientesCancelados = $cliente->find("status = 'cancelado'")->count();

    // Estatísticas de dispositivos
    $dispositivosAtivos = $dispositivo->find("status = 'ativo'")->count();
    $dispositivosOffline = count($dispositivo->listarOffline(30));
    $dispositivosBateriaBaixa = count($dispositivo->listarBateriaBaixa(20));

    // Estatísticas de faturas
    $estatisticasFaturas = $fatura->getEstatisticas();

    // Estatísticas de ordens de serviço
    $estatisticasOS = $ordemServico->getEstatisticas();

    // Receita dos últimos 6 meses
    $dataInicio = date('Y-m-d', strtotime('-6 months'));
    $dataFim = date('Y-m-d');
    $receitaPorMes = $fatura->getReceitaPorPeriodo($dataInicio, $dataFim);

    // Assinaturas por plano
    $plano = new Model\Plano();
    $estatisticasPlanos = $plano->getEstatisticas();

    // Alertas recentes
    $alertasRecentes = getAlertasRecentes(10);

    // Dispositivos que precisam de atenção
    $dispositivosAtencao = [
        'offline' => $dispositivo->listarOffline(60), // Offline há mais de 1 hora
        'bateria_baixa' => $dispositivo->listarBateriaBaixa(15)
    ];

    return [
        'success' => true,
        'data' => [
            'resumo' => [
                'total_clientes' => $totalClientes,
                'total_dispositivos' => $totalDispositivos,
                'total_assinaturas' => $totalAssinaturas,
                'receita_mensal' => number_format($receitaMensal, 2, ',', '.'),
                'receita_mensal_valor' => $receitaMensal
            ],
            'clientes' => [
                'ativos' => $clientesAtivos,
                'suspensos' => $clientesSuspensos,
                'cancelados' => $clientesCancelados
            ],
            'dispositivos' => [
                'ativos' => $dispositivosAtivos,
                'offline' => $dispositivosOffline,
                'bateria_baixa' => $dispositivosBateriaBaixa,
                'total' => $totalDispositivos
            ],
            'faturas' => $estatisticasFaturas,
            'ordens_servico' => $estatisticasOS,
            'receita_historica' => $receitaPorMes,
            'planos' => $estatisticasPlanos,
            'alertas_recentes' => $alertasRecentes,
            'dispositivos_atencao' => $dispositivosAtencao
        ]
    ];
}

function getDashboardCliente(int $clienteId): array
{
    $cliente = new Cliente();
    $dispositivo = new Dispositivo();
    $reservatorio = new Model\ReservatorioNovo();
    $assinatura = new Assinatura();
    $fatura = new Fatura();

    // Dados do cliente
    $dadosCliente = $cliente->findById($clienteId);
    if (!$dadosCliente) {
        throw new Exception('Cliente não encontrado');
    }

    // Assinatura ativa
    $assinaturaAtiva = $assinatura->buscarAssinaturaAtiva($clienteId);

    // Dispositivos do cliente
    $totalDispositivos = $dispositivo->contar($clienteId);
    $dispositivosAtivos = $dispositivo->find("cliente_id = :cliente_id AND status = 'ativo'", 
        "cliente_id={$clienteId}")->count();

    // Reservatórios do cliente
    $totalReservatorios = $reservatorio->contar($clienteId);
    $reservatoriosAtivos = $reservatorio->find("cliente_id = :cliente_id AND status = 'ativo'", 
        "cliente_id={$clienteId}")->count();

    // Última fatura
    $ultimaFatura = $fatura->find("cliente_id = :cliente_id", "cliente_id={$clienteId}")
        ->order("created_at DESC")
        ->fetch();

    // Faturas pendentes
    $faturasPendentes = $fatura->find("cliente_id = :cliente_id AND status IN ('pendente', 'vencida')", 
        "cliente_id={$clienteId}")
        ->fetch(true);

    // Últimas medições
    $ultimasMedicoes = getUltimasMedicoes($clienteId, 5);

    // Alertas do cliente
    $alertasCliente = getAlertasCliente($clienteId, 10);

    // Estatísticas dos reservatórios
    $estatisticasReservatorios = [];
    $reservatoriosCliente = $reservatorio->listarPorCliente($clienteId, 10);
    foreach ($reservatoriosCliente as $res) {
        $stats = $reservatorio->getEstatisticas($res->id, 7); // Últimos 7 dias
        $ultimaMedicao = $reservatorio->getUltimaMedicao($res->id);
        
        $estatisticasReservatorios[] = [
            'id' => $res->id,
            'nome' => $res->nome,
            'capacidade_total' => $res->capacidade_total,
            'status' => $res->status,
            'ultima_medicao' => $ultimaMedicao,
            'estatisticas' => $stats
        ];
    }

    return [
        'success' => true,
        'data' => [
            'cliente' => [
                'id' => $dadosCliente->id,
                'nome_fantasia' => $dadosCliente->nome_fantasia,
                'cnpj' => $dadosCliente->cnpj,
                'status' => $dadosCliente->status
            ],
            'assinatura' => $assinaturaAtiva,
            'resumo' => [
                'total_dispositivos' => $totalDispositivos,
                'dispositivos_ativos' => $dispositivosAtivos,
                'total_reservatorios' => $totalReservatorios,
                'reservatorios_ativos' => $reservatoriosAtivos
            ],
            'faturamento' => [
                'ultima_fatura' => $ultimaFatura ? [
                    'numero' => $ultimaFatura->numero_fatura,
                    'valor' => $ultimaFatura->valor,
                    'status' => $ultimaFatura->status,
                    'data_vencimento' => $ultimaFatura->data_vencimento
                ] : null,
                'faturas_pendentes' => count($faturasPendentes ?? [])
            ],
            'medicoes_recentes' => $ultimasMedicoes,
            'alertas' => $alertasCliente,
            'reservatorios' => $estatisticasReservatorios
        ]
    ];
}

function getAlertasRecentes(int $limite = 10): array
{
    try {
        $connect = Connect::getInstance();
        $query = "
            SELECT a.*, c.nome_fantasia as cliente_nome, 
                   r.nome as reservatorio_nome, d.codigo_serie as dispositivo_codigo
            FROM alertas a
            LEFT JOIN clientes c ON a.cliente_id = c.id
            LEFT JOIN reservatorios r ON a.reservatorio_id = r.id
            LEFT JOIN dispositivos d ON a.dispositivo_id = d.id
            WHERE a.status IN ('novo', 'visualizado')
            ORDER BY a.created_at DESC
            LIMIT {$limite}
        ";

        $stmt = $connect->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

function getAlertasCliente(int $clienteId, int $limite = 10): array
{
    try {
        $connect = Connect::getInstance();
        $query = "
            SELECT a.*, r.nome as reservatorio_nome, d.codigo_serie as dispositivo_codigo
            FROM alertas a
            LEFT JOIN reservatorios r ON a.reservatorio_id = r.id
            LEFT JOIN dispositivos d ON a.dispositivo_id = d.id
            WHERE a.cliente_id = :cliente_id AND a.status IN ('novo', 'visualizado')
            ORDER BY a.created_at DESC
            LIMIT {$limite}
        ";

        $stmt = $connect->prepare($query);
        $stmt->bindValue(':cliente_id', $clienteId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

function getUltimasMedicoes(int $clienteId, int $limite = 5): array
{
    try {
        $connect = Connect::getInstance();
        $query = "
            SELECT m.*, r.nome as reservatorio_nome, d.codigo_serie as dispositivo_codigo,
                   r.capacidade_total
            FROM medicoes m
            LEFT JOIN reservatorios r ON m.reservatorio_id = r.id
            LEFT JOIN dispositivos d ON m.dispositivo_id = d.id
            WHERE r.cliente_id = :cliente_id
            ORDER BY m.data_medicao DESC
            LIMIT {$limite}
        ";

        $stmt = $connect->prepare($query);
        $stmt->bindValue(':cliente_id', $clienteId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}
