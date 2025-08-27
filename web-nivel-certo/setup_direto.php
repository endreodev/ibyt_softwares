<?php
// Teste direto de conexão e criação do banco
header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔧 Teste Direto de Setup do Banco</h1>\n";
echo "<pre>\n";

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
    
    echo "1. 📋 Configurações carregadas:\n";
    echo "   Host: $host\n";
    echo "   Database: $database\n";
    echo "   Username: $username\n";
    echo "\n";
    
    // Conectar ao MySQL
    echo "2. 🔌 Conectando ao MySQL...\n";
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "   ✅ Conexão estabelecida!\n\n";
    
    // Verificar se banco existe
    echo "3. 🗃️ Verificando banco existente...\n";
    $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?");
    $stmt->execute([$database]);
    $bancoExiste = $stmt->fetch() !== false;
    echo "   Banco '$database' existe: " . ($bancoExiste ? 'SIM' : 'NÃO') . "\n\n";
    
    // Remover banco se existir
    if ($bancoExiste) {
        echo "4. 🗑️ Removendo banco existente...\n";
        $pdo->exec("DROP DATABASE `$database`");
        echo "   ✅ Banco removido!\n\n";
    }
    
    // Criar banco
    echo "5. 🏗️ Criando novo banco...\n";
    $pdo->exec("CREATE DATABASE `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "   ✅ Banco '$database' criado!\n\n";
    
    // Selecionar banco
    echo "6. 🎯 Selecionando banco...\n";
    $pdo->exec("USE `$database`");
    echo "   ✅ Banco selecionado!\n\n";
    
    // Configurar timezone
    echo "7. 🕐 Configurando timezone...\n";
    $pdo->exec("SET time_zone = '-04:00'");
    echo "   ✅ Timezone configurado para Cuiabá!\n\n";
    
    // Criar algumas tabelas de teste
    echo "8. 📋 Criando tabelas básicas...\n";
    
    // Usuários
    $pdo->exec("CREATE TABLE usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        senha VARCHAR(255) NOT NULL,
        tipo ENUM('admin', 'operador') DEFAULT 'operador',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "   ✅ Tabela 'usuarios' criada!\n";
    
    // Clientes
    $pdo->exec("CREATE TABLE clientes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome_fantasia VARCHAR(255) NOT NULL,
        razao_social VARCHAR(255),
        email VARCHAR(255),
        telefone VARCHAR(20),
        cidade VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "   ✅ Tabela 'clientes' criada!\n";
    
    // Dispositivos
    $pdo->exec("CREATE TABLE dispositivos (
        id INT PRIMARY KEY,
        cliente_id INT DEFAULT NULL,
        identificador VARCHAR(100) DEFAULT NULL,
        tipo VARCHAR(50) DEFAULT 'sensor_nivel',
        status ENUM('ativo', 'inativo') DEFAULT 'ativo',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "   ✅ Tabela 'dispositivos' criada!\n";
    
    // Reservatórios
    $pdo->exec("CREATE TABLE reservatorios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cliente_id INT DEFAULT NULL,
        dispositivo_id INT DEFAULT NULL,
        nome VARCHAR(255) NOT NULL,
        capacidade DECIMAL(10,2) DEFAULT NULL,
        altura_total DECIMAL(8,2) DEFAULT NULL,
        altura_minima DECIMAL(8,2) DEFAULT 10.00,
        tipo ENUM('cilindrico', 'retangular', 'outro') DEFAULT 'cilindrico',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "   ✅ Tabela 'reservatorios' criada!\n";
    
    // Medições
    $pdo->exec("CREATE TABLE medicoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        dispositivo_id INT NOT NULL,
        nivel_agua DECIMAL(8,2) NOT NULL,
        percentual DECIMAL(5,2) DEFAULT NULL,
        timestamp_medicao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "   ✅ Tabela 'medicoes' criada!\n\n";
    
    // Inserir dados de teste
    echo "9. 📝 Inserindo dados de teste...\n";
    
    // Usuário admin
    $pdo->exec("INSERT INTO usuarios (nome, email, senha, tipo) VALUES 
        ('Administrador', 'admin@sistema.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')");
    echo "   ✅ Usuário admin inserido!\n";
    
    // Clientes
    $pdo->exec("INSERT INTO clientes (nome_fantasia, razao_social, email, telefone, cidade) VALUES 
        ('Fazenda Bom Jesus', 'Fazenda Bom Jesus LTDA', 'contato@fazendabomjesus.com', '(65) 99999-1111', 'Cuiabá'),
        ('Condomínio Vista Verde', 'Condomínio Vista Verde', 'admin@vistaverde.com', '(65) 99999-2222', 'Várzea Grande')");
    echo "   ✅ Clientes inseridos!\n";
    
    // Dispositivos
    $pdo->exec("INSERT INTO dispositivos (id, cliente_id, identificador, tipo, status) VALUES 
        (1, 1, 'SENSOR_FAZENDA_001', 'sensor_nivel', 'ativo'),
        (2, 1, 'SENSOR_FAZENDA_002', 'sensor_nivel', 'ativo'),
        (3, 2, 'SENSOR_COND_001', 'sensor_nivel', 'ativo')");
    echo "   ✅ Dispositivos inseridos!\n";
    
    // Reservatórios
    $pdo->exec("INSERT INTO reservatorios (cliente_id, dispositivo_id, nome, capacidade, altura_total, altura_minima, tipo) VALUES 
        (1, 1, 'Reservatório Principal Fazenda', 10000.00, 250.00, 20.00, 'cilindrico'),
        (1, 2, 'Reservatório Secundário Fazenda', 5000.00, 180.00, 15.00, 'cilindrico'),
        (2, 3, 'Reservatório Central Condomínio', 15000.00, 300.00, 30.00, 'retangular')");
    echo "   ✅ Reservatórios inseridos!\n";
    
    // Medições de exemplo
    $pdo->exec("INSERT INTO medicoes (dispositivo_id, nivel_agua, percentual) VALUES 
        (1, 150.00, 60.00),
        (2, 90.00, 50.00),
        (3, 200.00, 66.67)");
    echo "   ✅ Medições inseridas!\n\n";
    
    // Verificar estrutura final
    echo "10. 🔍 Verificando estrutura final...\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tabelas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "    Tabelas criadas: " . count($tabelas) . "\n";
    foreach ($tabelas as $tabela) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `$tabela`");
        $count = $stmt->fetchColumn();
        echo "    📋 $tabela: $count registros\n";
    }
    
    echo "\n";
    
    // Testar timezone
    $stmt = $pdo->query("SELECT NOW() as horario_atual, @@time_zone as fuso");
    $tempo = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "    🕐 Horário: " . $tempo['horario_atual'] . " (Fuso: " . $tempo['fuso'] . ")\n\n";
    
    echo "🎉 SETUP CONCLUÍDO COM SUCESSO!\n";
    echo "\n";
    echo "Próximos passos:\n";
    echo "1. Acesse: http://localhost/web-nivel-certo/gestao_reservatorios.html\n";
    echo "2. Teste IoT: http://localhost/web-nivel-certo/api/medicao.php?D1N120\n";
    echo "3. Login: admin@sistema.com / password\n";
    
} catch (PDOException $e) {
    echo "❌ ERRO DE BANCO: " . $e->getMessage() . "\n";
    echo "Código: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "❌ ERRO GERAL: " . $e->getMessage() . "\n";
}

echo "</pre>\n";
echo "<p><a href='gestao_reservatorios.html'>🚀 Ir para Gestão de Reservatórios</a></p>\n";
echo "<p><a href='teste_medicao.html'>🧪 Testar API IoT</a></p>\n";
?>
