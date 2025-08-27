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
        parent::__construct("reservatorios", ["cliente_id", "nome"], "id", true);
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

        // Verificar dispositivo duplicado (se informado)
        if (!empty($this->dispositivo_id)) {
            if (empty($this->id)) {
                // Novo reservatório - verificar se dispositivo não existe em nenhum reservatório
                $existingReservatorio = $this->find("dispositivo_id = :dispositivo_id", "dispositivo_id={$this->dispositivo_id}")->fetch();
                if ($existingReservatorio) {
                    $this->fail = new \Exception("Este dispositivo já está cadastrado em outro reservatório");
                    return false;
                }
            } else {
                // Atualização - verificar se não existe outro reservatório com o mesmo dispositivo
                $existingReservatorio = $this->find("dispositivo_id = :dispositivo_id AND id != :id", 
                    "dispositivo_id={$this->dispositivo_id}&id={$this->id}")->fetch();
                if ($existingReservatorio) {
                    $this->fail = new \Exception("Este dispositivo já está cadastrado em outro reservatório");
                    return false;
                }
            }
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
     * Lista todos os reservatórios com paginação e join com clientes
     */
    public function listar(int $limite = 50, int $offset = 0, string $busca = ''): array
    {
        $sql = "
            SELECT 
                r.id,
                r.cliente_id,
                r.dispositivo_id,
                r.nome,
                r.capacidade_total,
                r.altura_total,
                r.tipo,
                r.localizacao,
                r.ativo,
                r.created_at,
                r.updated_at,
                c.razao_social as cliente_nome,
                c.nome_fantasia as cliente_fantasia,
                c.cnpj as cliente_cnpj
            FROM reservatorios r
            INNER JOIN clientes c ON r.cliente_id = c.id
        ";
        
        $params = [];
        
        if (!empty($busca)) {
            $sql .= " WHERE (r.nome LIKE :busca OR c.razao_social LIKE :busca OR c.nome_fantasia LIKE :busca)";
            $params['busca'] = "%{$busca}%";
        }
        
        $sql .= " ORDER BY r.created_at DESC LIMIT :limite OFFSET :offset";
        $params['limite'] = $limite;
        $params['offset'] = $offset;
        
        try {
            $stmt = \CoffeeCode\DataLayer\Connect::getInstance()->prepare($sql);
            
            // Bind dos parâmetros
            foreach ($params as $key => $value) {
                if ($key === 'limite' || $key === 'offset') {
                    $stmt->bindValue(":{$key}", (int)$value, \PDO::PARAM_INT);
                } else {
                    $stmt->bindValue(":{$key}", $value, \PDO::PARAM_STR);
                }
            }
            
            $stmt->execute();
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            return $result ?: [];
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Conta total de reservatórios
     */
    public function contar(string $busca = ''): int
    {
        $sql = "
            SELECT COUNT(*) as total
            FROM reservatorios r
            INNER JOIN clientes c ON r.cliente_id = c.id
        ";
        
        $params = [];
        
        if (!empty($busca)) {
            $sql .= " WHERE (r.nome LIKE :busca OR c.razao_social LIKE :busca OR c.nome_fantasia LIKE :busca)";
            $params['busca'] = "%{$busca}%";
        }
        
        try {
            $stmt = \CoffeeCode\DataLayer\Connect::getInstance()->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue(":{$key}", $value, \PDO::PARAM_STR);
            }
            
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return (int)($result['total'] ?? 0);
        } catch (\PDOException $e) {
            return 0;
        }
    }

    /**
     * Busca dispositivos disponíveis (que ainda não estão em reservatórios)
     */
    public function buscarDispositivosDisponiveis(): array
    {
        $sql = "
            SELECT d.id, d.codigo, d.nome
            FROM dispositivos d
            LEFT JOIN reservatorios r ON d.id = r.dispositivo_id
            WHERE r.dispositivo_id IS NULL
            AND d.status = 'ativo'
            ORDER BY d.codigo
        ";
        
        try {
            $stmt = \CoffeeCode\DataLayer\Connect::getInstance()->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            return $result ?: [];
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Busca todos os clientes para o select
     */
    public function buscarClientes(): array
    {
        // Usar query direta em vez de instanciar a classe Cliente
        $sql = "SELECT id, razao_social, nome_fantasia, cnpj FROM clientes WHERE status = 'ativo' ORDER BY razao_social ASC";
        
        try {
            $stmt = \CoffeeCode\DataLayer\Connect::getInstance()->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            return $result ?: [];
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Conta reservatórios por cliente
     */
    public function contarPorCliente(int $clienteId = null): int
    {
        $sql = "SELECT COUNT(*) as total FROM reservatorios";
        $params = [];
        
        if ($clienteId) {
            $sql .= " WHERE cliente_id = :cliente_id";
            $params['cliente_id'] = $clienteId;
        }
        
        try {
            $stmt = \CoffeeCode\DataLayer\Connect::getInstance()->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue(":{$key}", $value, \PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return (int)($result['total'] ?? 0);
        } catch (\PDOException $e) {
            return 0;
        }
    }
}
