<?php
// logout.php - Cierra la sesión.
require_once __DIR__ . '/includes/auth.php';
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => $p['path'] ?? '/',
        'domain' => $p['domain'] ?? '',
        'secure' => (bool)($p['secure'] ?? false),
        'httponly' => true,
        'samesite' => $p['samesite'] ?: 'Lax',
    ]);
}
session_destroy();
redirigir('login.php');
