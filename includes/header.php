<?php
require_once __DIR__ . '/auth.php';
$usuario = usuarioActual();
$flash = obtenerFlash();
$paginaActual = basename($_SERVER['SCRIPT_NAME']);
function enlaceNav(string $archivo, string $texto, string $actual): string
{
    $clase = $archivo === $actual ? ' class="activo"' : '';
    return '<a href="' . $archivo . '"' . $clase . '>' . $texto . '</a>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo ?? 'GolApp') ?> | GolApp</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body<?= $usuario ? '' : ' class="pagina-auth"' ?>>
<header class="barra">
    <a class="logo" href="dashboard.php">&#9917; GolApp</a>
    <?php if ($usuario): ?>
    <nav>
        <?= enlaceNav('dashboard.php', 'Inicio', $paginaActual) ?>
        <?= enlaceNav('calendario.php', 'Calendario', $paginaActual) ?>
        <?= enlaceNav('nueva_reserva.php', 'Reservar', $paginaActual) ?>
        <?= enlaceNav('reservas.php', esAdmin() ? 'Reservas' : 'Mis reservas', $paginaActual) ?>
        <?php if (esAdmin()): ?>
            <?= enlaceNav('canchas.php', 'Canchas', $paginaActual) ?>
            <?= enlaceNav('tipos.php', 'Tipos', $paginaActual) ?>
            <?= enlaceNav('inventario.php', 'Inventario', $paginaActual) ?>
            <?= enlaceNav('reporte.php', 'Reporte', $paginaActual) ?>
        <?php endif; ?>
    </nav>
    <div class="sesion">
        <span><?= e($usuario['nombre']) ?> <small>(<?= e($usuario['rol']) ?>)</small></span>
        <a class="btn btn-sec" href="logout.php">Salir</a>
    </div>
    <?php endif; ?>
</header>
<main class="contenedor">
<?php if ($flash): ?>
    <div class="alerta alerta-<?= e($flash['tipo']) ?>"><?= e($flash['mensaje']) ?></div>
<?php endif; ?>
