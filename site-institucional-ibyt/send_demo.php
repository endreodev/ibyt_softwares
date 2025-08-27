<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Validar e sanitizar dados
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$company = trim($_POST['company'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$message = trim($_POST['message'] ?? '');

// Validações
if (empty($name) || empty($email) || empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Por favor, preencha todos os campos obrigatórios.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'E-mail inválido.']);
    exit;
}

// Configurações do email
$to = 'endreo.dev@gmail.com';
$subject = 'Solicitação de Demonstração - Nível Certo';

// Corpo do email em HTML
$email_body = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #25D366; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 20px; }
        .field { margin-bottom: 15px; }
        .label { font-weight: bold; color: #555; }
        .value { margin-top: 5px; }
        .footer { background: #333; color: white; padding: 15px; text-align: center; font-size: 12px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>Solicitação de Demonstração - Nível Certo</h2>
        </div>
        <div class='content'>
            <div class='field'>
                <div class='label'>Nome:</div>
                <div class='value'>" . htmlspecialchars($name) . "</div>
            </div>
            <div class='field'>
                <div class='label'>E-mail:</div>
                <div class='value'>" . htmlspecialchars($email) . "</div>
            </div>
            <div class='field'>
                <div class='label'>Empresa:</div>
                <div class='value'>" . htmlspecialchars($company ?: 'Não informado') . "</div>
            </div>
            <div class='field'>
                <div class='label'>Telefone:</div>
                <div class='value'>" . htmlspecialchars($phone) . "</div>
            </div>
            <div class='field'>
                <div class='label'>Mensagem:</div>
                <div class='value'>" . nl2br(htmlspecialchars($message ?: 'Solicitação de demonstração do sistema Nível Certo')) . "</div>
            </div>
            <div class='field'>
                <div class='label'>Data/Hora:</div>
                <div class='value'>" . date('d/m/Y H:i:s') . "</div>
            </div>
            <div class='field'>
                <div class='label'>IP:</div>
                <div class='value'>" . $_SERVER['REMOTE_ADDR'] . "</div>
            </div>
        </div>
        <div class='footer'>
            IBYT Software - Sistema Nível Certo
        </div>
    </div>
</body>
</html>
";

// Headers do email
$headers = [
    'MIME-Version: 1.0',
    'Content-type: text/html; charset=UTF-8',
    'From: noreply@ibyt.com.br',
    'Reply-To: ' . $email,
    'X-Mailer: PHP/' . phpversion()
];

// Tentar enviar o email
if (mail($to, $subject, $email_body, implode("\r\n", $headers))) {
    echo json_encode(['success' => true, 'message' => 'Solicitação enviada com sucesso! Entraremos em contato para agendar a demonstração.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao enviar solicitação. Tente novamente ou entre em contato pelo WhatsApp.']);
}
?>
