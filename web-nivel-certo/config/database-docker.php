<?php

// Definir fuso horário de Cuiabá
date_default_timezone_set('America/Cuiaba');

const VERSION = "1.0.0";
const PATCH = "";

// Detectar se está rodando no Docker
$isDocker = isset($_ENV['DOCKER_ENV']) || getenv('DOCKER_ENV') || file_exists('/.dockerenv');

// Configuração para Docker ou desenvolvimento local
if ($isDocker) {
    // Configuração para Docker
    define('DATA_LAYER_CONFIG', [
        "driver" => "mysql",
        "host" => "db", // Nome do serviço no docker-compose
        "port" => "3306", 
        "dbname" => "nivercerto",
        "username" => "nivel_user",
        "passwd" => "nivel123",
        "options" => [
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_CASE => PDO::CASE_NATURAL
        ]
    ]);
} else {
    // Detectar ambiente local
    $isLocal = (
        ($_SERVER['HTTP_HOST'] ?? 'localhost') === 'localhost' || 
        strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false ||
        strpos($_SERVER['HTTP_HOST'] ?? '', '.local') !== false
    );

    if ($isLocal) {
        // Configuração para desenvolvimento local
        define('DATA_LAYER_CONFIG', [
            "driver" => "mysql",
            "host" => "localhost",
            "port" => "3306", 
            "dbname" => "nivercerto",
            "username" => "root",
            "passwd" => "",
            "options" => [
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                PDO::ATTR_CASE => PDO::CASE_NATURAL
            ]
        ]);
    } else {
        // Configuração para produção (ajustar conforme necessário)
        define('DATA_LAYER_CONFIG', [
            "driver" => "mysql",
            "host" => $_ENV['DB_HOST'] ?? "localhost",
            "port" => $_ENV['DB_PORT'] ?? "3306",
            "dbname" => $_ENV['DB_NAME'] ?? "nivercerto",
            "username" => $_ENV['DB_USER'] ?? "root",
            "passwd" => $_ENV['DB_PASS'] ?? "",
            "options" => [
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                PDO::ATTR_CASE => PDO::CASE_NATURAL
            ]
        ]);
    }
}

// Função helper para obter URL base
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $port = $_SERVER['SERVER_PORT'] ?? '80';
    
    // Se for Docker e estiver na porta 8080, ajustar
    if (file_exists('/.dockerenv') && $port == '80' && strpos($host, ':') === false) {
        $host = $host . ':8080';
    }
    
    return $protocol . $host . PATCH;
}

// Constante para URL base
define('BASE_URL', getBaseUrl());
