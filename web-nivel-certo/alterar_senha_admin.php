<?php
// Script para alterar senha do administrador
header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔑 Alterar Senha do Administrador</h1>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova_senha = $_POST['nova_senha'] ?? '';
    
    if (empty($nova_senha)) {
        echo "<p style='color: red;'>❌ Por favor, informe a nova senha!</p>";
    } else {
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
            
            // Gerar hash da nova senha
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            
            // Atualizar a senha do admin
            $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE email = 'admin@ibyt.com.br'");
            $stmt->execute([$senha_hash]);
            
            if ($stmt->rowCount() > 0) {
                echo "<div style='color: green; padding: 15px; border: 1px solid green; border-radius: 5px; margin: 10px 0;'>";
                echo "<h3>✅ Senha atualizada com sucesso!</h3>";
                echo "<p><strong>Email:</strong> admin@ibyt.com.br</p>";
                echo "<p><strong>Nova senha:</strong> " . htmlspecialchars($nova_senha) . "</p>";
                echo "<p><strong>Hash gerado:</strong> <small>" . $senha_hash . "</small></p>";
                echo "</div>";
                
                echo "<p><a href='admin.html' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Fazer Login</a></p>";
            } else {
                echo "<p style='color: orange;'>⚠️ Nenhum usuário admin encontrado para atualizar.</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
        }
    }
} else {
    // Mostrar informações atuais
    try {
        // Incluir configurações
        $configFile = 'config/database-simples.php';
        if (!file_exists($configFile)) {
            $configFile = 'config/database.php';
        }
        require_once $configFile;
        
        if (defined('DATA_LAYER_CONFIG')) {
            $config = DATA_LAYER_CONFIG;
            $host = $config['host'];
            $database = $config['dbname'];
            $username = $config['username'];
            $password = $config['passwd'];
            
            // Conectar ao banco
            $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Verificar usuário admin atual
            $stmt = $pdo->prepare("SELECT nome, email, tipo FROM usuarios WHERE email = 'admin@ibyt.com.br'");
            $stmt->execute();
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin) {
                echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
                echo "<h3>📋 Informações do Administrador Atual</h3>";
                echo "<p><strong>Nome:</strong> " . htmlspecialchars($admin['nome']) . "</p>";
                echo "<p><strong>Email:</strong> " . htmlspecialchars($admin['email']) . "</p>";
                echo "<p><strong>Tipo:</strong> " . htmlspecialchars($admin['tipo']) . "</p>";
                echo "</div>";
                
                echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
                echo "<h4>💡 Senha Padrão Atual</h4>";
                echo "<p>Se você acabou de fazer o setup, a senha padrão é: <strong>password</strong></p>";
                echo "<p>Se isso não funcionar, use o formulário abaixo para definir uma nova senha.</p>";
                echo "</div>";
            } else {
                echo "<p style='color: orange;'>⚠️ Usuário admin não encontrado no banco de dados.</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erro ao verificar dados: " . $e->getMessage() . "</p>";
    }
}
?>

<form method="POST" style="max-width: 400px; margin: 20px 0;">
    <h3>🔐 Definir Nova Senha</h3>
    <div style="margin: 10px 0;">
        <label for="nova_senha"><strong>Nova Senha:</strong></label><br>
        <input type="password" id="nova_senha" name="nova_senha" 
               style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px;"
               placeholder="Digite a nova senha" required>
    </div>
    <div style="margin: 10px 0;">
        <input type="checkbox" id="mostrar_senha" onclick="togglePassword()">
        <label for="mostrar_senha">Mostrar senha</label>
    </div>
    <button type="submit" 
            style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
        🔄 Alterar Senha
    </button>
</form>

<script>
function togglePassword() {
    var senhaInput = document.getElementById('nova_senha');
    var checkbox = document.getElementById('mostrar_senha');
    
    if (checkbox.checked) {
        senhaInput.type = 'text';
    } else {
        senhaInput.type = 'password';
    }
}
</script>

<hr>
<p><small>💡 <strong>Dica:</strong> Após alterar a senha, você pode fazer login em <a href="admin.html">admin.html</a></small></p>
