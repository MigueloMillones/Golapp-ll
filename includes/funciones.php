<?php
/**
 * funciones.php - Utilidades generales: escape HTML, redirecciones,
 * mensajes flash y formato.
 */

/** Escapa texto para imprimirlo de forma segura en HTML (previene XSS). */
function e($texto): string
{
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}

/** Redirige a otra página y termina la ejecución. */
function redirigir(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/** Guarda un mensaje para mostrarlo en la siguiente página. */
function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

/** Obtiene y borra el mensaje flash. */
function obtenerFlash(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/** Formatea un valor como pesos colombianos. */
function moneda($valor): string
{
    return '$' . number_format((float)$valor, 0, ',', '.');
}

/** Devuelve la insignia HTML de un estado de reserva. */
function insignia(string $estado): string
{
    return '<span class="badge badge-' . e($estado) . '">' . e(ucfirst($estado)) . '</span>';
}

/** Convierte una hora entera (ej. 8) en formato TIME de MySQL ("08:00:00"). */
function horaSql(int $hora): string
{
    return sprintf('%02d:00:00', $hora);
}
