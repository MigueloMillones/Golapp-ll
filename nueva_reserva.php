<?php
/** nueva_reserva.php - Crear reserva (cliente para sí mismo; administrador para cualquier cliente). */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/reservas_lib.php';
requiereLogin();
$db = getDb();
$u = usuarioActual();

$canchas = $db->query('SELECT c.id, c.nombre, c.precio_hora, t.nombre AS tipo FROM canchas c
                       JOIN tipos_cancha t ON t.id = c.id_tipo WHERE c.activa = 1 ORDER BY c.nombre')->fetchAll();
$clientes = esAdmin() ? $db->query("SELECT id, nombre FROM usuarios WHERE rol = 'cliente' ORDER BY nombre")->fetchAll() : [];

$sel = [
    'cancha' => (int)($_REQUEST['cancha'] ?? 0),
    'fecha' => $_REQUEST['fecha'] ?? date('Y-m-d'),
    'hora' => (int)($_REQUEST['hora'] ?? HORA_APERTURA),
    'duracion' => (int)($_REQUEST['duracion'] ?? 1),
    'cliente' => (int)($_REQUEST['cliente'] ?? 0),
];
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    $idUsuario = $u['id'];
    $estado = 'pendiente';
    if (esAdmin()) {                       // Reserva manual del administrador
        $idUsuario = $sel['cliente'];
        $estado = 'confirmada';
        $chk = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE id = ? AND rol = 'cliente'");
        $chk->execute([$idUsuario]);
        if (!(int)$chk->fetchColumn()) {
            $idUsuario = 0;
        }
    }
    if ($idUsuario <= 0 || $sel['cancha'] <= 0) {
        $error = 'Selecciona la cancha' . (esAdmin() ? ' y el cliente.' : '.');
    } else {
        [$ok, $msg] = crearReserva($db, $idUsuario, $sel['cancha'], $sel['fecha'], $sel['hora'], $sel['duracion'], $estado);
        if ($ok) {
            flash('ok', $msg . (esAdmin() ? '' : ' Queda pendiente de confirmación.'));
            redirigir('reservas.php');
        }
        $error = $msg;
    }
}
$titulo = 'Nueva reserva';
require __DIR__ . '/includes/header.php';
?>
<h1>Nueva reserva</h1>
<?php if (!$canchas): ?>
    <div class="alerta alerta-error">No hay canchas activas para reservar.</div>
<?php endif; ?>
<div class="tarjeta">
    <?php if ($error): ?><div class="alerta alerta-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="fila">
        <?= csrfCampo() ?>
        <?php if (esAdmin()): ?>
        <div class="campo"><label>Cliente</label><select name="cliente" required>
            <option value="">Selecciona...</option>
            <?php foreach ($clientes as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $sel['cliente'] == $c['id'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option><?php endforeach; ?>
        </select></div>
        <?php endif; ?>
        <div class="campo"><label>Cancha</label><select name="cancha" id="id_cancha" required>
            <?php foreach ($canchas as $c): ?>
            <option value="<?= (int)$c['id'] ?>" data-precio="<?= e($c['precio_hora']) ?>" <?= $sel['cancha'] == $c['id'] ? 'selected' : '' ?>>
                <?= e($c['nombre']) ?> (<?= e($c['tipo']) ?>) - <?= moneda($c['precio_hora']) ?>/h</option>
            <?php endforeach; ?></select></div>
        <div class="campo"><label>Fecha</label><input type="date" name="fecha" min="<?= date('Y-m-d') ?>" value="<?= e($sel['fecha']) ?>" required></div>
        <div class="campo"><label>Hora de inicio</label><select name="hora">
            <?php for ($h = HORA_APERTURA; $h < HORA_CIERRE; $h++): ?><option value="<?= $h ?>" <?= $sel['hora'] == $h ? 'selected' : '' ?>><?= sprintf('%02d', $h) ?>:00</option><?php endfor; ?>
        </select></div>
        <div class="campo"><label>Duración</label><select name="duracion" id="duracion">
            <?php for ($i = 1; $i <= DURACION_MAXIMA; $i++): ?><option value="<?= $i ?>" <?= $sel['duracion'] == $i ? 'selected' : '' ?>><?= $i ?> hora<?= $i > 1 ? 's' : '' ?></option><?php endfor; ?>
        </select></div>
        <div class="campo"><label>Total estimado</label><b id="totalEstimado">-</b></div>
        <button class="btn" type="submit">Reservar</button>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
