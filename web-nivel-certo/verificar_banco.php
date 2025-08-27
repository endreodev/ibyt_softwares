<?php
// Script para verificar estrutura do banco
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
    $host = $config['host'];
    $database = $config['dbname'];
    $username = $config['username'];
    $password = $config['passwd'];
    
    echo "<h1>Verificação do Banco de Dados</h1>";
    echo "<p><strong>Host:</strong> $host</p>";
    echo "<p><strong>Database:</strong> $database</p>";
    echo "<p><strong>User:</strong> $username</p>";
    
    // Conectar ao banco
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>✅ Conexão estabelecida com sucesso!</h2>";
    
    // Verificar tabelas existentes
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Tabelas no banco:</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // Verificar estrutura das tabelas principais
    $mainTables = ['clientes', 'dispositivos', 'reservatorios', 'medicoes'];
    
    foreach ($mainTables as $table) {
        if (in_array($table, $tables)) {
            echo "<h3>Estrutura da tabela: $table</h3>";
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>{$column['Field']}</td>";
                echo "<td>{$column['Type']}</td>";
                echo "<td>{$column['Null']}</td>";
                echo "<td>{$column['Key']}</td>";
                echo "<td>{$column['Default']}</td>";
                echo "<td>{$column['Extra']}</td>";
                echo "</tr>";
            }
            echo "</table><br>";
            
            // Contar registros
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM $table");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            echo "<p><strong>Total de registros:</strong> $count</p>";
        } else {
            echo "<h3>❌ Tabela '$table' não encontrada!</h3>";
        }
    }
    
    // Testar as queries do dashboard
    echo "<h2>Teste das queries do dashboard:</h2>";
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM clientes WHERE ativo = 1");
        $totalClientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "<p>✅ Total clientes ativos: $totalClientes</p>";
    } catch (Exception $e) {
        echo "<p>❌ Erro ao contar clientes: " . $e->getMessage() . "</p>";
    }
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM dispositivos WHERE status = 'ativo'");
        $totalDispositivos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "<p>✅ Total dispositivos ativos: $totalDispositivos</p>";
    } catch (Exception $e) {
        echo "<p>❌ Erro ao contar dispositivos: " . $e->getMessage() . "</p>";
    }
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM reservatorios");
        $totalReservatorios = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "<p>✅ Total reservatórios: $totalReservatorios</p>";
    } catch (Exception $e) {
        echo "<p>❌ Erro ao contar reservatórios: " . $e->getMessage() . "</p>";
    }
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM medicoes WHERE DATE(timestamp_medicao) = CURDATE()");
        $medicoesHoje = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "<p>✅ Medições hoje: $medicoesHoje</p>";
    } catch (Exception $e) {
        echo "<p>❌ Erro ao contar medições: " . $e->getMessage() . "</p>";
    }

} catch (Exception $e) {
    echo "<h2>❌ Erro:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
