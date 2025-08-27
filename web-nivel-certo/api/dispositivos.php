<?php

require_once '../config/database.php';
require_once '../config/helpers.php';

use Model\Dispositivo;
use Model\Cliente;
use Model\ReservatorioNovo as Reservatorio;

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
    $dispositivo = new Dispositivo();

    switch ($method) {
        case 'GET':
            handleGet($dispositivo, $action);
            break;
            
        case 'POST':
            handlePost($dispositivo, $action);
            break;
            
        case 'PUT':
            handlePut($dispositivo, $action);
            break;
            
        case 'DELETE':
            handleDelete($dispositivo, $action);
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

function handleGet($dispositivo, $action)
{
    switch ($action) {
        case 'listar':
            $clienteId = $_GET['cliente_id'] ?? null;
            $limite = (int)($_GET['limite'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);
            $busca = $_GET['busca'] ?? '';

            if ($clienteId) {
                $dispositivos = $dispositivo->listarPorCliente($clienteId, $limite, $offset);
                $total = $dispositivo->contar($clienteId, $busca);
            } else {
                $dispositivos = $dispositivo->listarTodos($limite, $offset, $busca);
                $total = $dispositivo->contar(null, $busca);
            }

            echo json_encode([
                'success' => true,
                'data' => $dispositivos,
                'total' => $total,
                'limite' => $limite,
                'offset' => $offset
            ]);
            break;

        case 'buscar':
            $id = $_GET['id'] ?? null;
            $codigoSerie = $_GET['codigo_serie'] ?? null;
            $macAddress = $_GET['mac_address'] ?? null;

            if ($id) {
                $result = $dispositivo->findById($id);
            } elseif ($codigoSerie) {
                $result = $dispositivo->buscarPorCodigoSerie($codigoSerie);
            } elseif ($macAddress) {
                $result = $dispositivo->buscarPorMacAddress($macAddress);
            } else {
                throw new Exception('Parâmetro de busca obrigatório');
            }

            if ($result) {
                // Buscar informações adicionais
                $data = $result->data();
                
                // Buscar cliente
                $cliente = new Cliente();
                $clienteData = $cliente->findById($data->cliente_id);
                $data->cliente_nome = $clienteData ? $clienteData->nome_fantasia : null;
                
                // Buscar reservatório vinculado
                if ($data->reservatorio_id) {
                    $reservatorio = new Reservatorio();
                    $reservatorioData = $reservatorio->findById($data->reservatorio_id);
                    $data->reservatorio_nome = $reservatorioData ? $reservatorioData->nome : null;
                }

                echo json_encode([
                    'success' => true,
                    'data' => $data
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Dispositivo não encontrado'
                ]);
            }
            break;

        case 'offline':
            $minutos = (int)($_GET['minutos'] ?? 30);
            $dispositivos = $dispositivo->listarOffline($minutos);
            
            echo json_encode([
                'success' => true,
                'data' => $dispositivos
            ]);
            break;

        case 'bateria_baixa':
            $nivelMinimo = (int)($_GET['nivel_minimo'] ?? 20);
            $dispositivos = $dispositivo->listarBateriaBaixa($nivelMinimo);
            
            echo json_encode([
                'success' => true,
                'data' => $dispositivos
            ]);
            break;

        case 'clientes_disponiveis':
            $cliente = new Cliente();
            $clientes = $cliente->find("status = 'ativo'")->order("nome_fantasia ASC")->fetch(true);
            
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

        case 'reservatorios_disponiveis':
            $clienteId = $_GET['cliente_id'] ?? null;
            if (!$clienteId) {
                throw new Exception('Cliente ID é obrigatório');
            }
            
            $reservatorio = new Reservatorio();
            $reservatorios = $reservatorio->listarSemDispositivo();
            
            // Filtrar por cliente se especificado
            $result = array_filter($reservatorios, function($res) use ($clienteId) {
                return $res->cliente_id == $clienteId;
            });
            
            echo json_encode([
                'success' => true,
                'data' => array_values($result)
            ]);
            break;

        default:
            throw new Exception('Ação não especificada');
    }
}

function handlePost($dispositivo, $action)
{
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'criar':
            // Validar dados obrigatórios
            if (empty($data['codigo_serie'])) {
                throw new Exception('Código de série é obrigatório');
            }
            if (empty($data['mac_address'])) {
                throw new Exception('MAC Address é obrigatório');
            }
            if (empty($data['modelo'])) {
                throw new Exception('Modelo é obrigatório');
            }
            if (empty($data['cliente_id'])) {
                throw new Exception('Cliente é obrigatório');
            }

            // Criar dispositivo
            $dispositivo->codigo_serie = $data['codigo_serie'];
            $dispositivo->mac_address = $data['mac_address'];
            $dispositivo->modelo = $data['modelo'];
            $dispositivo->versao_firmware = $data['versao_firmware'] ?? null;
            $dispositivo->cliente_id = $data['cliente_id'];
            $dispositivo->reservatorio_id = $data['reservatorio_id'] ?? null;
            $dispositivo->status = $data['status'] ?? 'ativo';
            $dispositivo->data_instalacao = $data['data_instalacao'] ?? date('Y-m-d');
            $dispositivo->localizacao_descricao = $data['localizacao_descricao'] ?? null;
            $dispositivo->latitude = $data['latitude'] ?? null;
            $dispositivo->longitude = $data['longitude'] ?? null;
            $dispositivo->observacoes = $data['observacoes'] ?? null;

            if ($dispositivo->save()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Dispositivo cadastrado com sucesso',
                    'data' => ['id' => $dispositivo->id]
                ]);
            } else {
                throw new Exception($dispositivo->fail->getMessage());
            }
            break;

        case 'vincular_reservatorio':
            if (empty($data['dispositivo_id']) || empty($data['reservatorio_id'])) {
                throw new Exception('Dispositivo ID e Reservatório ID são obrigatórios');
            }

            if ($dispositivo->vincularReservatorio($data['dispositivo_id'], $data['reservatorio_id'])) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Dispositivo vinculado ao reservatório com sucesso'
                ]);
            } else {
                throw new Exception($dispositivo->fail->getMessage());
            }
            break;

        case 'desvincular_reservatorio':
            if (empty($data['dispositivo_id'])) {
                throw new Exception('Dispositivo ID é obrigatório');
            }

            if ($dispositivo->desvincularReservatorio($data['dispositivo_id'])) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Dispositivo desvinculado do reservatório com sucesso'
                ]);
            } else {
                throw new Exception($dispositivo->fail->getMessage());
            }
            break;

        case 'atualizar_comunicacao':
            if (empty($data['dispositivo_id'])) {
                throw new Exception('Dispositivo ID é obrigatório');
            }

            $nivelBateria = $data['nivel_bateria'] ?? null;
            $intensidadeSinal = $data['intensidade_sinal'] ?? null;

            if ($dispositivo->atualizarComunicacao($data['dispositivo_id'], $nivelBateria, $intensidadeSinal)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Comunicação atualizada com sucesso'
                ]);
            } else {
                throw new Exception('Erro ao atualizar comunicação');
            }
            break;

        default:
            throw new Exception('Ação não especificada');
    }
}

function handlePut($dispositivo, $action)
{
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'atualizar':
            if (empty($data['id'])) {
                throw new Exception('ID do dispositivo é obrigatório');
            }

            $device = $dispositivo->findById($data['id']);
            if (!$device) {
                throw new Exception('Dispositivo não encontrado');
            }

            // Atualizar campos
            if (isset($data['codigo_serie'])) $device->codigo_serie = $data['codigo_serie'];
            if (isset($data['mac_address'])) $device->mac_address = $data['mac_address'];
            if (isset($data['modelo'])) $device->modelo = $data['modelo'];
            if (isset($data['versao_firmware'])) $device->versao_firmware = $data['versao_firmware'];
            if (isset($data['cliente_id'])) $device->cliente_id = $data['cliente_id'];
            if (isset($data['reservatorio_id'])) $device->reservatorio_id = $data['reservatorio_id'];
            if (isset($data['status'])) $device->status = $data['status'];
            if (isset($data['data_instalacao'])) $device->data_instalacao = $data['data_instalacao'];
            if (isset($data['localizacao_descricao'])) $device->localizacao_descricao = $data['localizacao_descricao'];
            if (isset($data['latitude'])) $device->latitude = $data['latitude'];
            if (isset($data['longitude'])) $device->longitude = $data['longitude'];
            if (isset($data['observacoes'])) $device->observacoes = $data['observacoes'];

            if ($device->save()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Dispositivo atualizado com sucesso'
                ]);
            } else {
                throw new Exception($device->fail->getMessage());
            }
            break;

        case 'alterar_status':
            if (empty($data['id']) || empty($data['status'])) {
                throw new Exception('ID e status são obrigatórios');
            }

            if ($dispositivo->alterarStatus($data['id'], $data['status'])) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Status alterado com sucesso'
                ]);
            } else {
                throw new Exception($dispositivo->fail->getMessage());
            }
            break;

        default:
            throw new Exception('Ação não especificada');
    }
}

function handleDelete($dispositivo, $action)
{
    switch ($action) {
        case 'excluir':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                throw new Exception('ID do dispositivo é obrigatório');
            }

            $device = $dispositivo->findById($id);
            if (!$device) {
                throw new Exception('Dispositivo não encontrado');
            }

            if ($device->destroy()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Dispositivo excluído com sucesso'
                ]);
            } else {
                throw new Exception('Erro ao excluir dispositivo');
            }
            break;

        default:
            throw new Exception('Ação não especificada');
    }
}
