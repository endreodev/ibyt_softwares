<?php
// Teste simples da API
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    // Incluir configurações
    $configFile = '../config/database-simples.php';
    if (!file_exists($configFile)) {
        $configFile = '../config/database.php';
    }
    require_once $configFile;
    
    $config = DATA_LAYER_CONFIG;
    $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4", 
                   $config['username'], $config['passwd']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo json_encode([
        'success' => true,
        'message' => 'API funcionando',
        'data' => [
            'estatisticas' => [
                'total_clientes' => 3,
                'total_dispositivos' => 5,
                'total_reservatorios' => 5,
                'medicoes_hoje' => 10
            ],
            'debug' => 'Dados retornados com sucesso'
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => true
    ], JSON_PRETTY_PRINT);
}
?>
