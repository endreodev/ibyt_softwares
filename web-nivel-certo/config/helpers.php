<?php
/**
 * Funções auxiliares para URLs e paths do sistema
 */

// Incluir configurações se ainda não foram incluídas
if (!defined('PATCH')) {
    require_once __DIR__ . '/database.php';
}

/**
 * Gera URL completa para uma view
 * @param string $view Nome da view (ex: 'login', 'dashboard')
 * @return string URL completa
 */
function viewUrl($view) {
    if (empty(PATCH)) {
        return '/views/' . ltrim($view, '/');
    }
    return PATCH . '/views/' . ltrim($view, '/');
}

/**
 * Gera URL completa para uma API
 * @param string $api Nome da API (ex: 'usuarios.php', 'logout.php')
 * @return string URL completa
 */
function apiUrl($api) {
    if (empty(PATCH)) {
        return '/api/' . ltrim($api, '/');
    }
    return PATCH . '/api/' . ltrim($api, '/');
}

/**
 * Gera URL completa para um asset
 * @param string $asset Caminho do asset (ex: 'js/api.js', 'css/style.css')
 * @return string URL completa
 */
function assetUrl($asset) {
    if (empty(PATCH)) {
        return '/assets/' . ltrim($asset, '/');
    }
    return PATCH . '/assets/' . ltrim($asset, '/');
}

/**
 * Gera URL base da aplicação
 * @param string $path Caminho adicional (opcional)
 * @return string URL completa
 */
function baseUrl($path = '') {
    if (empty($path)) {
        return PATCH;
    }
    if (empty(PATCH)) {
        return '/' . ltrim($path, '/');
    }
    return PATCH . '/' . ltrim($path, '/');
}

/**
 * Gera URL para um script
 * @param string $script Caminho do script (ex: 'monitor_automatico.php')
 * @return string Caminho completo do sistema
 */
function scriptPath($script) {
    // Para scripts, retorna o caminho físico do sistema
    $basePath = $_SERVER['DOCUMENT_ROOT'];
    if (!empty(PATCH)) {
        $basePath .= PATCH;
    }
    return $basePath . '/scripts/' . ltrim($script, '/');
}

/**
 * Gera URL HTTP completa para um script (para cron jobs, etc)
 * @param string $script Caminho do script
 * @return string URL HTTP completa
 */
function scriptUrl($script) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (empty(PATCH)) {
        return $protocol . '://' . $host . '/scripts/' . ltrim($script, '/');
    }
    return $protocol . '://' . $host . PATCH . '/scripts/' . ltrim($script, '/');
}
