<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Função para retornar JSON padronizado
function returnJson($data) {
    $output = ob_get_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Iniciar buffer de saída e suprimir erros de display
ob_start();
error_reporting(0);

// Verificar se o arquivo de configuração existe
$configFile = '../config/database-simples.php';
if (!file_exists($configFile)) {
    $configFile = '../config/database.php';
    if (!file_exists($configFile)) {
        returnJson([
            'success' => false,
            'message' => 'Arquivo de configuração não encontrado',
            'banco_existe' => false,
            'tabelas' => []
        ]);
    }
}

require_once $configFile;

// Verificar se as configurações estão definidas
if (!defined('DATA_LAYER_CONFIG')) {
    returnJson([
        'success' => false,
        'message' => 'Configurações de banco não definidas corretamente',
        'banco_existe' => false,
        'tabelas' => []
    ]);
}

$config = DATA_LAYER_CONFIG;

// Extrair configurações para variáveis individuais
$host = $config['host'];
$database = $config['dbname'];
$username = $config['username'];
$password = $config['passwd'];

try {
    // Conectar ao banco
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar se o banco existe
    $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?");
    $stmt->execute([$database]);
    $bancoExiste = $stmt->fetch() !== false;
    
    $tabelas = [];
    
    if ($bancoExiste) {
        // Selecionar banco
        $pdo->exec("USE `$database`");
        
        // Lista de tabelas esperadas
        $tabelasEsperadas = [
            'clientes',
            'reservatorios', 
            'dispositivos',
            'medicoes',
            'notificacoes',
            'ordens_servico',
            'usuarios'
        ];
        
        foreach ($tabelasEsperadas as $nomeTabela) {
            try {
                // Verificar se a tabela existe
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = ? AND table_name = ?");
                $stmt->execute([$database, $nomeTabela]);
                $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $existe = $resultado['count'] > 0;
                $registros = 0;
                
                if ($existe) {
                    // Contar registros
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$nomeTabela`");
                        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
                        $registros = $resultado['count'];
                    } catch (Exception $e) {
                        $registros = 'Erro ao contar';
                    }
                }
                
                $tabelas[] = [
                    'nome' => $nomeTabela,
                    'existe' => $existe,
                    'registros' => $registros
                ];
                
            } catch (Exception $e) {
                $tabelas[] = [
                    'nome' => $nomeTabela,
                    'existe' => false,
                    'registros' => 0,
                    'erro' => $e->getMessage()
                ];
            }
        }
        
        // Verificar outras tabelas existentes não listadas
        $stmt = $pdo->query("SHOW TABLES");
        $todasTabelas = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $tabelasExtras = array_diff($todasTabelas, $tabelasEsperadas);
        foreach ($tabelasExtras as $tabelaExtra) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$tabelaExtra`");
                $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $tabelas[] = [
                    'nome' => $tabelaExtra,
                    'existe' => true,
                    'registros' => $resultado['count'],
                    'extra' => true
                ];
            } catch (Exception $e) {
                $tabelas[] = [
                    'nome' => $tabelaExtra,
                    'existe' => true,
                    'registros' => 'Erro',
                    'extra' => true
                ];
            }
        }
    }
    
    // Informações adicionais do banco
    $infoAdicional = [];
    
    if ($bancoExiste) {
        try {
            // Versão do MySQL
            $stmt = $pdo->query("SELECT VERSION() as version");
            $version = $stmt->fetch(PDO::FETCH_ASSOC);
            $infoAdicional['mysql_version'] = $version['version'];
            
            // Charset do banco
            $stmt = $pdo->prepare("SELECT DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?");
            $stmt->execute([$database]);
            $charset = $stmt->fetch(PDO::FETCH_ASSOC);
            $infoAdicional['charset'] = $charset;
            
            // Tamanho do banco
            $stmt = $pdo->prepare("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'size_mb' FROM information_schema.tables WHERE table_schema = ?");
            $stmt->execute([$database]);
            $size = $stmt->fetch(PDO::FETCH_ASSOC);
            $infoAdicional['tamanho_mb'] = $size['size_mb'];
            
        } catch (Exception $e) {
            $infoAdicional['erro'] = $e->getMessage();
        }
    }
    
    $response = [
        'success' => true,
        'banco_existe' => $bancoExiste,
        'database_name' => $database,
        'tabelas' => $tabelas,
        'info_adicional' => $infoAdicional,
        'total_tabelas' => count($tabelas),
        'tabelas_existentes' => count(array_filter($tabelas, function($t) { return $t['existe']; }))
    ];
    
    returnJson($response);
    
} catch (Exception $e) {
    returnJson([
        'success' => false,
        'message' => $e->getMessage(),
        'banco_existe' => false,
        'tabelas' => []
    ]);
}
?>
