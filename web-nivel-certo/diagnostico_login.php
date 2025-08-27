<?php
// Diagnóstico completo do sistema de login
header('Content-Type: text/html; charset=utf-8');
session_start();

echo "<h1>🔧 Diagnóstico do Sistema de Login</h1>";

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
    $host = $config['host'];
    $database = $config['dbname'];
    $username = $config['username'];
    $password = $config['passwd'];
    
    // Conectar ao banco
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>✅ 1. Conexão com Banco de Dados - OK</h2>";
    
    // Verificar se tabela usuarios existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'usuarios'");
    if ($stmt->rowCount() > 0) {
        echo "<h2>✅ 2. Tabela 'usuarios' existe - OK</h2>";
        
        // Listar todos os usuários
        $stmt = $pdo->query("SELECT id, nome, email, tipo, ativo FROM usuarios ORDER BY id");
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>👥 Usuários no Sistema:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th style='padding: 8px;'>ID</th><th style='padding: 8px;'>Nome</th><th style='padding: 8px;'>Email</th><th style='padding: 8px;'>Tipo</th><th style='padding: 8px;'>Ativo</th></tr>";
        
        foreach ($usuarios as $user) {
            $ativo = $user['ativo'] ? 'Sim' : 'Não';
            $corAtivo = $user['ativo'] ? 'green' : 'red';
            echo "<tr>";
            echo "<td style='padding: 8px;'>{$user['id']}</td>";
            echo "<td style='padding: 8px;'>{$user['nome']}</td>";
            echo "<td style='padding: 8px;'><strong>{$user['email']}</strong></td>";
            echo "<td style='padding: 8px;'>{$user['tipo']}</td>";
            echo "<td style='padding: 8px; color: $corAtivo;'>$ativo</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Teste de login para cada usuário admin
        echo "<h3>🔐 Teste de Login (senha: 'password'):</h3>";
        $senha_teste = 'password';
        
        $stmt = $pdo->prepare("SELECT id, nome, email, senha FROM usuarios WHERE tipo = 'admin'");
        $stmt->execute();
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th style='padding: 8px;'>Email</th><th style='padding: 8px;'>Senha Funciona?</th><th style='padding: 8px;'>Hash</th></tr>";
        
        foreach ($admins as $admin) {
            $senha_ok = password_verify($senha_teste, $admin['senha']);
            $status = $senha_ok ? "✅ SIM" : "❌ NÃO";
            $cor = $senha_ok ? "green" : "red";
            
            echo "<tr>";
            echo "<td style='padding: 8px;'><strong>{$admin['email']}</strong></td>";
            echo "<td style='padding: 8px; color: $cor; font-weight: bold;'>$status</td>";
            echo "<td style='padding: 8px; font-size: 11px;'>" . substr($admin['senha'], 0, 30) . "...</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<h2>❌ 2. Tabela 'usuarios' NÃO existe</h2>";
        echo "<p style='color: red;'>Execute o setup do banco primeiro!</p>";
    }
    
    // Verificar arquivos de autenticação
    echo "<h2>📁 3. Verificação de Arquivos:</h2>";
    
    $arquivos_verificar = [
        'api/autenticacao.php' => 'API de Autenticação',
        'admin.html' => 'Página de Login',
        'alterar_senha_admin.php' => 'Alteração de Senha'
    ];
    
    foreach ($arquivos_verificar as $arquivo => $descricao) {
        if (file_exists($arquivo)) {
            echo "<p>✅ <strong>$descricao</strong>: $arquivo - OK</p>";
        } else {
            echo "<p>❌ <strong>$descricao</strong>: $arquivo - NÃO EXISTE</p>";
        }
    }
    
    // Verificar sessão
    echo "<h2>🔒 4. Status da Sessão:</h2>";
    echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
    echo "<p><strong>Logado:</strong> " . (isset($_SESSION['logged_in']) ? 'SIM' : 'NÃO') . "</p>";
    if (isset($_SESSION['user'])) {
        echo "<p><strong>Usuário:</strong> " . $_SESSION['user'] . "</p>";
        echo "<p><strong>Nome:</strong> " . ($_SESSION['user_name'] ?? 'N/A') . "</p>";
        echo "<p><strong>Tipo:</strong> " . ($_SESSION['user_tipo'] ?? 'N/A') . "</p>";
    }
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Erro: " . $e->getMessage() . "</h2>";
}
?>

<hr>
<h2>🛠️ Ações Rápidas:</h2>
<p>
    <a href="alterar_senha_admin.php" style="background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin-right: 10px;">🔑 Alterar Senha</a>
    <a href="setup_banco.html" style="background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin-right: 10px;">🗃️ Setup Banco</a>
    <a href="admin.html" style="background: #6c757d; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;">🚀 Fazer Login</a>
</p>

<script>
// Auto-refresh a cada 30 segundos
setTimeout(function() {
    location.reload();
}, 30000);
</script>
