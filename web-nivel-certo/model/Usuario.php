<?php

namespace Model;

use CoffeeCode\DataLayer\DataLayer;

class Usuario extends DataLayer
{
    /**
     * Usuario constructor.
     */
    public function __construct()
    {
        parent::__construct("usuarios", ["usuario", "senha"], "usuarioid", true);
    }

    /**
     * Autentica usuário
     */
    public function autenticar(string $usuario, string $senha): bool
    {
        $user = $this->find("usuario = :usuario", "usuario={$usuario}")->fetch();
        if ($user && $user->senha === $senha) { // Para compatibilidade com senhas simples
            return true;
        }
        return false;
    }

    /**
     * Valida dados do usuário
     */
    public function validar(): bool
    {
        if (empty($this->usuario) || strlen($this->usuario) < 3) {
            $this->fail = new \Exception("Usuário deve ter pelo menos 3 caracteres");
            return false;
        }

        if (empty($this->senha) || strlen($this->senha) < 4) {
            $this->fail = new \Exception("Senha deve ter pelo menos 4 caracteres");
            return false;
        }

        if (!empty($this->email) && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $this->fail = new \Exception("Email inválido");
            return false;
        }

        // Verificar se usuário já existe (apenas para novos registros)
        if (empty($this->usuarioid)) {
            $existingUser = $this->find("usuario = :usuario", "usuario={$this->usuario}")->fetch();
            if ($existingUser) {
                $this->fail = new \Exception("Usuário já existe");
                return false;
            }
        } else {
            // Para atualizações, verificar se não existe outro usuário com o mesmo nome
            $existingUser = $this->find("usuario = :usuario AND usuarioid != :id", 
                "usuario={$this->usuario}&id={$this->usuarioid}")->fetch();
            if ($existingUser) {
                $this->fail = new \Exception("Usuário já existe");
                return false;
            }
        }

        return true;
    }

    /**
     * Salva usuário com validação
     */
    public function save(): bool
    {
        if (!$this->validar()) {
            return false;
        }

        // Para senhas simples (manter compatibilidade)
        if (!empty($this->senha) && strlen($this->senha) < 60) {
            // Não criptografar se já estiver criptografada
            // $this->senha = password_hash($this->senha, PASSWORD_DEFAULT);
        }

        return parent::save();
    }

    /**
     * Lista todos os usuários com paginação
     */
    public function listar(int $limite = 50, int $offset = 0, string $busca = ''): array
    {
        $query = $this->find();
        
        // Adicionar filtro de busca se fornecido
        if (!empty($busca)) {
            $query = $query->find("usuario LIKE :busca OR nome LIKE :busca OR email LIKE :busca", 
                                  "busca=%{$busca}%");
        }
        
        $usuarios = $query->order("created_at DESC")
            ->limit($limite)
            ->offset($offset)
            ->fetch(true);

        if (!$usuarios) {
            return [];
        }

        // Remover senhas do retorno
        $result = [];
        foreach ($usuarios as $usuario) {
            $data = $usuario->data();
            unset($data->senha);
            $result[] = $data;
        }

        return $result;
    }

    /**
     * Conta total de usuários
     */
    public function contar(string $busca = ''): int
    {
        $query = $this->find();
        
        // Adicionar filtro de busca se fornecido
        if (!empty($busca)) {
            $query = $query->find("usuario LIKE :busca OR nome LIKE :busca OR email LIKE :busca", 
                                  "busca=%{$busca}%");
        }
        
        return $query->count();
    }
}