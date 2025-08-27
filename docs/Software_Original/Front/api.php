<?php
// Configurações do banco
$host = 'localhost';
$dbname = 'u454452574_ibyt_nivel';
$user = 'u454452574_ibyt_nivel';
$pass = 'Maionese#2025';

// Conectar ao banco
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao conectar ao banco de dados: ' . $e->getMessage()]);
    exit;
}

// Obter URI completa e extrair o dado bruto (após "api.php/")
$uri = $_SERVER['REQUEST_URI'];
$parts = explode('/', $uri);

// Último segmento após "api.php/"
$dadoBruto = end($parts);
$dadoBruto = trim($dadoBruto);

// Validar string
if (!preg_match_all('/[A-Z]{1}[0-9]+/i', $dadoBruto, $partes) || count($partes[0]) !== 3) {
    http_response_code(400);
    echo json_encode(['erro' => 'Formato inválido. Esperado: https://dominio/api.php/D2N20']);
    //Dispositivo e Nivel
    exit;
}

// Extrair os números
$cliente = preg_replace('/[^0-9]/', '', $partes[0][0]);       // Ex: 2
$nivel = preg_replace('/[^0-9]/', '', $partes[0][1]);         // Ex: 20


// Inserir no banco
$stmt = $pdo->prepare("INSERT INTO dados_sensor (cliente, reservatorio, nivel) VALUES (?, ?, ?)");
$inserido = $stmt->execute([$cliente, $reservatorio, $nivel]);

// Criar log
$logMsg = date('Y-m-d H:i:s') . " | Cliente: $cliente | Reservatório: $reservatorio | Nível: $nivel | Bruto: $dadoBruto | IP: " . $_SERVER['REMOTE_ADDR'] . PHP_EOL;
file_put_contents('log.txt', $logMsg, FILE_APPEND);

// Resposta
if ($inserido) {
    echo json_encode(['sucesso' => true, 'mensagem' => 'Dados inseridos com sucesso.']);
} else {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao inserir os dados.']);
}
?>
