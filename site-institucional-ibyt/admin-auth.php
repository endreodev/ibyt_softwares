<?php
/**
 * Arquivo de proteção para páginas administrativas
 * Inclua este arquivo no início de qualquer página que precise de autenticação admin
 */

// Iniciar sessão se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado
function checkAdminAuth() {
    // Verificar se está logado
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        // Redirecionar para login
        header('Location: login-admin.php');
        exit();
    }
    
    // Verificar se a sessão não expirou (6 horas)
    $session_timeout = 6 * 60 * 60; // 6 horas em segundos
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $session_timeout)) {
        // Sessão expirou
        session_destroy();
        header('Location: login-admin.php?expired=1');
        exit();
    }
    
    // Atualizar tempo da última atividade
    $_SESSION['last_activity'] = time();
    
    return true;
}

// Função para fazer logout
function adminLogout() {
    session_destroy();
    header('Location: login-admin.php?logout=1');
    exit();
}

// Verificar autenticação automaticamente
checkAdminAuth();
?>
