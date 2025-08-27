<?php

namespace Model;

use CoffeeCode\DataLayer\DataLayer;

class Medicao extends DataLayer
{
    /**
     * Medicao constructor.
     */
    public function __construct()
    {
        // Configurar fuso horário para Cuiabá
        date_default_timezone_set('America/Cuiaba');
        
        // Campos obrigatórios: dispositivo_id e nivel_agua (sem timestamps automáticos)
        parent::__construct("medicoes", ["dispositivo_id", "nivel_agua"], "id", false);
    }

    /**
     * Gravar medição de dispositivo (cria dispositivo se não existir)
     */
    public function gravarMedicao(int $dispositivoId, float $nivelAgua): bool
    {
        // Configurar fuso horário para Cuiabá
        date_default_timezone_set('America/Cuiaba');
        
        try {
            // Primeiro tentar criar dispositivo se não existir
            $this->garantirDispositivo($dispositivoId);
            
            // Usar SQL direto para gravar medição
            $stmt = \CoffeeCode\DataLayer\Connect::getInstance()->prepare(
                "INSERT INTO medicoes (dispositivo_id, nivel_agua) VALUES (:dispositivo_id, :nivel_agua)"
            );
            $stmt->bindValue(':dispositivo_id', $dispositivoId, \PDO::PARAM_INT);
            $stmt->bindValue(':nivel_agua', $nivelAgua);
            
            $resultado = $stmt->execute();
            
            if (!$resultado) {
                error_log("Erro no SQL: " . implode(", ", $stmt->errorInfo()));
            }
            
            return $resultado;
            
        } catch (\Exception $e) {
            // Log detalhado do erro
            error_log("ERRO DETALHADO ao gravar medição: " . $e->getMessage());
            error_log("Dispositivo ID: $dispositivoId, Nível: $nivelAgua");
            return false;
        }
    }

    /**
     * Garantir que o dispositivo existe (criar se necessário)
     */
    private function garantirDispositivo(int $dispositivoId): void
    {
        try {
            // Tentar inserir dispositivo (ignore se já existir)
            $stmt = \CoffeeCode\DataLayer\Connect::getInstance()->prepare(
                "INSERT IGNORE INTO dispositivos (id) VALUES (:id)"
            );
            $stmt->bindValue(':id', $dispositivoId, \PDO::PARAM_INT);
            $stmt->execute();
            
        } catch (\PDOException $e) {
            // Log do erro mas não falha a medição
            error_log("Aviso ao criar dispositivo ID $dispositivoId: " . $e->getMessage());
        }
    }

    /**
     * Buscar reservatório vinculado ao dispositivo
     */
    private function buscarReservatorioDoDispositivo(int $dispositivoId): ?object
    {
        try {
            $stmt = \CoffeeCode\DataLayer\Connect::getInstance()->prepare(
                "SELECT * FROM reservatorios WHERE dispositivo_id = :dispositivo_id AND ativo = 1 LIMIT 1"
            );
            $stmt->bindValue(':dispositivo_id', $dispositivoId, \PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_OBJ);
            
            // fetch() retorna false quando não encontra nada, precisamos converter para null
            return $result === false ? null : $result;
        } catch (\PDOException $e) {
            return null;
        }
    }

    /**
     * Calcular percentual do nível de água
     */
    private function calcularPercentual(float $nivelAgua, int $reservatorioId): float
    {
        try {
            $stmt = \CoffeeCode\DataLayer\Connect::getInstance()->prepare(
                "SELECT altura_total FROM reservatorios WHERE id = :id"
            );
            $stmt->bindValue(':id', $reservatorioId, \PDO::PARAM_INT);
            $stmt->execute();
            $reservatorio = $stmt->fetch(\PDO::FETCH_OBJ);
            
            if ($reservatorio && $reservatorio->altura_total > 0) {
                return ($nivelAgua / $reservatorio->altura_total) * 100;
            }
        } catch (\PDOException $e) {
            // Ignorar erro no cálculo
        }

        return 0;
    }
}