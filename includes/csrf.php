<?php
/**
 * Proteccion CSRF simple por sesion
 */

if (!function_exists('csrf_token')) {
    function csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_campo')) {
    function csrf_campo() {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
    }
}

if (!function_exists('csrf_validar')) {
    function csrf_validar() {
        $token = $_POST['csrf_token'] ?? '';
        $sesion = $_SESSION['csrf_token'] ?? '';
        if (empty($sesion) || !hash_equals($sesion, $token)) {
            http_response_code(400);
            die('Solicitud no valida (proteccion CSRF). Vuelva atras e intente nuevamente.');
        }
    }
}