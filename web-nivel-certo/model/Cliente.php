<?php

namespace Model;

use CoffeeCode\DataLayer\DataLayer;

class Cliente extends DataLayer
{
    /**
     * Cliente constructor.
     */
    public function __construct()
    {
        parent::__construct("clientes", ["cnpj", "razao_social"], "id", true);
    }

    /**
     * Valida dados do cliente
     */
    public function validar(): bool
    {
        if (empty($this->cnpj) || strlen($this->cnpj) < 14) {
            $this->fail = new \Exception("CNPJ deve ter 14 dígitos");
            return false;
        }

        if (empty($this->razao_social) || strlen($this->razao_social) < 3) {
            $this->fail = new \Exception("Razão social deve ter pelo menos 3 caracteres");
            return false;
        }

        if (empty($this->nome_fantasia) || strlen($this->nome_fantasia) < 3) {
            $this->fail = new \Exception("Nome fantasia deve ter pelo menos 3 caracteres");
            return false;
        }

        if (empty($this->endereco)) {
            $this->fail = new \Exception("Endereço é obrigatório");
            return false;
        }

        if (empty($this->cidade)) {
            $this->fail = new \Exception("Cidade é obrigatória");
            return false;
        }

        if (empty($this->estado) || strlen($this->estado) != 2) {
            $this->fail = new \Exception("Estado deve ter 2 caracteres");
            return false;
        }

        if (empty($this->cep)) {
            $this->fail = new \Exception("CEP é obrigatório");
            return false;
        }

        if (!empty($this->email) && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $this->fail = new \Exception("Email inválido");
            return false;
        }

        // Verificar se CNPJ já existe (apenas para novos registros)
        if (empty($this->id)) {
            $existingCliente = $this->find("cnpj = :cnpj", "cnpj={$this->cnpj}")->fetch();
            if ($existingCliente) {
                $this->fail = new \Exception("CNPJ já cadastrado");
                return false;
            }
        } else {
            // Para atualizações, verificar se não existe outro cliente com o mesmo CNPJ
            $existingCliente = $this->find("cnpj = :cnpj AND id != :id", 
                "cnpj={$this->cnpj}&id={$this->id}")->fetch();
            if ($existingCliente) {
                $this->fail = new \Exception("CNPJ já cadastrado");
                return false;
            }
        }

        return true;
    }

    /**
     * Salva cliente com validação
     */
    public function save(): bool
    {
        if (!$this->validar()) {
            return false;
        }

        // Limpar CNPJ antes de salvar
        $this->cnpj = $this->limparCnpj($this->cnpj);

        return parent::save();
    }

    /**
     * Lista todos os clientes com paginação
     */
    public function listar(int $limite = 50, int $offset = 0, string $busca = ''): array
    {
        $query = $this->find();
        
        // Adicionar filtro de busca se fornecido
        if (!empty($busca)) {
            $query = $query->find("razao_social LIKE :busca OR nome_fantasia LIKE :busca OR cnpj LIKE :busca OR email LIKE :busca", 
                                  "busca=%{$busca}%");
        }
        
        $clientes = $query->order("created_at DESC")
            ->limit($limite)
            ->offset($offset)
            ->fetch(true);

        if (!$clientes) {
            return [];
        }

        // Converter objetos para array e enriquecer dados
        $result = [];
        foreach ($clientes as $cliente) {
            $data = $cliente->data();
            $data->cnpj_formatado = $this->formatarCnpj($data->cnpj ?? null);
            $data->status_texto = $this->getStatusTexto($data->status ?? 'ativo');
            $data->total_dispositivos = $this->contarDispositivos($data->id);
            $data->total_reservatorios = $this->contarReservatorios($data->id);
            $result[] = $data;
        }

        return $result;
    }

    /**
     * Conta total de clientes
     */
    public function contar(string $busca = ''): int
    {
        $query = $this->find();
        
        // Adicionar filtro de busca se fornecido
        if (!empty($busca)) {
            $query = $query->find("razao_social LIKE :busca OR nome_fantasia LIKE :busca OR cnpj LIKE :busca OR email LIKE :busca", 
                                  "busca=%{$busca}%");
        }
        
        return $query->count();
    }

    /**
     * Formatar CNPJ para exibição
     */
    public function formatarCnpj(?string $cnpj = null): string
    {
        $cnpjFormatacao = $cnpj ?? $this->cnpj;
        
        if (empty($cnpjFormatacao)) {
            return '';
        }
        
        $cnpjLimpo = preg_replace('/\D/', '', $cnpjFormatacao);
        if (strlen($cnpjLimpo) == 14) {
            return substr($cnpjLimpo, 0, 2) . '.' . substr($cnpjLimpo, 2, 3) . '.' . substr($cnpjLimpo, 5, 3) . '/' . substr($cnpjLimpo, 8, 4) . '-' . substr($cnpjLimpo, 12, 2);
        }
        return $cnpjFormatacao ?? '';
    }

    /**
     * Limpar formatação do CNPJ
     */
    public function limparCnpj(string $cnpj): string
    {
        return preg_replace('/\D/', '', $cnpj);
    }

    /**
     * Alterar status do cliente
     */
    public function alterarStatus(int $clienteId, string $status): bool
    {
        $statusValidos = ['ativo', 'suspenso', 'cancelado'];
        if (!in_array($status, $statusValidos)) {
            $this->fail = new \Exception("Status inválido");
            return false;
        }

        $cliente = $this->findById($clienteId);
        if (!$cliente) {
            return false;
        }

        $cliente->status = $status;
        return $cliente->save();
    }

    /**
     * Buscar cliente por CNPJ
     */
    public function buscarPorCnpj(string $cnpj)
    {
        $cnpjLimpo = $this->limparCnpj($cnpj);
        return $this->find("cnpj = :cnpj", "cnpj={$cnpjLimpo}")->fetch();
    }

    /**
     * Obter assinatura ativa do cliente
     */
    public function getAssinaturaAtiva(int $clienteId)
    {
        $assinatura = new Assinatura();
        return $assinatura->buscarAssinaturaAtiva($clienteId);
    }

    /**
     * Contar dispositivos do cliente
     */
    private function contarDispositivos(int $clienteId): int
    {
        $dispositivo = new Dispositivo();
        return $dispositivo->contar($clienteId);
    }

    /**
     * Contar reservatórios do cliente
     */
    private function contarReservatorios(int $clienteId): int
    {
        $reservatorio = new Reservatorio();
        return $reservatorio->contar($clienteId);
    }

    /**
     * Obter texto do status
     */
    private function getStatusTexto(string $status): string
    {
        $statusMap = [
            'ativo' => 'Ativo',
            'suspenso' => 'Suspenso',
            'cancelado' => 'Cancelado'
        ];

        return $statusMap[$status] ?? 'Desconhecido';
    }
}
