<?php

// Carregar autoload do Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Definir fuso horário de Cuiabá
date_default_timezone_set('America/Cuiaba');

const VERSION = "1.0.0";
// Para desenvolvimento local, use "/nivel-certo"
// Para produção/hospedagem, use "" (string vazia) ou o subdiretório correto
// Para Docker, use ""
const PATCH = "/nivel-certo";

// Detectar ambiente Docker
$isDocker = (
    file_exists('/.dockerenv') || 
    getenv('DOCKER_ENV') || 
    ($_SERVER['HTTP_HOST'] ?? '') === 'localhost:8080'
);

// Detectar ambiente local
$isLocal = (
    ($_SERVER['HTTP_HOST'] ?? 'localhost') === 'localhost' || 
    strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false ||
    strpos($_SERVER['HTTP_HOST'] ?? '', '.local') !== false
) && !$isDocker;

// Configuração dinâmica baseada no ambiente
if ($isDocker) {
    // Configuração para Docker
    define('PATCH', ""); // Sem subdiretório no Docker
    define('DATA_LAYER_CONFIG', [
        "driver" => "mysql",
        "host" => "db", // Nome do serviço Docker
        "port" => "3306", 
        "dbname" => "erp_agua",
        "username" => "root",
        "passwd" => "root123",
        "options" => [
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_CASE => PDO::CASE_NATURAL
        ]
    ]);
} elseif ($isLocal) {
    // Configuração para desenvolvimento local
    define('DATA_LAYER_CONFIG', [
        "driver" => "mysql",
        "host" => "localhost",
        "port" => "3306", 
        "dbname" => "erp_agua",
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
    // Configuração para produção
    define('DATA_LAYER_CONFIG', [
        "driver" => "mysql",
        "host" => "62.72.62.1",
        "port" => "3306",
        "dbname" => "u454452574_ibyt_nivel",
        "username" => "u454452574_ibyt_nivel",
        "passwd" => "Samurai@157",
        "options" => [
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_CASE => PDO::CASE_NATURAL
        ]
    ]);
}