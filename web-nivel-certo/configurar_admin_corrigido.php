<?php
// Configuração automática do administrador
header('Content-Type: text/html; charset=utf-8');

try {
    // Incluir configurações
    $configFile = 'config/database-simples.php';
    if (!file_exists($configFile)) {
        $configFile = 'config/database.php';
    }
    require_once $configFile;
    
    if (!defined('DATA_LAYER_CONFIG')) {
        throw new Exception('Configurações de banco não definidas');
    }
    
    $config = DATA_LAYER_CONFIG;
    $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4", 
                   $config['username'], $config['passwd']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>🔧 Configuração do Administrador</h1>";
    echo "<p>Configurando usuário administrador padrão...</p>";
    
    // Verificar se tabela usuarios existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'usuarios'");
    if (!$stmt->fetch()) {
        echo "<p>📋 Criando tabela de usuários...</p>";
        $sql = "CREATE TABLE usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            senha VARCHAR(255) NOT NULL,
            tipo ENUM('admin', 'cliente', 'operador') DEFAULT 'cliente',
            ativo TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $pdo->exec($sql);
        echo "<p>✅ Tabela de usuários criada</p>";
    } else {
        echo "<p>✅ Tabela de usuários já existe</p>";
    }
    
    // Verificar se admin já existe
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = 'admin' AND tipo = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<p>ℹ️ Administrador já existe, atualizando senha...</p>";
        // Atualizar senha do admin existente
        $senhaHash = password_hash('123456', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET senha = ?, nome = 'Administrador' WHERE email = 'admin'");
        $stmt->execute([$senhaHash]);
        echo "<p>✅ Senha do administrador atualizada</p>";
    } else {
        echo "<p>👤 Criando usuário administrador...</p>";
        // Criar novo admin
        $senhaHash = password_hash('123456', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Administrador', 'admin', $senhaHash, 'admin']);
        echo "<p>✅ Administrador criado com sucesso</p>";
    }
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🎉 Configuração Concluída!</h3>";
    echo "<p><strong>Dados de acesso:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Usuário:</strong> admin</li>";
    echo "<li><strong>Senha:</strong> 123456</li>";
    echo "</ul>";
    echo "<p><a href='login.html' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔐 Fazer Login</a></p>";
    echo "</div>";
    
    // Criar alguns usuários de exemplo
    echo "<h3>Criando usuários de exemplo...</h3>";
    
    $usuariosExemplo = [
        ['João Silva', 'joao@exemplo.com', 'cliente'],
        ['Maria Santos', 'maria@exemplo.com', 'cliente'],
        ['Carlos Operador', 'carlos@exemplo.com', 'operador']
    ];
    
    foreach ($usuariosExemplo as $usuario) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$usuario[1]]);
        
        if (!$stmt->fetch()) {
            $senhaHash = password_hash('123456', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)");
            $stmt->execute([$usuario[0], $usuario[1], $senhaHash, $usuario[2]]);
            echo "<p>✅ Usuário {$usuario[0]} criado</p>";
        } else {
            echo "<p>ℹ️ Usuário {$usuario[0]} já existe</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ Erro na Configuração</h3>";
    echo "<p>{$e->getMessage()}</p>";
    echo "</div>";
}
?>
