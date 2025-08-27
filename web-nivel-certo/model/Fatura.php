<?php

namespace Model;

use CoffeeCode\DataLayer\DataLayer;

class Fatura extends DataLayer
{
    /**
     * Fatura constructor.
     */
    public function __construct()
    {
        parent::__construct("faturas", ["numero_fatura", "cliente_id", "assinatura_id", "valor"], "id", true);
    }

    /**
     * Valida dados da fatura
     */
    public function validar(): bool
    {
        if (empty($this->numero_fatura)) {
            $this->fail = new \Exception("Número da fatura é obrigatório");
            return false;
        }

        if (empty($this->cliente_id)) {
            $this->fail = new \Exception("Cliente é obrigatório");
            return false;
        }

        if (empty($this->assinatura_id)) {
            $this->fail = new \Exception("Assinatura é obrigatória");
            return false;
        }

        if (empty($this->valor) || $this->valor <= 0) {
            $this->fail = new \Exception("Valor deve ser maior que zero");
            return false;
        }

        if (empty($this->data_vencimento)) {
            $this->fail = new \Exception("Data de vencimento é obrigatória");
            return false;
        }

        // Verificar se número da fatura já existe
        if (empty($this->id)) {
            $existingFatura = $this->find("numero_fatura = :numero", "numero={$this->numero_fatura}")->fetch();
            if ($existingFatura) {
                $this->fail = new \Exception("Número da fatura já existe");
                return false;
            }
        }

        return true;
    }

    /**
     * Salva fatura com validação
     */
    public function save(): bool
    {
        if (!$this->validar()) {
            return false;
        }

        return parent::save();
    }

    /**
     * Gerar nova fatura para assinatura
     */
    public function gerarFatura(int $assinaturaId): bool
    {
        $assinatura = new Assinatura();
        $dadosAssinatura = $assinatura->findById($assinaturaId);
        
        if (!$dadosAssinatura || $dadosAssinatura->status != 'ativa') {
            $this->fail = new \Exception("Assinatura não encontrada ou inativa");
            return false;
        }

        // Calcular mês/ano da próxima fatura
        $ultimaFatura = $this->find("assinatura_id = :assinatura_id", "assinatura_id={$assinaturaId}")
            ->order("ano_referencia DESC, mes_referencia DESC")
            ->fetch();

        if ($ultimaFatura) {
            $mesReferencia = $ultimaFatura->mes_referencia + 1;
            $anoReferencia = $ultimaFatura->ano_referencia;
            
            if ($mesReferencia > 12) {
                $mesReferencia = 1;
                $anoReferencia++;
            }
        } else {
            // Primeira fatura
            $mesReferencia = (int)date('n');
            $anoReferencia = (int)date('Y');
        }

        // Verificar se já existe fatura para este mês/ano
        $faturaExistente = $this->find(
            "assinatura_id = :assinatura_id AND mes_referencia = :mes AND ano_referencia = :ano",
            "assinatura_id={$assinaturaId}&mes={$mesReferencia}&ano={$anoReferencia}"
        )->fetch();

        if ($faturaExistente) {
            $this->fail = new \Exception("Fatura já existe para este período");
            return false;
        }

        // Gerar número da fatura
        $numeroFatura = $this->gerarNumeroFatura($anoReferencia, $mesReferencia);

        // Calcular data de vencimento
        $dataVencimento = $this->calcularDataVencimento($dadosAssinatura->dia_vencimento, $mesReferencia, $anoReferencia);

        $this->numero_fatura = $numeroFatura;
        $this->cliente_id = $dadosAssinatura->cliente_id;
        $this->assinatura_id = $assinaturaId;
        $this->mes_referencia = $mesReferencia;
        $this->ano_referencia = $anoReferencia;
        $this->valor = $dadosAssinatura->valor_mensal;
        $this->data_vencimento = $dataVencimento;
        $this->status = 'pendente';

        return $this->save();
    }

    /**
     * Marcar fatura como paga
     */
    public function marcarComoPaga(int $faturaId, string $metodoPagamento = '', string $observacoes = ''): bool
    {
        $fatura = $this->findById($faturaId);
        if (!$fatura) {
            $this->fail = new \Exception("Fatura não encontrada");
            return false;
        }

        $fatura->status = 'paga';
        $fatura->data_pagamento = date('Y-m-d');
        
        if (!empty($metodoPagamento)) {
            $fatura->metodo_pagamento = $metodoPagamento;
        }
        
        if (!empty($observacoes)) {
            $fatura->observacoes = $observacoes;
        }

        return $fatura->save();
    }

    /**
     * Cancelar fatura
     */
    public function cancelar(int $faturaId, string $motivo = ''): bool
    {
        $fatura = $this->findById($faturaId);
        if (!$fatura) {
            $this->fail = new \Exception("Fatura não encontrada");
            return false;
        }

        $fatura->status = 'cancelada';
        
        if (!empty($motivo)) {
            $fatura->observacoes = $motivo;
        }

        return $fatura->save();
    }

    /**
     * Listar faturas do cliente
     */
    public function listarPorCliente(int $clienteId, int $limite = 50, int $offset = 0): array
    {
        $query = "
            SELECT f.*, a.plano_id, p.nome as plano_nome
            FROM faturas f
            LEFT JOIN assinaturas a ON f.assinatura_id = a.id
            LEFT JOIN planos p ON a.plano_id = p.id
            WHERE f.cliente_id = :cliente_id
            ORDER BY f.ano_referencia DESC, f.mes_referencia DESC
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
     * Listar todas as faturas
     */
    public function listarTodas(int $limite = 50, int $offset = 0, string $status = ''): array
    {
        $query = "
            SELECT f.*, c.nome_fantasia as cliente_nome, c.cnpj as cliente_cnpj,
                   a.plano_id, p.nome as plano_nome
            FROM faturas f
            LEFT JOIN clientes c ON f.cliente_id = c.id
            LEFT JOIN assinaturas a ON f.assinatura_id = a.id
            LEFT JOIN planos p ON a.plano_id = p.id
        ";

        $params = [];
        if (!empty($status)) {
            $query .= " WHERE f.status = :status";
            $params['status'] = $status;
        }

        $query .= " ORDER BY f.created_at DESC LIMIT :limite OFFSET :offset";
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
            $resultado = $stmt->fetchAll(\PDO::FETCH_OBJ);
            return $this->enriquecerResultado($resultado);
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Listar faturas vencidas
     */
    public function listarVencidas(): array
    {
        $dataAtual = date('Y-m-d');
        
        $query = "
            SELECT f.*, c.nome_fantasia as cliente_nome, c.email as cliente_email,
                   c.telefone as cliente_telefone
            FROM faturas f
            LEFT JOIN clientes c ON f.cliente_id = c.id
            WHERE f.status = 'pendente' AND f.data_vencimento < :data_atual
            ORDER BY f.data_vencimento ASC
        ";

        $stmt = $this->read($query, ['data_atual' => $dataAtual]);
        if ($stmt) {
            $resultado = $stmt->fetchAll(\PDO::FETCH_OBJ);
            return $this->enriquecerResultado($resultado);
        }

        return [];
    }

    /**
     * Marcar faturas vencidas
     */
    public function marcarVencidas(): int
    {
        $dataAtual = date('Y-m-d');
        
        $query = "
            UPDATE faturas 
            SET status = 'vencida' 
            WHERE status = 'pendente' AND data_vencimento < :data_atual
        ";

        $stmt = $this->read($query, ['data_atual' => $dataAtual]);
        return $stmt ? $stmt->rowCount() : 0;
    }

    /**
     * Obter receita por período
     */
    public function getReceitaPorPeriodo(string $dataInicio, string $dataFim): array
    {
        $query = "
            SELECT 
                DATE_FORMAT(data_pagamento, '%Y-%m') as periodo,
                COUNT(*) as total_faturas,
                SUM(valor) as receita_total
            FROM faturas
            WHERE status = 'paga' 
            AND data_pagamento BETWEEN :data_inicio AND :data_fim
            GROUP BY periodo
            ORDER BY periodo ASC
        ";

        $stmt = $this->read($query, [
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim
        ]);

        if ($stmt) {
            $resultado = $stmt->fetchAll(\PDO::FETCH_OBJ);
            foreach ($resultado as $item) {
                $item->receita_formatada = $this->formatarValor($item->receita_total);
            }
            return $resultado;
        }

        return [];
    }

    /**
     * Obter estatísticas de faturas
     */
    public function getEstatisticas(): array
    {
        $query = "
            SELECT 
                status,
                COUNT(*) as total,
                SUM(valor) as valor_total
            FROM faturas
            GROUP BY status
        ";

        $stmt = $this->read($query);
        $resultado = $stmt ? $stmt->fetchAll(\PDO::FETCH_OBJ) : [];

        $estatisticas = [
            'pendente' => ['total' => 0, 'valor' => 0],
            'paga' => ['total' => 0, 'valor' => 0],
            'vencida' => ['total' => 0, 'valor' => 0],
            'cancelada' => ['total' => 0, 'valor' => 0]
        ];

        foreach ($resultado as $item) {
            $estatisticas[$item->status] = [
                'total' => $item->total,
                'valor' => $item->valor_total,
                'valor_formatado' => $this->formatarValor($item->valor_total)
            ];
        }

        return $estatisticas;
    }

    /**
     * Gerar número da fatura
     */
    private function gerarNumeroFatura(int $ano, int $mes): string
    {
        $sequencial = $this->find("ano_referencia = :ano", "ano={$ano}")->count() + 1;
        return sprintf('%04d%02d%06d', $ano, $mes, $sequencial);
    }

    /**
     * Calcular data de vencimento
     */
    private function calcularDataVencimento(int $diaVencimento, int $mes, int $ano): string
    {
        // Ajustar se o dia não existir no mês (ex: 31 de fevereiro)
        $ultimoDiaMes = cal_days_in_month(CAL_GREGORIAN, $mes, $ano);
        $dia = min($diaVencimento, $ultimoDiaMes);
        
        return sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
    }

    /**
     * Enriquecer resultado com dados formatados
     */
    private function enriquecerResultado(array $resultado): array
    {
        foreach ($resultado as $item) {
            $item->valor_formatado = $this->formatarValor($item->valor);
            $item->status_texto = $this->getStatusTexto($item->status);
            $item->mes_ano_referencia = $this->formatarMesAno($item->mes_referencia, $item->ano_referencia);
            $item->dias_vencimento = $this->calcularDiasVencimento($item->data_vencimento);
            
            if ($item->data_vencimento) {
                $item->data_vencimento_formatada = date('d/m/Y', strtotime($item->data_vencimento));
            }
            
            if ($item->data_pagamento) {
                $item->data_pagamento_formatada = date('d/m/Y', strtotime($item->data_pagamento));
            }
        }

        return $resultado;
    }

    /**
     * Formatar valor monetário
     */
    private function formatarValor(float $valor): string
    {
        return 'R$ ' . number_format($valor, 2, ',', '.');
    }

    /**
     * Obter texto do status
     */
    private function getStatusTexto(string $status): string
    {
        $statusMap = [
            'pendente' => 'Pendente',
            'paga' => 'Paga',
            'vencida' => 'Vencida',
            'cancelada' => 'Cancelada'
        ];

        return $statusMap[$status] ?? 'Desconhecido';
    }

    /**
     * Formatar mês/ano de referência
     */
    private function formatarMesAno(int $mes, int $ano): string
    {
        $meses = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
        ];

        return $meses[$mes] . '/' . $ano;
    }

    /**
     * Calcular dias para vencimento (negativo se vencida)
     */
    private function calcularDiasVencimento(string $dataVencimento): int
    {
        $vencimento = new \DateTime($dataVencimento);
        $hoje = new \DateTime();
        $diff = $vencimento->diff($hoje);
        
        if ($vencimento < $hoje) {
            return -$diff->days; // Negativo se vencida
        } else {
            return $diff->days; // Positivo se não vencida
        }
    }
}
