<?php
/**
 * auth.php - Sesiones, control de acceso por rol y protección CSRF.
 * Las contraseñas se verifican con password_verify() (bcrypt).
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/funciones.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

/** Devuelve el usuario en sesión o null. */
function usuarioActual(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

function esAdmin(): bool
{
    return (usuarioActual()['rol'] ?? '') === 'administrador';
}

/** Exige sesión iniciada. */
function requiereLogin(): void
{
    if (!usuarioActual()) {
        redirigir('login.php');
    }
}

/** Exige rol administrador. */
function requiereAdmin(): void
{
    requiereLogin();
    if (!esAdmin()) {
        http_response_code(403);
        exit('Acceso denegado: solo administradores.');
    }
}

/** Token CSRF de la sesión. */
function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Campo oculto HTML con el token CSRF. */
function csrfCampo(): string
{
    return '<input type="hidden" name="csrf" value="' . csrfToken() . '">';
}

/** Verifica el token CSRF de un POST. */
function verificarCsrf(): void
{
    $ses = $_SESSION['csrf'] ?? '';
    $post = $_POST['csrf'] ?? '';
    // Sin token en sesión, '' === '' pasaría el check y anularía el CSRF.
    if ($ses === '' || !hash_equals($ses, $post)) {
        http_response_code(419);
        exit('Solicitud inválida (token CSRF).');
    }
}
