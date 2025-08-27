<?php

namespace Model;

use CoffeeCode\DataLayer\DataLayer;

class Dispositivo extends DataLayer
{
    /**
     * Dispositivo constructor.
     */
    public function __construct()
    {
        parent::__construct("dispositivos", ["codigo_serie", "mac_address", "modelo", "cliente_id"], "id", true);
    }

    /**
     * Valida dados do dispositivo
     */
    public function validar(): bool
    {
        if (empty($this->codigo_serie)) {
            $this->fail = new \Exception("Código de série é obrigatório");
            return false;
        }

        if (empty($this->mac_address)) {
            $this->fail = new \Exception("MAC Address é obrigatório");
            return false;
        }

        if (!$this->validarMacAddress($this->mac_address)) {
            $this->fail = new \Exception("MAC Address inválido");
            return false;
        }

        if (empty($this->modelo)) {
            $this->fail = new \Exception("Modelo é obrigatório");
            return false;
        }

        if (empty($this->cliente_id)) {
            $this->fail = new \Exception("Cliente é obrigatório");
            return false;
        }

        // Verificar se código de série já existe
        if (empty($this->id)) {
            $existingDevice = $this->find("codigo_serie = :codigo", "codigo={$this->codigo_serie}")->fetch();
            if ($existingDevice) {
                $this->fail = new \Exception("Código de série já cadastrado");
                return false;
            }
        } else {
            $existingDevice = $this->find("codigo_serie = :codigo AND id != :id", 
                "codigo={$this->codigo_serie}&id={$this->id}")->fetch();
            if ($existingDevice) {
                $this->fail = new \Exception("Código de série já cadastrado");
                return false;
            }
        }

        // Verificar se MAC address já existe
        if (empty($this->id)) {
            $existingMac = $this->find("mac_address = :mac", "mac={$this->mac_address}")->fetch();
            if ($existingMac) {
                $this->fail = new \Exception("MAC Address já cadastrado");
                return false;
            }
        } else {
            $existingMac = $this->find("mac_address = :mac AND id != :id", 
                "mac={$this->mac_address}&id={$this->id}")->fetch();
            if ($existingMac) {
                $this->fail = new \Exception("MAC Address já cadastrado");
                return false;
            }
        }

        return true;
    }

    /**
     * Valida MAC Address
     */
    private function validarMacAddress(string $mac): bool
    {
        return preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $mac);
    }

    /**
     * Salva dispositivo com validação
     */
    public function save(): bool
    {
        if (!$this->validar()) {
            return false;
        }

        // Normalizar MAC address
        $this->mac_address = strtoupper(str_replace('-', ':', $this->mac_address));

        return parent::save();
    }

    /**
     * Lista dispositivos por cliente
     */
    public function listarPorCliente(int $clienteId, int $limite = 50, int $offset = 0): array
    {
        $dispositivos = $this->find("cliente_id = :cliente_id", "cliente_id={$clienteId}")
            ->order("created_at DESC")
            ->limit($limite)
            ->offset($offset)
            ->fetch(true);

        if (!$dispositivos) {
            return [];
        }

        $result = [];
        foreach ($dispositivos as $dispositivo) {
            $data = $dispositivo->data();
            $data->status_texto = $this->getStatusTexto($data->status);
            $data->tempo_offline = $this->calcularTempoOffline($data->ultima_comunicacao);
            $result[] = $data;
        }

        return $result;
    }

    /**
     * Lista todos os dispositivos com informações do cliente
     */
    public function listarTodos(int $limite = 50, int $offset = 0, string $busca = ''): array
    {
        $query = "
            SELECT d.*, c.nome_fantasia as cliente_nome, r.nome as reservatorio_nome
            FROM dispositivos d
            LEFT JOIN clientes c ON d.cliente_id = c.id
            LEFT JOIN reservatorios r ON d.reservatorio_id = r.id
        ";

        $params = [];
        if (!empty($busca)) {
            $query .= " WHERE (d.codigo_serie LIKE :busca OR d.mac_address LIKE :busca OR d.modelo LIKE :busca OR c.nome_fantasia LIKE :busca)";
            $params['busca'] = "%{$busca}%";
        }

        $query .= " ORDER BY d.created_at DESC LIMIT :limite OFFSET :offset";
        $params['limite'] = $limite;
        $params['offset'] = $offset;

        try {
            $stmt = \CoffeeCode\DataLayer\Connect::getInstance()->prepare($query);
            
            // Bind dos parâmetros
            foreach ($params as $key => $value) {
                if ($key === 'limite' || $key === 'offset') {
                    $stmt->bindValue(":{$key}", (int)$value, \PDO::PARAM_INT);
                } else {
                    $stmt->bindValue(":{$key}", $value, \PDO::PARAM_STR);
                }
            }
            
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Contar dispositivos
     */
    public function contar(int $clienteId = null, string $busca = ''): int
    {
        $query = $this->find();

        if ($clienteId) {
            $query = $query->find("cliente_id = :cliente_id", "cliente_id={$clienteId}");
        }

        if (!empty($busca)) {
            $searchQuery = "codigo_serie LIKE :busca OR mac_address LIKE :busca OR modelo LIKE :busca";
            if ($clienteId) {
                $query = $query->find($searchQuery, "busca=%{$busca}%");
            } else {
                $query = $query->find($searchQuery, "busca=%{$busca}%");
            }
        }

        return $query->count();
    }

    /**
     * Buscar dispositivo por código de série
     */
    public function buscarPorCodigoSerie(string $codigoSerie)
    {
        return $this->find("codigo_serie = :codigo", "codigo={$codigoSerie}")->fetch();
    }

    /**
     * Buscar dispositivo por MAC address
     */
    public function buscarPorMacAddress(string $macAddress)
    {
        $macAddress = strtoupper(str_replace('-', ':', $macAddress));
        return $this->find("mac_address = :mac", "mac={$macAddress}")->fetch();
    }

    /**
     * Atualizar última comunicação
     */
    public function atualizarComunicacao(int $dispositivoId, int $nivelBateria = null, int $intensidadeSinal = null): bool
    {
        $dispositivo = $this->findById($dispositivoId);
        if (!$dispositivo) {
            return false;
        }

        $dispositivo->ultima_comunicacao = date('Y-m-d H:i:s');
        
        if ($nivelBateria !== null) {
            $dispositivo->nivel_bateria = $nivelBateria;
        }
        
        if ($intensidadeSinal !== null) {
            $dispositivo->intensidade_sinal = $intensidadeSinal;
        }

        return $dispositivo->save();
    }

    /**
     * Vincular dispositivo ao reservatório
     */
    public function vincularReservatorio(int $dispositivoId, int $reservatorioId): bool
    {
        $dispositivo = $this->findById($dispositivoId);
        if (!$dispositivo) {
            return false;
        }

        $dispositivo->reservatorio_id = $reservatorioId;
        return $dispositivo->save();
    }

    /**
     * Desvincular dispositivo do reservatório
     */
    public function desvincularReservatorio(int $dispositivoId): bool
    {
        $dispositivo = $this->findById($dispositivoId);
        if (!$dispositivo) {
            return false;
        }

        $dispositivo->reservatorio_id = null;
        return $dispositivo->save();
    }

    /**
     * Alterar status do dispositivo
     */
    public function alterarStatus(int $dispositivoId, string $status): bool
    {
        $statusValidos = ['ativo', 'inativo', 'manutencao', 'defeito'];
        if (!in_array($status, $statusValidos)) {
            $this->fail = new \Exception("Status inválido");
            return false;
        }

        $dispositivo = $this->findById($dispositivoId);
        if (!$dispositivo) {
            return false;
        }

        $dispositivo->status = $status;
        return $dispositivo->save();
    }

    /**
     * Listar dispositivos offline há mais de X minutos
     */
    public function listarOffline(int $minutos = 30): array
    {
        $dataLimite = date('Y-m-d H:i:s', strtotime("-{$minutos} minutes"));
        
        $dispositivos = $this->find("(ultima_comunicacao IS NULL OR ultima_comunicacao < :data_limite) AND status = 'ativo'", 
            "data_limite={$dataLimite}")
            ->fetch(true);

        if (!$dispositivos) {
            return [];
        }

        $result = [];
        foreach ($dispositivos as $dispositivo) {
            $result[] = $dispositivo->data();
        }

        return $result;
    }

    /**
     * Listar dispositivos com bateria baixa
     */
    public function listarBateriaBaixa(int $nivelMinimo = 20): array
    {
        $dispositivos = $this->find("nivel_bateria <= :nivel AND status = 'ativo'", "nivel={$nivelMinimo}")
            ->fetch(true);

        if (!$dispositivos) {
            return [];
        }

        $result = [];
        foreach ($dispositivos as $dispositivo) {
            $result[] = $dispositivo->data();
        }

        return $result;
    }

    /**
     * Obter texto do status
     */
    private function getStatusTexto(string $status): string
    {
        $statusMap = [
            'ativo' => 'Ativo',
            'inativo' => 'Inativo',
            'manutencao' => 'Em Manutenção',
            'defeito' => 'Com Defeito'
        ];

        return $statusMap[$status] ?? 'Desconhecido';
    }

    /**
     * Calcular tempo offline
     */
    private function calcularTempoOffline($ultimaComunicacao): string
    {
        if (!$ultimaComunicacao) {
            return 'Nunca conectou';
        }

        $agora = new \DateTime();
        $ultima = new \DateTime($ultimaComunicacao);
        $diff = $agora->diff($ultima);

        if ($diff->days > 0) {
            return $diff->days . ' dia(s) offline';
        } elseif ($diff->h > 0) {
            return $diff->h . ' hora(s) offline';
        } elseif ($diff->i > 0) {
            return $diff->i . ' minuto(s) offline';
        } else {
            return 'Online';
        }
    }
}
