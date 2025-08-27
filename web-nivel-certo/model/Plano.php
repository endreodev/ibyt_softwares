<?php

namespace Model;

use CoffeeCode\DataLayer\DataLayer;

class Plano extends DataLayer
{
    /**
     * Plano constructor.
     */
    public function __construct()
    {
        parent::__construct("planos", ["nome", "valor_mensal"], "id", true);
    }

    /**
     * Valida dados do plano
     */
    public function validar(): bool
    {
        if (empty($this->nome) || strlen($this->nome) < 3) {
            $this->fail = new \Exception("Nome do plano deve ter pelo menos 3 caracteres");
            return false;
        }

        if (empty($this->valor_mensal) || $this->valor_mensal <= 0) {
            $this->fail = new \Exception("Valor mensal deve ser maior que zero");
            return false;
        }

        if (empty($this->max_dispositivos) || $this->max_dispositivos <= 0) {
            $this->fail = new \Exception("Máximo de dispositivos deve ser maior que zero");
            return false;
        }

        if (empty($this->max_reservatorios) || $this->max_reservatorios <= 0) {
            $this->fail = new \Exception("Máximo de reservatórios deve ser maior que zero");
            return false;
        }

        // Verificar se nome já existe
        if (empty($this->id)) {
            $existingPlano = $this->find("nome = :nome", "nome={$this->nome}")->fetch();
            if ($existingPlano) {
                $this->fail = new \Exception("Nome do plano já existe");
                return false;
            }
        } else {
            $existingPlano = $this->find("nome = :nome AND id != :id", 
                "nome={$this->nome}&id={$this->id}")->fetch();
            if ($existingPlano) {
                $this->fail = new \Exception("Nome do plano já existe");
                return false;
            }
        }

        return true;
    }

    /**
     * Salva plano com validação
     */
    public function save(): bool
    {
        if (!$this->validar()) {
            return false;
        }

        // Garantir valores booleanos
        $this->incluir_alertas = $this->incluir_alertas ? 1 : 0;
        $this->incluir_relatorios = $this->incluir_relatorios ? 1 : 0;
        $this->incluir_api = $this->incluir_api ? 1 : 0;
        $this->ativo = $this->ativo ? 1 : 0;

        return parent::save();
    }

    /**
     * Lista todos os planos ativos
     */
    public function listarAtivos(): array
    {
        $planos = $this->find("ativo = 1")
            ->order("valor_mensal ASC")
            ->fetch(true);

        if (!$planos) {
            return [];
        }

        $result = [];
        foreach ($planos as $plano) {
            $data = $plano->data();
            $data->valor_formatado = $this->formatarValor($data->valor_mensal);
            $data->recursos = $this->getRecursos($plano);
            $result[] = $data;
        }

        return $result;
    }

    /**
     * Lista todos os planos (ativos e inativos)
     */
    public function listarTodos(int $limite = 50, int $offset = 0): array
    {
        $planos = $this->find()
            ->order("created_at DESC")
            ->limit($limite)
            ->offset($offset)
            ->fetch(true);

        if (!$planos) {
            return [];
        }

        $result = [];
        foreach ($planos as $plano) {
            $data = $plano->data();
            $data->valor_formatado = $this->formatarValor($data->valor_mensal);
            $data->recursos = $this->getRecursos($plano);
            $data->total_assinantes = $this->contarAssinantes($plano->id);
            $result[] = $data;
        }

        return $result;
    }

    /**
     * Buscar plano por ID
     */
    public function buscarPorId(int $id)
    {
        $plano = $this->findById($id);
        if ($plano) {
            $data = $plano->data();
            $data->valor_formatado = $this->formatarValor($data->valor_mensal);
            $data->recursos = $this->getRecursos($plano);
            return $data;
        }
        return null;
    }

    /**
     * Ativar/Desativar plano
     */
    public function alterarStatus(int $planoId, bool $ativo): bool
    {
        $plano = $this->findById($planoId);
        if (!$plano) {
            $this->fail = new \Exception("Plano não encontrado");
            return false;
        }

        $plano->ativo = $ativo ? 1 : 0;
        return $plano->save();
    }

    /**
     * Contar total de planos
     */
    public function contar(): int
    {
        return $this->find()->count();
    }

    /**
     * Verificar se cliente pode ter mais dispositivos
     */
    public function podeAdicionarDispositivo(int $clienteId, int $planoId): bool
    {
        $plano = $this->findById($planoId);
        if (!$plano) {
            return false;
        }

        // Contar dispositivos atuais do cliente
        $dispositivo = new Dispositivo();
        $totalDispositivos = $dispositivo->contar($clienteId);

        return $totalDispositivos < $plano->max_dispositivos;
    }

    /**
     * Verificar se cliente pode ter mais reservatórios
     */
    public function podeAdicionarReservatorio(int $clienteId, int $planoId): bool
    {
        $plano = $this->findById($planoId);
        if (!$plano) {
            return false;
        }

        // Contar reservatórios atuais do cliente
        $reservatorio = new Reservatorio();
        $totalReservatorios = $reservatorio->contar($clienteId);

        return $totalReservatorios < $plano->max_reservatorios;
    }

    /**
     * Formatar valor monetário
     */
    private function formatarValor(float $valor): string
    {
        return 'R$ ' . number_format($valor, 2, ',', '.');
    }

    /**
     * Obter lista de recursos do plano
     */
    private function getRecursos($plano): array
    {
        $recursos = [];
        
        $recursos[] = $plano->max_dispositivos . ' dispositivo(s)';
        $recursos[] = $plano->max_reservatorios . ' reservatório(s)';
        
        if ($plano->incluir_alertas) {
            $recursos[] = 'Alertas por email';
        }
        
        if ($plano->incluir_relatorios) {
            $recursos[] = 'Relatórios detalhados';
        }
        
        if ($plano->incluir_api) {
            $recursos[] = 'Acesso à API';
        }

        return $recursos;
    }

    /**
     * Contar assinantes do plano
     */
    private function contarAssinantes(int $planoId): int
    {
        $assinatura = new Assinatura();
        return $assinatura->find("plano_id = :plano_id AND status = 'ativa'", "plano_id={$planoId}")->count();
    }

    /**
     * Obter plano mais popular
     */
    public function getMaisPopular()
    {
        $query = "
            SELECT p.*, COUNT(a.id) as total_assinantes
            FROM planos p
            LEFT JOIN assinaturas a ON p.id = a.plano_id AND a.status = 'ativa'
            WHERE p.ativo = 1
            GROUP BY p.id
            ORDER BY total_assinantes DESC, p.valor_mensal ASC
            LIMIT 1
        ";

        $stmt = $this->read($query);
        if ($stmt && $stmt->rowCount() > 0) {
            $plano = $stmt->fetch(\PDO::FETCH_OBJ);
            $plano->valor_formatado = $this->formatarValor($plano->valor_mensal);
            return $plano;
        }

        return null;
    }

    /**
     * Obter estatísticas de planos
     */
    public function getEstatisticas(): array
    {
        $query = "
            SELECT 
                p.id,
                p.nome,
                p.valor_mensal,
                COUNT(a.id) as total_assinantes,
                SUM(CASE WHEN a.status = 'ativa' THEN 1 ELSE 0 END) as assinantes_ativos,
                SUM(CASE WHEN a.status = 'ativa' THEN p.valor_mensal ELSE 0 END) as receita_mensal
            FROM planos p
            LEFT JOIN assinaturas a ON p.id = a.plano_id
            WHERE p.ativo = 1
            GROUP BY p.id, p.nome, p.valor_mensal
            ORDER BY p.valor_mensal ASC
        ";

        $stmt = $this->read($query);
        if ($stmt) {
            $resultado = $stmt->fetchAll(\PDO::FETCH_OBJ);
            foreach ($resultado as $item) {
                $item->valor_formatado = $this->formatarValor($item->valor_mensal);
                $item->receita_formatada = $this->formatarValor($item->receita_mensal);
            }
            return $resultado;
        }

        return [];
    }
}
