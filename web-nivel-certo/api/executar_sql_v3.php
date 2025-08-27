<?php
// Capturar todos os erros e warnings
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0); // Não mostrar erros na saída

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Função para limpar buffer e retornar JSON
function returnJson($data) {
    ob_clean(); // Limpar qualquer saída anterior
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Tratar erros fatais
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        returnJson([
            'success' => false,
            'message' => 'Erro fatal: ' . $error['message'],
            'erros' => ['Erro fatal na linha ' . $error['line'] . ': ' . $error['message']]
        ]);
    }
});

try {
    // Verificar se o arquivo de configuração existe
    $configFile = '../config/database-simples.php';
    if (!file_exists($configFile)) {
        $configFile = '../config/database.php';
        if (!file_exists($configFile)) {
            throw new Exception('Arquivo de configuração não encontrado');
        }
    }
    
    require_once $configFile;
    
    // Verificar se as configurações estão definidas
    if (!defined('DATA_LAYER_CONFIG')) {
        throw new Exception('Configurações de banco não definidas corretamente');
    }
    
    $config = DATA_LAYER_CONFIG;
    
    // Extrair configurações para variáveis individuais (compatibilidade)
    $host = $config['host'];
    $database = $config['dbname'];
    $username = $config['username'];
    $password = $config['passwd'];
    
    // Receber dados JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Dados inválidos');
    }
    
    $comandos = $input['comandos'] ?? [];
    $tipo = $input['tipo'] ?? 'completa';
    
    if (empty($comandos)) {
        throw new Exception('Nenhum comando SQL fornecido');
    }
    
    $startTime = microtime(true);
    $comandosExecutados = 0;
    $detalhes = [];
    $erros = [];
    
    // FASE 1: Conectar e criar/selecionar banco
    $detalhes[] = "🔌 Conectando ao MySQL...";
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Forçar criação e seleção do banco
    $detalhes[] = "🗃️ Criando/verificando banco '$database'...";
    $pdo->exec("DROP DATABASE IF EXISTS `$database`");
    $pdo->exec("CREATE DATABASE `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$database`");
    $detalhes[] = "✅ Banco '$database' criado e selecionado";
    
    // FASE 2: Configurar timezone
    $pdo->exec("SET time_zone = '-04:00'");
    $detalhes[] = "🕐 Timezone configurado para Cuiabá (-04:00)";
    
    // FASE 3: Executar comandos filtrados
    $comandosFiltrados = [];
    foreach ($comandos as $comando) {
        $comando = trim($comando);
        
        // Pular comandos vazios e comentários
        if (empty($comando) || 
            strpos($comando, '--') === 0 || 
            strpos($comando, '#') === 0 ||
            strpos($comando, '/*') === 0) {
            continue;
        }
        
        $cmdUpper = strtoupper($comando);
        
        // Pular comandos que já executamos
        if (strpos($cmdUpper, 'DROP DATABASE') !== false ||
            strpos($cmdUpper, 'CREATE DATABASE') !== false ||
            strpos($cmdUpper, 'USE ') === 0 ||
            strpos($cmdUpper, 'SET TIME_ZONE') !== false) {
            continue;
        }
        
        $comandosFiltrados[] = $comando;
    }
    
    $detalhes[] = "📋 Comandos para executar: " . count($comandosFiltrados);
    
    // FASE 4: Executar comandos
    foreach ($comandosFiltrados as $index => $comando) {
        try {
            $result = $pdo->exec($comando);
            $comandosExecutados++;
            
            // Log baseado no tipo de comando
            $cmdUpper = strtoupper($comando);
            
            if (strpos($cmdUpper, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE\s+(?:IF NOT EXISTS\s+)?`?(\w+)`?/i', $comando, $matches);
                $tabela = $matches[1] ?? 'desconhecida';
                $detalhes[] = "📋 Tabela '$tabela' criada";
                
            } elseif (strpos($cmdUpper, 'INSERT INTO') !== false) {
                preg_match('/INSERT INTO\s+`?(\w+)`?/i', $comando, $matches);
                $tabela = $matches[1] ?? 'desconhecida';
                $affected = $result !== false ? $result : 0;
                $detalhes[] = "📝 Dados inseridos em '$tabela' ($affected registros)";
                
            } elseif (strpos($cmdUpper, 'CREATE INDEX') !== false) {
                preg_match('/CREATE INDEX\s+`?(\w+)`?/i', $comando, $matches);
                $indice = $matches[1] ?? 'desconhecido';
                $detalhes[] = "🔍 Índice '$indice' criado";
                
            } elseif (strpos($cmdUpper, 'CREATE VIEW') !== false) {
                preg_match('/CREATE VIEW\s+`?(\w+)`?/i', $comando, $matches);
                $view = $matches[1] ?? 'desconhecida';
                $detalhes[] = "👁️ View '$view' criada";
                
            } else {
                $detalhes[] = "⚙️ Comando executado: " . substr($comando, 0, 50) . "...";
            }
            
        } catch (PDOException $e) {
            $erro = "❌ Erro no comando " . ($index + 1) . ": " . $e->getMessage();
            $erros[] = $erro;
            
            // Se for erro crítico, parar
            if (strpos($e->getMessage(), 'syntax error') !== false ||
                strpos($e->getMessage(), 'Unknown database') !== false) {
                throw new Exception("Erro crítico: " . $e->getMessage());
            }
        }
    }
    
    // FASE 5: Verificar resultados
    $detalhes[] = "🔍 Verificando estrutura criada...";
    
    $stmt = $pdo->query("SHOW TABLES");
    $tabelas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $detalhes[] = "📊 Tabelas criadas: " . count($tabelas) . " (" . implode(', ', $tabelas) . ")";
    
    // Contar registros nas principais tabelas
    $tabelasImportantes = ['usuarios', 'clientes', 'dispositivos', 'reservatorios', 'medicoes'];
    foreach ($tabelasImportantes as $tabela) {
        if (in_array($tabela, $tabelas)) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM `$tabela`");
                $count = $stmt->fetchColumn();
                $detalhes[] = "📈 $tabela: $count registros";
            } catch (Exception $e) {
                $detalhes[] = "⚠️ Erro ao contar $tabela: " . $e->getMessage();
            }
        }
    }
    
    // Testar timezone
    try {
        $stmt = $pdo->query("SELECT NOW() as horario_atual, @@time_zone as fuso");
        $tempo = $stmt->fetch(PDO::FETCH_ASSOC);
        $detalhes[] = "🕐 Horário atual: " . $tempo['horario_atual'] . " (Fuso: " . $tempo['fuso'] . ")";
    } catch (Exception $e) {
        $detalhes[] = "⚠️ Erro ao verificar timezone: " . $e->getMessage();
    }
    
    $endTime = microtime(true);
    $tempoExecucao = round($endTime - $startTime, 2) . ' segundos';
    
    $response = [
        'success' => true,
        'message' => 'Setup executado com sucesso!',
        'comandos_executados' => $comandosExecutados,
        'tempo' => $tempoExecucao,
        'detalhes' => $detalhes,
        'erros' => $erros,
        'total_comandos' => count($comandosFiltrados),
        'tabelas_criadas' => $tabelas,
        'tipo_execucao' => $tipo
    ];
    
    if (!empty($erros)) {
        $response['warning'] = 'Alguns comandos geraram warnings, mas o setup foi concluído';
    }
    
    returnJson($response);
    
} catch (Exception $e) {
    returnJson([
        'success' => false,
        'message' => 'Erro no setup: ' . $e->getMessage(),
        'erros' => [$e->getMessage()],
        'detalhes' => $detalhes ?? []
    ]);
}
?>
