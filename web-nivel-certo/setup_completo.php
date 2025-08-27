<?php
// Script para criar tabelas e dados de exemplo
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
    
    echo "<h1>Configuração Completa do Banco de Dados</h1>";
    
    // Conectar ao banco
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Conectado ao banco: $database</p>";
    
    // Criar tabela de clientes
    $sql = "CREATE TABLE IF NOT EXISTS clientes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome_fantasia VARCHAR(255) NOT NULL,
        razao_social VARCHAR(255),
        cnpj VARCHAR(20),
        email VARCHAR(255),
        telefone VARCHAR(20),
        endereco TEXT,
        cidade VARCHAR(100),
        estado VARCHAR(2),
        cep VARCHAR(10),
        ativo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "<p>✅ Tabela 'clientes' criada/verificada</p>";
    
    // Criar tabela de dispositivos
    $sql = "CREATE TABLE IF NOT EXISTS dispositivos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        identificador VARCHAR(50) UNIQUE NOT NULL,
        cliente_id INT,
        status ENUM('ativo', 'inativo', 'manutencao') DEFAULT 'ativo',
        tipo VARCHAR(50) DEFAULT 'sensor_nivel',
        localizacao VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "<p>✅ Tabela 'dispositivos' criada/verificada</p>";
    
    // Criar tabela de reservatórios
    $sql = "CREATE TABLE IF NOT EXISTS reservatorios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        cliente_id INT,
        dispositivo_id INT,
        capacidade_total DECIMAL(10,2),
        nivel_minimo DECIMAL(5,2) DEFAULT 10.00,
        nivel_maximo DECIMAL(5,2) DEFAULT 90.00,
        status ENUM('ativo', 'inativo', 'manutencao') DEFAULT 'ativo',
        localizacao VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "<p>✅ Tabela 'reservatorios' criada/verificada</p>";
    
    // Inserir dados de exemplo - clientes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM clientes");
    $totalClientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($totalClientes == 0) {
        $sql = "INSERT INTO clientes (nome_fantasia, razao_social, cidade, estado, ativo) VALUES
            ('IBYT Tecnologia', 'IBYT Tecnologia LTDA', 'São Paulo', 'SP', 1),
            ('AquaTech Solutions', 'AquaTech Solutions LTDA', 'Rio de Janeiro', 'RJ', 1),
            ('HidroMax Sistemas', 'HidroMax Sistemas SA', 'Belo Horizonte', 'MG', 1)";
        $pdo->exec($sql);
        echo "<p>✅ Dados de exemplo inseridos na tabela 'clientes'</p>";
    } else {
        echo "<p>ℹ️ Tabela 'clientes' já possui dados ($totalClientes registros)</p>";
    }
    
    // Inserir dados de exemplo - dispositivos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM dispositivos");
    $totalDispositivos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($totalDispositivos == 0) {
        $sql = "INSERT INTO dispositivos (identificador, cliente_id, status, localizacao) VALUES
            ('D001', 1, 'ativo', 'Reservatório Principal - São Paulo'),
            ('D002', 1, 'ativo', 'Reservatório Secundário - São Paulo'),
            ('D003', 2, 'ativo', 'Tanque Central - Rio de Janeiro'),
            ('D004', 3, 'ativo', 'Cisterna Norte - Belo Horizonte'),
            ('D005', 3, 'inativo', 'Reservatório Sul - Belo Horizonte')";
        $pdo->exec($sql);
        echo "<p>✅ Dados de exemplo inseridos na tabela 'dispositivos'</p>";
    } else {
        echo "<p>ℹ️ Tabela 'dispositivos' já possui dados ($totalDispositivos registros)</p>";
    }
    
    // Inserir dados de exemplo - reservatorios
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reservatorios");
    $totalReservatorios = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($totalReservatorios == 0) {
        $sql = "INSERT INTO reservatorios (nome, cliente_id, dispositivo_id, capacidade_total, nivel_minimo, nivel_maximo, localizacao) VALUES
            ('Reservatório Principal SP', 1, 1, 10000.00, 15.00, 85.00, 'São Paulo - Centro'),
            ('Reservatório Secundário SP', 1, 2, 5000.00, 20.00, 90.00, 'São Paulo - Zona Sul'),
            ('Tanque Central RJ', 2, 3, 15000.00, 10.00, 80.00, 'Rio de Janeiro - Copacabana'),
            ('Cisterna Norte BH', 3, 4, 8000.00, 25.00, 95.00, 'Belo Horizonte - Pampulha'),
            ('Reservatório Sul BH', 3, 5, 12000.00, 15.00, 85.00, 'Belo Horizonte - Savassi')";
        $pdo->exec($sql);
        echo "<p>✅ Dados de exemplo inseridos na tabela 'reservatorios'</p>";
    } else {
        echo "<p>ℹ️ Tabela 'reservatorios' já possui dados ($totalReservatorios registros)</p>";
    }
    
    // Verificar tabela medicoes
    $stmt = $pdo->query("SHOW TABLES LIKE 'medicoes'");
    $tabelaMedicoes = $stmt->fetch();
    
    if (!$tabelaMedicoes) {
        echo "<p>❌ Tabela 'medicoes' não encontrada - será criada</p>";
        $sql = "CREATE TABLE medicoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            dispositivo_id INT NOT NULL,
            nivel_agua DECIMAL(5,2) NOT NULL,
            timestamp_medicao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            temperatura DECIMAL(5,2) NULL,
            status VARCHAR(50) DEFAULT 'normal'
        )";
        $pdo->exec($sql);
        echo "<p>✅ Tabela 'medicoes' criada</p>";
    } else {
        echo "<p>✅ Tabela 'medicoes' já existe</p>";
    }
    
    // Inserir algumas medições de exemplo
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM medicoes");
    $totalMedicoes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($totalMedicoes < 10) {
        echo "<p>ℹ️ Inserindo medições de exemplo...</p>";
        for ($i = 1; $i <= 5; $i++) {
            for ($j = 0; $j < 5; $j++) {
                $nivel = rand(20, 90);
                $horas = rand(1, 24);
                $sql = "INSERT INTO medicoes (dispositivo_id, nivel_agua, timestamp_medicao) VALUES 
                        ($i, $nivel, DATE_SUB(NOW(), INTERVAL $horas HOUR))";
                $pdo->exec($sql);
            }
        }
        echo "<p>✅ 25 medições de exemplo inseridas</p>";
    } else {
        echo "<p>ℹ️ Tabela 'medicoes' já possui dados ($totalMedicoes registros)</p>";
    }
    
    echo "<h2>🎉 Configuração concluída com sucesso!</h2>";
    echo "<p><a href='admin_simples.html'>🔗 Ir para o Dashboard</a></p>";
    echo "<p><a href='verificar_banco.php'>🔍 Verificar estrutura do banco</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Erro:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
