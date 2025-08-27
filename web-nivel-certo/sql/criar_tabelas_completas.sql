-- Script para criar todas as tabelas necessárias
-- Execute este script no phpMyAdmin ou MySQL

USE erp_agua;

-- Tabela de clientes
CREATE TABLE IF NOT EXISTS clientes (
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
);

-- Tabela de dispositivos
CREATE TABLE IF NOT EXISTS dispositivos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identificador VARCHAR(50) UNIQUE NOT NULL,
    cliente_id INT,
    status ENUM('ativo', 'inativo', 'manutencao') DEFAULT 'ativo',
    tipo VARCHAR(50) DEFAULT 'sensor_nivel',
    localizacao VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de reservatórios
CREATE TABLE IF NOT EXISTS reservatorios (
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
);

-- Inserir dados de exemplo
INSERT IGNORE INTO clientes (id, nome_fantasia, razao_social, cidade, estado, ativo) VALUES
(1, 'IBYT Tecnologia', 'IBYT Tecnologia LTDA', 'São Paulo', 'SP', 1),
(2, 'AquaTech Solutions', 'AquaTech Solutions LTDA', 'Rio de Janeiro', 'RJ', 1),
(3, 'HidroMax Sistemas', 'HidroMax Sistemas SA', 'Belo Horizonte', 'MG', 1);

INSERT IGNORE INTO dispositivos (id, identificador, cliente_id, status, localizacao) VALUES
(1, 'D001', 1, 'ativo', 'Reservatório Principal - São Paulo'),
(2, 'D002', 1, 'ativo', 'Reservatório Secundário - São Paulo'),
(3, 'D003', 2, 'ativo', 'Tanque Central - Rio de Janeiro'),
(4, 'D004', 3, 'ativo', 'Cisterna Norte - Belo Horizonte'),
(5, 'D005', 3, 'inativo', 'Reservatório Sul - Belo Horizonte');

INSERT IGNORE INTO reservatorios (id, nome, cliente_id, dispositivo_id, capacidade_total, nivel_minimo, nivel_maximo, localizacao) VALUES
(1, 'Reservatório Principal SP', 1, 1, 10000.00, 15.00, 85.00, 'São Paulo - Centro'),
(2, 'Reservatório Secundário SP', 1, 2, 5000.00, 20.00, 90.00, 'São Paulo - Zona Sul'),
(3, 'Tanque Central RJ', 2, 3, 15000.00, 10.00, 80.00, 'Rio de Janeiro - Copacabana'),
(4, 'Cisterna Norte BH', 3, 4, 8000.00, 25.00, 95.00, 'Belo Horizonte - Pampulha'),
(5, 'Reservatório Sul BH', 3, 5, 12000.00, 15.00, 85.00, 'Belo Horizonte - Savassi');

-- Verificar se existe a tabela medicoes
SELECT COUNT(*) as existe FROM information_schema.tables 
WHERE table_schema = 'erp_agua' AND table_name = 'medicoes';
