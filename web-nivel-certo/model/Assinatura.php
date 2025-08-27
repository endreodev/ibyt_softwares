<?php

namespace Model;

use CoffeeCode\DataLayer\DataLayer;

class Assinatura extends DataLayer
{
    /**
     * Assinatura constructor.
     */
    public function __construct()
    {
        parent::__construct("assinaturas", ["cliente_id", "plano_id", "data_inicio", "valor_mensal"], "id", true);
    }

    /**
     * Listar todas as assinaturas com filtros
     */
    public function listarTodas(int $limite = 50, int $offset = 0, string $status = ""): array
    {
        $query = "
            SELECT a.*, 
                   c.nome_fantasia as cliente_nome, 
                   c.cnpj as cliente_cnpj,
                   c.email as cliente_email
            FROM assinaturas a
            LEFT JOIN clientes c ON a.cliente_id = c.id
        ";

        $params = [];
        if (!empty($status)) {
            $query .= " WHERE a.status = :status";
            $params['status'] = $status;
        }

        $query .= " ORDER BY a.created_at DESC LIMIT :limite OFFSET :offset";
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
            
            return $resultado;
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Buscar por ID
     */
    public function buscarPorId(int $id): ?object
    {
        try {
            return $this->findById($id);
        } catch (\Exception $e) {
            return null;
        }
    }
}
