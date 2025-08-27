<?php

namespace Model;

use CoffeeCode\DataLayer\DataLayer;

class Reservatorio extends DataLayer
{
    /**
     * Reservatorio constructor.
     */
    public function __construct()
    {
        parent::__construct("reservatorios", ["cliente_id", "nome", "capacidade_total", "altura_total"], "id", true);
    }

    /**
     * Valida dados do reservatório
     */
    public function validar(): bool
    {
        if (empty($this->cliente_id)) {
            $this->fail = new \Exception("Cliente é obrigatório");
            return false;
        }

        if (empty($this->nome) || strlen($this->nome) < 3) {
            $this->fail = new \Exception("Nome deve ter pelo menos 3 caracteres");
            return false;
        }

        if (empty($this->capacidade_total) || $this->capacidade_total <= 0) {
            $this->fail = new \Exception("Capacidade total deve ser maior que zero");
            return false;
        }

        if (empty($this->altura_total) || $this->altura_total <= 0) {
            $this->fail = new \Exception("Altura total deve ser maior que zero");
            return false;
        }

        // Verificar se o cliente existe
        $cliente = new Cliente();
        $clienteExiste = $cliente->findById($this->cliente_id);
        
        if (!$clienteExiste) {
            $this->fail = new \Exception("Cliente não encontrado");
            return false;
        }

        // Validar níveis
        if ($this->nivel_min >= $this->nivel_max) {
            $this->fail = new \Exception("Nível mínimo deve ser menor que o máximo");
            return false;
        }

        if ($this->nivel_critico > $this->nivel_min) {
            $this->fail = new \Exception("Nível crítico deve ser menor ou igual ao mínimo");
            return false;
        }

        return true;
    }

    /**
     * Salva reservatório com validação
     */
    public function save(): bool
    {
        if (!$this->validar()) {
            return false;
        }

        return parent::save();
    }

    /**
     * Lista reservatórios por cliente
     */
    public function listarPorCliente(int $clienteId, int $limite = 50, int $offset = 0): array
    {
        $reservatorios = $this->find("cliente_id = :cliente_id", "cliente_id={$clienteId}")
            ->order("created_at DESC")
            ->limit($limite)
            ->offset($offset)
            ->fetch(true);

        if (!$reservatorios) {
            return [];
        }

        $result = [];
        foreach ($reservatorios as $reservatorio) {
            $data = $reservatorio->data();
            $data->status_texto = $this->getStatusTexto($data->status);
            $data->possui_dispositivo = $this->possuiDispositivo($data->id);
            $data->ultima_medicao = $this->getUltimaMedicao($data->id);
            $result[] = $data;
        }

        return $result;
    }

    /**
     * Lista todos os reservatórios com informações do cliente
     */
    public function listarTodos(int $limite = 50, int $offset = 0, string $busca = ''): array
    {
        $query = "
            SELECT r.*, c.nome_fantasia as cliente_nome, c.cnpj as cliente_cnpj,
                   d.codigo_serie as dispositivo_codigo
            FROM reservatorios r
            LEFT JOIN clientes c ON r.cliente_id = c.id
            LEFT JOIN dispositivos d ON r.id = d.reservatorio_id
        ";

        $params = [];
        if (!empty($busca)) {
            $query .= " WHERE (r.nome LIKE :busca OR c.nome_fantasia LIKE :busca OR c.cnpj LIKE :busca)";
            $params['busca'] = "%{$busca}%";
        }

        $query .= " ORDER BY r.created_at DESC LIMIT {$limite} OFFSET {$offset}";

        $stmt = $this->read($query, $params);
        if ($stmt) {
            $resultado = $stmt->fetchAll(\PDO::FETCH_OBJ);
            foreach ($resultado as $item) {
                $item->status_texto = $this->getStatusTexto($item->status);
                $item->capacidade_formatada = $this->formatarCapacidade($item->capacidade_total);
                $item->possui_dispositivo = !empty($item->dispositivo_codigo);
            }
            return $resultado;
        }

        return [];
    }

    /**
     * Contar reservatórios
     */
    public function contar(int $clienteId = null, string $busca = ''): int
    {
        $query = $this->find();

        if ($clienteId) {
            $query = $query->find("cliente_id = :cliente_id", "cliente_id={$clienteId}");
        }

        if (!empty($busca)) {
            $searchQuery = "nome LIKE :busca OR descricao LIKE :busca";
            if ($clienteId) {
                $query = $query->find($searchQuery, "busca=%{$busca}%");
            } else {
                $query = $query->find($searchQuery, "busca=%{$busca}%");
            }
        }

        return $query->count();
    }

    /**
     * Alterar status do reservatório
     */
    public function alterarStatus(int $reservatorioId, string $status): bool
    {
        $statusValidos = ['ativo', 'inativo', 'manutencao'];
        if (!in_array($status, $statusValidos)) {
            $this->fail = new \Exception("Status inválido");
            return false;
        }

        $reservatorio = $this->findById($reservatorioId);
        if (!$reservatorio) {
            return false;
        }

        $reservatorio->status = $status;
        return $reservatorio->save();
    }

    /**
     * Vincular dispositivo ao reservatório
     */
    public function vincularDispositivo(int $reservatorioId, int $dispositivoId): bool
    {
        // Verificar se reservatório existe
        $reservatorio = $this->findById($reservatorioId);
        if (!$reservatorio) {
            $this->fail = new \Exception("Reservatório não encontrado");
            return false;
        }

        // Usar o modelo Dispositivo para fazer a vinculação
        $dispositivo = new Dispositivo();
        return $dispositivo->vincularReservatorio($dispositivoId, $reservatorioId);
    }

    /**
     * Desvincular dispositivo do reservatório
     */
    public function desvincularDispositivo(int $reservatorioId): bool
    {
        $query = "UPDATE dispositivos SET reservatorio_id = NULL WHERE reservatorio_id = :reservatorio_id";
        $stmt = $this->read($query, ['reservatorio_id' => $reservatorioId]);
        return $stmt !== false;
    }

    /**
     * Calcular volume atual baseado no nível
     */
    public function calcularVolume(float $nivelAtual): float
    {
        if (empty($this->capacidade_total) || empty($this->altura_total)) {
            return 0;
        }

        // Cálculo simples baseado na proporção do nível
        $percentual = ($nivelAtual / $this->altura_total) * 100;
        return ($percentual / 100) * $this->capacidade_total;
    }

    /**
     * Calcular percentual do nível
     */
    public function calcularPercentual(float $nivelAtual): float
    {
        if (empty($this->altura_total)) {
            return 0;
        }

        return ($nivelAtual / $this->altura_total) * 100;
    }

    /**
     * Obter última medição do reservatório
     */
    public function getUltimaMedicao(int $reservatorioId)
    {
        $query = "
            SELECT m.*, d.codigo_serie as dispositivo_codigo
            FROM medicoes m
            LEFT JOIN dispositivos d ON m.dispositivo_id = d.id
            WHERE m.reservatorio_id = :reservatorio_id
            ORDER BY m.data_medicao DESC
            LIMIT 1
        ";

        $stmt = $this->read($query, ['reservatorio_id' => $reservatorioId]);
        if ($stmt && $stmt->rowCount() > 0) {
            $medicao = $stmt->fetch(\PDO::FETCH_OBJ);
            $medicao->data_medicao_formatada = date('d/m/Y H:i', strtotime($medicao->data_medicao));
            return $medicao;
        }

        return null;
    }

    /**
     * Verificar se reservatório possui dispositivo vinculado
     */
    public function possuiDispositivo(int $reservatorioId): bool
    {
        $query = "SELECT COUNT(*) as total FROM dispositivos WHERE reservatorio_id = :reservatorio_id";
        $stmt = $this->read($query, ['reservatorio_id' => $reservatorioId]);
        
        if ($stmt && $stmt->rowCount() > 0) {
            $resultado = $stmt->fetch(\PDO::FETCH_OBJ);
            return $resultado->total > 0;
        }

        return false;
    }

    /**
     * Obter dispositivo vinculado
     */
    public function getDispositivoVinculado(int $reservatorioId)
    {
        $dispositivo = new Dispositivo();
        return $dispositivo->find("reservatorio_id = :reservatorio_id", "reservatorio_id={$reservatorioId}")->fetch();
    }

    /**
     * Listar reservatórios sem dispositivo
     */
    public function listarSemDispositivo(): array
    {
        $query = "
            SELECT r.*, c.nome_fantasia as cliente_nome
            FROM reservatorios r
            LEFT JOIN clientes c ON r.cliente_id = c.id
            LEFT JOIN dispositivos d ON r.id = d.reservatorio_id
            WHERE d.id IS NULL AND r.status = 'ativo'
            ORDER BY r.nome ASC
        ";

        $stmt = $this->read($query);
        return $stmt ? $stmt->fetchAll(\PDO::FETCH_OBJ) : [];
    }

    /**
     * Obter estatísticas do reservatório
     */
    public function getEstatisticas(int $reservatorioId, int $dias = 30): array
    {
        $dataInicio = date('Y-m-d', strtotime("-{$dias} days"));
        
        $query = "
            SELECT 
                COUNT(*) as total_medicoes,
                AVG(nivel_atual) as nivel_medio,
                MIN(nivel_atual) as nivel_minimo,
                MAX(nivel_atual) as nivel_maximo,
                AVG(volume_atual) as volume_medio
            FROM medicoes
            WHERE reservatorio_id = :reservatorio_id 
            AND data_medicao >= :data_inicio
        ";

        $stmt = $this->read($query, [
            'reservatorio_id' => $reservatorioId,
            'data_inicio' => $dataInicio
        ]);

        if ($stmt && $stmt->rowCount() > 0) {
            $stats = $stmt->fetch(\PDO::FETCH_OBJ);
            $stats->nivel_medio = round($stats->nivel_medio ?? 0, 2);
            $stats->nivel_minimo = round($stats->nivel_minimo ?? 0, 2);
            $stats->nivel_maximo = round($stats->nivel_maximo ?? 0, 2);
            $stats->volume_medio = round($stats->volume_medio ?? 0, 2);
            return (array) $stats;
        }

        return [
            'total_medicoes' => 0,
            'nivel_medio' => 0,
            'nivel_minimo' => 0,
            'nivel_maximo' => 0,
            'volume_medio' => 0
        ];
    }

    /**
     * Obter texto do status
     */
    private function getStatusTexto(string $status): string
    {
        $statusMap = [
            'ativo' => 'Ativo',
            'inativo' => 'Inativo',
            'manutencao' => 'Em Manutenção'
        ];

        return $statusMap[$status] ?? 'Desconhecido';
    }

    /**
     * Formatar capacidade
     */
    private function formatarCapacidade(float $capacidade): string
    {
        if ($capacidade >= 1000) {
            return number_format($capacidade / 1000, 1) . ' mil litros';
        }
        return number_format($capacidade, 0) . ' litros';
    }
}
