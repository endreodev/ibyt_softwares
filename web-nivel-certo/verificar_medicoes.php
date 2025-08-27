<?php
// Script para verificar e popular tabela medicoes
header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔧 Verificar e Corrigir Tabela Medicoes</h1>";

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
    
    echo "<h2>✅ Conectado ao banco de dados</h2>";
    
    // Verificar estrutura da tabela medicoes
    echo "<h3>📋 Estrutura da Tabela Medicoes:</h3>";
    $stmt = $pdo->query("DESCRIBE medicoes");
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th style='padding: 8px;'>Campo</th><th style='padding: 8px;'>Tipo</th><th style='padding: 8px;'>Nulo</th><th style='padding: 8px;'>Chave</th><th style='padding: 8px;'>Padrão</th></tr>";
    
    foreach ($colunas as $coluna) {
        echo "<tr>";
        echo "<td style='padding: 8px;'><strong>{$coluna['Field']}</strong></td>";
        echo "<td style='padding: 8px;'>{$coluna['Type']}</td>";
        echo "<td style='padding: 8px;'>{$coluna['Null']}</td>";
        echo "<td style='padding: 8px;'>{$coluna['Key']}</td>";
        echo "<td style='padding: 8px;'>{$coluna['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar quantas medições existem
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM medicoes");
    $totalMedicoes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "<h3>📊 Dados Atuais:</h3>";
    echo "<p><strong>Total de medições:</strong> {$totalMedicoes}</p>";
    
    // Se não há medições, criar algumas de exemplo
    if ($totalMedicoes == 0) {
        echo "<h3>🔧 Inserindo dados de exemplo...</h3>";
        
        // Verificar se existem dispositivos
        $stmt = $pdo->query("SELECT id FROM dispositivos LIMIT 5");
        $dispositivos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($dispositivos) > 0) {
            foreach ($dispositivos as $dispositivo) {
                // Inserir várias medições para cada dispositivo
                for ($i = 0; $i < 10; $i++) {
                    $nivel = rand(15, 95); // Níveis aleatórios entre 15% e 95%
                    $data = date('Y-m-d H:i:s', strtotime("-{$i} hours"));
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO medicoes (dispositivo_id, nivel_agua, timestamp_medicao) 
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$dispositivo['id'], $nivel, $data]);
                }
                echo "<p>✅ Inseridas 10 medições para dispositivo ID {$dispositivo['id']}</p>";
            }
            
            echo "<p style='color: green;'><strong>✅ Dados de exemplo inseridos com sucesso!</strong></p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Nenhum dispositivo encontrado. Execute o setup completo primeiro.</p>";
        }
    }
    
    // Mostrar algumas medições recentes
    echo "<h3>📈 Últimas Medições:</h3>";
    $stmt = $pdo->query("
        SELECT m.id, m.dispositivo_id, m.nivel_agua, m.timestamp_medicao, d.identificador, c.nome_fantasia
        FROM medicoes m
        LEFT JOIN dispositivos d ON m.dispositivo_id = d.id
        LEFT JOIN clientes c ON d.cliente_id = c.id
        ORDER BY m.timestamp_medicao DESC
        LIMIT 10
    ");
    $medicoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($medicoes) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th style='padding: 8px;'>ID</th><th style='padding: 8px;'>Dispositivo</th><th style='padding: 8px;'>Cliente</th><th style='padding: 8px;'>Nível</th><th style='padding: 8px;'>Data/Hora</th></tr>";
        
        foreach ($medicoes as $medicao) {
            $cor = $medicao['nivel_agua'] < 20 ? 'red' : ($medicao['nivel_agua'] > 80 ? 'green' : 'orange');
            echo "<tr>";
            echo "<td style='padding: 8px;'>{$medicao['id']}</td>";
            echo "<td style='padding: 8px;'>" . ($medicao['identificador'] ?: 'Dispositivo ' . $medicao['dispositivo_id']) . "</td>";
            echo "<td style='padding: 8px;'>" . ($medicao['nome_fantasia'] ?: 'N/A') . "</td>";
            echo "<td style='padding: 8px; color: $cor; font-weight: bold;'>{$medicao['nivel_agua']}%</td>";
            echo "<td style='padding: 8px;'>" . date('d/m/Y H:i:s', strtotime($medicao['timestamp_medicao'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>Nenhuma medição encontrada.</p>";
    }
    
    echo "<hr>";
    echo "<h3>🚀 Ações:</h3>";
    echo "<p>";
    echo "<a href='api/dashboard_simples.php?action=admin' style='background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>🔍 Testar API</a>";
    echo "<a href='admin_simples.html' style='background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>📊 Abrir Dashboard</a>";
    echo "<a href='setup_banco.html' style='background: #6c757d; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>🗃️ Setup Completo</a>";
    echo "</p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Erro: " . $e->getMessage() . "</h2>";
}
?>
