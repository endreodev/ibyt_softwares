<?php

namespace Model;

use CoffeeCode\DataLayer\DataLayer;

class OrdemServico extends DataLayer
{
    /**
     * OrdemServico constructor.
     */
    public function __construct()
    {
        parent::__construct("ordens_servico", ["numero_os", "cliente_id", "tipo", "descricao"], "id", true);
    }

    /**
     * Valida dados da ordem de serviço
     */
    public function validar(): bool
    {
        if (empty($this->numero_os)) {
            $this->fail = new \Exception("Número da OS é obrigatório");
            return false;
        }

        if (empty($this->cliente_id)) {
            $this->fail = new \Exception("Cliente é obrigatório");
            return false;
        }

        if (empty($this->tipo)) {
            $this->fail = new \Exception("Tipo de serviço é obrigatório");
            return false;
        }

        if (empty($this->descricao)) {
            $this->fail = new \Exception("Descrição é obrigatória");
            return false;
        }

        if (empty($this->data_abertura)) {
            $this->fail = new \Exception("Data de abertura é obrigatória");
            return false;
        }

        // Verificar se número da OS já existe
        if (empty($this->id)) {
            $existingOS = $this->find("numero_os = :numero", "numero={$this->numero_os}")->fetch();
            if ($existingOS) {
                $this->fail = new \Exception("Número da OS já existe");
                return false;
            }
        }

        return true;
    }

    /**
     * Salva ordem de serviço com validação
     */
    public function save(): bool
    {
        if (!$this->validar()) {
            return false;
        }

        return parent::save();
    }

    /**
     * Criar nova ordem de serviço
     */
    public function criarOS(array $dados): bool
    {
        $numeroOS = $this->gerarNumeroOS();

        $this->numero_os = $numeroOS;
        $this->cliente_id = $dados['cliente_id'];
        $this->dispositivo_id = $dados['dispositivo_id'] ?? null;
        $this->reservatorio_id = $dados['reservatorio_id'] ?? null;
        $this->tipo = $dados['tipo'];
        $this->prioridade = $dados['prioridade'] ?? 'media';
        $this->status = 'aberta';
        $this->descricao = $dados['descricao'];
        $this->tecnico_responsavel = $dados['tecnico_responsavel'] ?? null;
        $this->data_abertura = date('Y-m-d');
        $this->data_agendamento = $dados['data_agendamento'] ?? null;

        return $this->save();
    }

    /**
     * Listar todas as ordens de serviço
     */
    public function listarTodas(int $limite = 50, int $offset = 0, string $status = '', string $busca = ''): array
    {
        $query = "
            SELECT os.*, c.nome_fantasia as cliente_nome, 
                   u.nome as tecnico_nome,
                   d.codigo_serie as dispositivo_codigo,
                   r.nome as reservatorio_nome
            FROM ordens_servico os
            LEFT JOIN clientes c ON os.cliente_id = c.id
            LEFT JOIN usuarios u ON os.tecnico_responsavel = u.id
            LEFT JOIN dispositivos d ON os.dispositivo_id = d.id
            LEFT JOIN reservatorios r ON os.reservatorio_id = r.id
        ";

        $params = [];
        $conditions = [];

        if (!empty($status)) {
            $conditions[] = "os.status = :status";
            $params['status'] = $status;
        }

        if (!empty($busca)) {
            $conditions[] = "(os.numero_os LIKE :busca OR os.descricao LIKE :busca OR c.nome_fantasia LIKE :busca)";
            $params['busca'] = "%{$busca}%";
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $query .= " ORDER BY os.created_at DESC LIMIT {$limite} OFFSET {$offset}";

        $stmt = $this->read($query, $params);
        if ($stmt) {
            $resultado = $stmt->fetchAll(\PDO::FETCH_OBJ);
            return $this->enriquecerResultado($resultado);
        }

        return [];
    }

    /**
     * Listar ordens de serviço por cliente
     */
    public function listarPorCliente(int $clienteId, int $limite = 50, int $offset = 0): array
    {
        $query = "
            SELECT os.*, u.nome as tecnico_nome,
                   d.codigo_serie as dispositivo_codigo,
                   r.nome as reservatorio_nome
            FROM ordens_servico os
            LEFT JOIN usuarios u ON os.tecnico_responsavel = u.id
            LEFT JOIN dispositivos d ON os.dispositivo_id = d.id
            LEFT JOIN reservatorios r ON os.reservatorio_id = r.id
            WHERE os.cliente_id = :cliente_id
            ORDER BY os.created_at DESC
            LIMIT {$limite} OFFSET {$offset}
        ";

        $stmt = $this->read($query, ['cliente_id' => $clienteId]);
        if ($stmt) {
            $resultado = $stmt->fetchAll(\PDO::FETCH_OBJ);
            return $this->enriquecerResultado($resultado);
        }

        return [];
    }

    /**
     * Listar ordens de serviço por técnico
     */
    public function listarPorTecnico(int $tecnicoId, int $limite = 50, int $offset = 0): array
    {
        $query = "
            SELECT os.*, c.nome_fantasia as cliente_nome,
                   d.codigo_serie as dispositivo_codigo,
                   r.nome as reservatorio_nome
            FROM ordens_servico os
            LEFT JOIN clientes c ON os.cliente_id = c.id
            LEFT JOIN dispositivos d ON os.dispositivo_id = d.id
            LEFT JOIN reservatorios r ON os.reservatorio_id = r.id
            WHERE os.tecnico_responsavel = :tecnico_id
            ORDER BY os.data_agendamento ASC, os.prioridade DESC
            LIMIT {$limite} OFFSET {$offset}
        ";

        $stmt = $this->read($query, ['tecnico_id' => $tecnicoId]);
        if ($stmt) {
            $resultado = $stmt->fetchAll(\PDO::FETCH_OBJ);
            return $this->enriquecerResultado($resultado);
        }

        return [];
    }

    /**
     * Atribuir técnico à OS
     */
    public function atribuirTecnico(int $osId, int $tecnicoId): bool
    {
        $os = $this->findById($osId);
        if (!$os) {
            $this->fail = new \Exception("Ordem de serviço não encontrada");
            return false;
        }

        $os->tecnico_responsavel = $tecnicoId;
        return $os->save();
    }

    /**
     * Alterar status da OS
     */
    public function alterarStatus(int $osId, string $novoStatus, string $observacoes = ''): bool
    {
        $statusValidos = ['aberta', 'em_andamento', 'aguardando_peca', 'concluida', 'cancelada'];
        if (!in_array($novoStatus, $statusValidos)) {
            $this->fail = new \Exception("Status inválido");
            return false;
        }

        $os = $this->findById($osId);
        if (!$os) {
            $this->fail = new \Exception("Ordem de serviço não encontrada");
            return false;
        }

        $os->status = $novoStatus;

        // Marcar data de início se mudou para "em_andamento"
        if ($novoStatus === 'em_andamento' && empty($os->data_inicio)) {
            $os->data_inicio = date('Y-m-d H:i:s');
        }

        // Marcar data de conclusão se mudou para "concluida"
        if ($novoStatus === 'concluida' && empty($os->data_conclusao)) {
            $os->data_conclusao = date('Y-m-d H:i:s');
            
            // Calcular tempo de execução
            if ($os->data_inicio) {
                $inicio = new \DateTime($os->data_inicio);
                $fim = new \DateTime($os->data_conclusao);
                $diff = $fim->diff($inicio);
                $os->tempo_execucao = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
            }
        }

        if (!empty($observacoes)) {
            $os->observacoes_tecnico = $observacoes;
        }

        return $os->save();
    }

    /**
     * Agendar OS
     */
    public function agendar(int $osId, string $dataAgendamento): bool
    {
        $os = $this->findById($osId);
        if (!$os) {
            $this->fail = new \Exception("Ordem de serviço não encontrada");
            return false;
        }

        $os->data_agendamento = $dataAgendamento;
        return $os->save();
    }

    /**
     * Avaliar OS (cliente)
     */
    public function avaliar(int $osId, int $avaliacao, string $comentario = ''): bool
    {
        if ($avaliacao < 1 || $avaliacao > 5) {
            $this->fail = new \Exception("Avaliação deve estar entre 1 e 5");
            return false;
        }

        $os = $this->findById($osId);
        if (!$os) {
            $this->fail = new \Exception("Ordem de serviço não encontrada");
            return false;
        }

        if ($os->status !== 'concluida') {
            $this->fail = new \Exception("Apenas OS concluídas podem ser avaliadas");
            return false;
        }

        $os->avaliacao_cliente = $avaliacao;
        $os->comentario_cliente = $comentario;
        return $os->save();
    }

    /**
     * Obter estatísticas de OS
     */
    public function getEstatisticas(): array
    {
        $query = "
            SELECT 
                status,
                COUNT(*) as total
            FROM ordens_servico
            GROUP BY status
        ";

        $stmt = $this->read($query);
        $resultado = $stmt ? $stmt->fetchAll(\PDO::FETCH_OBJ) : [];

        $estatisticas = [
            'aberta' => 0,
            'em_andamento' => 0,
            'aguardando_peca' => 0,
            'concluida' => 0,
            'cancelada' => 0
        ];

        foreach ($resultado as $item) {
            $estatisticas[$item->status] = $item->total;
        }

        return $estatisticas;
    }

    /**
     * Obter média de avaliações
     */
    public function getMediaAvaliacoes(): float
    {
        $query = "
            SELECT AVG(avaliacao_cliente) as media
            FROM ordens_servico
            WHERE avaliacao_cliente IS NOT NULL
        ";

        $stmt = $this->read($query);
        if ($stmt && $stmt->rowCount() > 0) {
            $resultado = $stmt->fetch(\PDO::FETCH_OBJ);
            return round($resultado->media ?? 0, 2);
        }

        return 0;
    }

    /**
     * Gerar número da OS
     */
    private function gerarNumeroOS(): string
    {
        $ano = date('Y');
        $sequencial = $this->find("YEAR(data_abertura) = :ano", "ano={$ano}")->count() + 1;
        return sprintf('OS%04d%06d', $ano, $sequencial);
    }

    /**
     * Enriquecer resultado com dados formatados
     */
    private function enriquecerResultado(array $resultado): array
    {
        foreach ($resultado as $item) {
            $item->status_texto = $this->getStatusTexto($item->status);
            $item->prioridade_texto = $this->getPrioridadeTexto($item->prioridade);
            $item->tipo_texto = $this->getTipoTexto($item->tipo);
            
            if ($item->data_abertura) {
                $item->data_abertura_formatada = date('d/m/Y', strtotime($item->data_abertura));
            }
            
            if ($item->data_agendamento) {
                $item->data_agendamento_formatada = date('d/m/Y', strtotime($item->data_agendamento));
            }
            
            if ($item->data_conclusao) {
                $item->data_conclusao_formatada = date('d/m/Y H:i', strtotime($item->data_conclusao));
            }
            
            if ($item->tempo_execucao) {
                $item->tempo_execucao_formatado = $this->formatarTempo($item->tempo_execucao);
            }
        }

        return $resultado;
    }

    /**
     * Obter texto do status
     */
    private function getStatusTexto(string $status): string
    {
        $statusMap = [
            'aberta' => 'Aberta',
            'em_andamento' => 'Em Andamento',
            'aguardando_peca' => 'Aguardando Peça',
            'concluida' => 'Concluída',
            'cancelada' => 'Cancelada'
        ];

        return $statusMap[$status] ?? 'Desconhecido';
    }

    /**
     * Obter texto da prioridade
     */
    private function getPrioridadeTexto(string $prioridade): string
    {
        $prioridadeMap = [
            'baixa' => 'Baixa',
            'media' => 'Média',
            'alta' => 'Alta',
            'urgente' => 'Urgente'
        ];

        return $prioridadeMap[$prioridade] ?? 'Média';
    }

    /**
     * Obter texto do tipo
     */
    private function getTipoTexto(string $tipo): string
    {
        $tipoMap = [
            'instalacao' => 'Instalação',
            'manutencao' => 'Manutenção',
            'reparo' => 'Reparo',
            'troca' => 'Troca',
            'remocao' => 'Remoção'
        ];

        return $tipoMap[$tipo] ?? 'Manutenção';
    }

    /**
     * Formatar tempo em minutos para texto legível
     */
    private function formatarTempo(int $minutos): string
    {
        if ($minutos < 60) {
            return $minutos . ' min';
        }

        $horas = floor($minutos / 60);
        $minutosRestantes = $minutos % 60;

        if ($horas < 24) {
            return $horas . 'h' . ($minutosRestantes > 0 ? ' ' . $minutosRestantes . 'min' : '');
        }

        $dias = floor($horas / 24);
        $horasRestantes = $horas % 24;

        return $dias . 'd' . ($horasRestantes > 0 ? ' ' . $horasRestantes . 'h' : '');
    }
}
