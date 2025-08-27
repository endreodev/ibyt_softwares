-- Script SQL SIMPLIFICADO para ERP Água
-- Versão otimizada para resolver problemas de setup

-- Criar tabela de usuários
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('admin', 'operador') DEFAULT 'operador',
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Criar tabela de clientes
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_fantasia VARCHAR(255) NOT NULL,
    razao_social VARCHAR(255),
    cpf_cnpj VARCHAR(20),
    email VARCHAR(255),
    telefone VARCHAR(20),
    endereco TEXT,
    cidade VARCHAR(100),
    estado VARCHAR(2),
    cep VARCHAR(10),
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Criar tabela de dispositivos
CREATE TABLE dispositivos (
    id INT PRIMARY KEY,
    cliente_id INT DEFAULT NULL,
    identificador VARCHAR(100) DEFAULT NULL,
    tipo VARCHAR(50) DEFAULT 'sensor_nivel',
    status ENUM('ativo', 'inativo', 'manutencao') DEFAULT 'ativo',
    localizacao TEXT DEFAULT NULL,
    observacoes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Criar tabela de reservatórios
CREATE TABLE reservatorios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT DEFAULT NULL,
    dispositivo_id INT DEFAULT NULL,
    nome VARCHAR(255) NOT NULL,
    capacidade DECIMAL(10,2) DEFAULT NULL,
    altura_total DECIMAL(8,2) DEFAULT NULL,
    altura_minima DECIMAL(8,2) DEFAULT 10.00,
    altura_maxima DECIMAL(8,2) DEFAULT NULL,
    tipo ENUM('cilindrico', 'retangular', 'outro') DEFAULT 'cilindrico',
    localizacao TEXT DEFAULT NULL,
    descricao TEXT DEFAULT NULL,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Criar tabela de medições
CREATE TABLE medicoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dispositivo_id INT NOT NULL,
    nivel_agua DECIMAL(8,2) NOT NULL,
    percentual DECIMAL(5,2) DEFAULT NULL,
    temperatura DECIMAL(5,2) DEFAULT NULL,
    bateria DECIMAL(5,2) DEFAULT NULL,
    qualidade_sinal INT DEFAULT NULL,
    timestamp_medicao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Criar tabela de notificações
CREATE TABLE notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT DEFAULT NULL,
    dispositivo_id INT DEFAULT NULL,
    reservatorio_id INT DEFAULT NULL,
    tipo ENUM('nivel_baixo', 'nivel_critico', 'nivel_alto', 'dispositivo_offline', 'manutencao', 'fatura', 'bateria_baixa') DEFAULT 'nivel_baixo',
    titulo VARCHAR(255) NOT NULL,
    mensagem TEXT NOT NULL,
    nivel_atual DECIMAL(8,2) DEFAULT NULL,
    percentual_atual DECIMAL(5,2) DEFAULT NULL,
    lida TINYINT(1) DEFAULT 0,
    enviada TINYINT(1) DEFAULT 0,
    data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Criar tabela de ordens de serviço
CREATE TABLE ordens_servico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT DEFAULT NULL,
    dispositivo_id INT DEFAULT NULL,
    reservatorio_id INT DEFAULT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    tipo ENUM('instalacao', 'manutencao', 'reparo', 'retirada') DEFAULT 'manutencao',
    prioridade ENUM('baixa', 'media', 'alta', 'urgente') DEFAULT 'media',
    status ENUM('aberta', 'em_andamento', 'concluida', 'cancelada') DEFAULT 'aberta',
    data_abertura TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_agendamento DATETIME DEFAULT NULL,
    data_conclusao DATETIME DEFAULT NULL,
    tecnico_responsavel VARCHAR(255) DEFAULT NULL,
    observacoes TEXT DEFAULT NULL,
    valor_orcado DECIMAL(10,2) DEFAULT NULL,
    valor_final DECIMAL(10,2) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inserir usuários administradores
INSERT INTO usuarios (nome, email, senha, tipo) VALUES 
('Administrador', 'admin@sistema.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Administrador IBYT', 'admin@ibyt.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Inserir clientes de exemplo
INSERT INTO clientes (nome_fantasia, razao_social, cpf_cnpj, email, telefone, cidade, estado) VALUES 
('Fazenda Bom Jesus', 'Fazenda Bom Jesus LTDA', '12.345.678/0001-90', 'contato@fazendabomjesus.com', '(65) 99999-1111', 'Cuiabá', 'MT'),
('Condomínio Vista Verde', 'Condomínio Vista Verde', '98.765.432/0001-10', 'administracao@vistaverde.com', '(65) 99999-2222', 'Várzea Grande', 'MT'),
('Empresa Agro Tech', 'Agro Tech Soluções LTDA', '11.222.333/0001-44', 'sistemas@agrotech.com', '(65) 99999-3333', 'Rondonópolis', 'MT');

-- Inserir dispositivos de exemplo
INSERT INTO dispositivos (id, cliente_id, identificador, tipo, status, localizacao) VALUES 
(1, 1, 'SENSOR_FAZENDA_001', 'sensor_nivel', 'ativo', 'Reservatório Principal - Fazenda Bom Jesus'),
(2, 1, 'SENSOR_FAZENDA_002', 'sensor_nivel', 'ativo', 'Reservatório Secundário - Fazenda Bom Jesus'),
(3, 2, 'SENSOR_COND_001', 'sensor_nivel', 'ativo', 'Reservatório Central - Condomínio Vista Verde'),
(4, 2, 'SENSOR_COND_002', 'sensor_nivel', 'ativo', 'Reservatório Backup - Condomínio Vista Verde'),
(5, 3, 'SENSOR_AGRO_001', 'sensor_nivel', 'ativo', 'Tanque Principal - Agro Tech'),
(10, NULL, 'SENSOR_TESTE_010', 'sensor_nivel', 'ativo', 'Dispositivo de Teste - Sem Cliente');

-- Inserir reservatórios de exemplo
INSERT INTO reservatorios (cliente_id, dispositivo_id, nome, capacidade, altura_total, altura_minima, altura_maxima, tipo, localizacao, descricao) VALUES 
(1, 1, 'Reservatório Principal Fazenda', 10000.00, 250.00, 20.00, 240.00, 'cilindrico', 'Área Central da Fazenda', 'Reservatório principal para irrigação e consumo animal'),
(1, 2, 'Reservatório Secundário Fazenda', 5000.00, 180.00, 15.00, 170.00, 'cilindrico', 'Área Norte da Fazenda', 'Reservatório de apoio para períodos de seca'),
(2, 3, 'Reservatório Central Condomínio', 15000.00, 300.00, 30.00, 290.00, 'retangular', 'Casa de Bombas Central', 'Reservatório principal de abastecimento do condomínio'),
(2, 4, 'Reservatório Backup Condomínio', 8000.00, 200.00, 20.00, 190.00, 'cilindrico', 'Torre Norte', 'Reservatório de emergência'),
(3, 5, 'Tanque Principal Agro Tech', 20000.00, 400.00, 40.00, 380.00, 'retangular', 'Galpão Principal', 'Tanque para processamento e armazenamento');

-- Inserir medições de exemplo
INSERT INTO medicoes (dispositivo_id, nivel_agua, percentual, timestamp_medicao) VALUES 
(1, 150.00, 60.00, DATE_SUB(NOW(), INTERVAL 5 MINUTE)),
(2, 90.00, 50.00, DATE_SUB(NOW(), INTERVAL 10 MINUTE)),
(3, 200.00, 66.67, DATE_SUB(NOW(), INTERVAL 15 MINUTE)),
(4, 120.00, 60.00, DATE_SUB(NOW(), INTERVAL 20 MINUTE)),
(5, 250.00, 62.50, DATE_SUB(NOW(), INTERVAL 25 MINUTE));

-- Criar índices
CREATE INDEX idx_medicoes_dispositivo ON medicoes(dispositivo_id);
CREATE INDEX idx_medicoes_timestamp ON medicoes(timestamp_medicao);
CREATE INDEX idx_dispositivos_cliente ON dispositivos(cliente_id);
CREATE INDEX idx_reservatorios_dispositivo ON reservatorios(dispositivo_id);
CREATE INDEX idx_reservatorios_cliente ON reservatorios(cliente_id);
