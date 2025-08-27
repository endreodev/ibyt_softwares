<?php
// Incluir configurações para acessar PATCH
require_once __DIR__ . '/../config/database.php';

session_start();
session_destroy();

// Check if it's an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Check if it's a POST request (from our JavaScript function)
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';

if ($isAjax || $isPost) {
    // Return JSON response for AJAX requests
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Logout successful',
        'redirect' => PATCH . '/views/login'
    ]);
    exit;
} else {
    // Regular redirect for direct access
    header('Location: ' . PATCH . '/views/login');
    exit;
}
