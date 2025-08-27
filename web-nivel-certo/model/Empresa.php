<?php

namespace Model;

use CoffeeCode\DataLayer\DataLayer;

class Empresa extends DataLayer
{
    /**
     * Empresa constructor.
     */
    public function __construct()
    {
        parent::__construct("empresa", ["razao_social", "cnpj"], "id", true);
    }

    /**
     * Valida dados da empresa
     */
    public function validar(): bool
    {
        if (empty($this->razao_social) || strlen($this->razao_social) < 3) {
            $this->fail = new \Exception("Razão social deve ter pelo menos 3 caracteres");
            return false;
        }

        if (empty($this->nome_fantasia) || strlen($this->nome_fantasia) < 3) {
            $this->fail = new \Exception("Nome fantasia deve ter pelo menos 3 caracteres");
            return false;
        }

        if (empty($this->cnpj) || strlen($this->cnpj) < 14) {
            $this->fail = new \Exception("CNPJ deve ter 14 dígitos");
            return false;
        }

        if (!$this->validarCnpj($this->cnpj)) {
            $this->fail = new \Exception("CNPJ inválido");
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

        return true;
    }

    /**
     * Valida CNPJ
     */
    private function validarCnpj(string $cnpj): bool
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);
        
        if (strlen($cnpj) != 14) {
            return false;
        }

        // Verifica se todos os dígitos são iguais
        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }

        // Calcula o primeiro dígito verificador
        $soma = 0;
        $pesos = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        for ($i = 0; $i < 12; $i++) {
            $soma += $cnpj[$i] * $pesos[$i];
        }
        $resto = $soma % 11;
        $dv1 = $resto < 2 ? 0 : 11 - $resto;

        // Calcula o segundo dígito verificador
        $soma = 0;
        $pesos = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        for ($i = 0; $i < 13; $i++) {
            $soma += $cnpj[$i] * $pesos[$i];
        }
        $resto = $soma % 11;
        $dv2 = $resto < 2 ? 0 : 11 - $resto;

        return $cnpj[12] == $dv1 && $cnpj[13] == $dv2;
    }

    /**
     * Salva empresa com validação
     */
    public function save(): bool
    {
        if (!$this->validar()) {
            return false;
        }

        // Limpar CNPJ antes de salvar
        $this->cnpj = $this->limparCnpj($this->cnpj);
        $this->cep = $this->limparCep($this->cep);

        return parent::save();
    }

    /**
     * Formatar CNPJ para exibição
     */
    public function formatarCnpj(): string
    {
        $cnpj = preg_replace('/\D/', '', $this->cnpj);
        if (strlen($cnpj) == 14) {
            return substr($cnpj, 0, 2) . '.' . substr($cnpj, 2, 3) . '.' . substr($cnpj, 5, 3) . '/' . substr($cnpj, 8, 4) . '-' . substr($cnpj, 12, 2);
        }
        return $this->cnpj;
    }

    /**
     * Limpar formatação do CNPJ
     */
    public function limparCnpj(string $cnpj): string
    {
        return preg_replace('/\D/', '', $cnpj);
    }

    /**
     * Formatar CEP para exibição
     */
    public function formatarCep(): string
    {
        $cep = preg_replace('/\D/', '', $this->cep);
        if (strlen($cep) == 8) {
            return substr($cep, 0, 5) . '-' . substr($cep, 5, 3);
        }
        return $this->cep;
    }

    /**
     * Limpar formatação do CEP
     */
    public function limparCep(string $cep): string
    {
        return preg_replace('/\D/', '', $cep);
    }

    /**
     * Buscar empresa ativa
     */
    public function buscarEmpresa()
    {
        return $this->find()->fetch();
    }

    /**
     * Atualizar logo da empresa
     */
    public function atualizarLogo(string $logoPath): bool
    {
        $this->logo_path = $logoPath;
        return parent::save();
    }
}
