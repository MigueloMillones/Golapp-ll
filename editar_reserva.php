<?php
/** editar_reserva.php - El administrador modifica cancha, fecha u horario de una reserva. */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/reservas_lib.php';
requiereAdmin();
$db = getDb();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$st = $db->prepare('SELECT * FROM reservas WHERE id = ?');
$st->execute([$id]);
$r = $st->fetch();
if (!$r) { flash('error', 'La reserva no existe.'); redirigir('reservas.php'); }
if (!in_array($r['estado'], ['pendiente', 'confirmada'], true)) {
    flash('error', 'Solo se pueden modificar reservas pendientes o confirmadas.');
    redirigir('reservas.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    $idCancha = (int)$_POST['cancha'];
    $fecha = $_POST['fecha'] ?? '';
    $hora = (int)($_POST['hora'] ?? 0);
    $dur = (int)($_POST['duracion'] ?? 1);
    $error = validarHorario($fecha, $hora, $dur) ?? '';
    if (!$error) {
        try {
            $db->beginTransaction();
            $lock = $db->prepare('SELECT estado FROM reservas WHERE id = ? FOR UPDATE');
            $lock->execute([$id]);
            $estadoActual = $lock->fetchColumn();
            if (!in_array($estadoActual, ['pendiente', 'confirmada'], true)) {
                $db->rollBack();
                $error = 'La reserva ya no se puede modificar.';
            } else {
                $p = $db->prepare('SELECT precio_hora FROM canchas WHERE id = ? AND (activa = 1 OR id = ?) FOR UPDATE');
                $p->execute([$idCancha, $r['id_cancha']]);
                $precio = $p->fetchColumn();
                $ini = horaSql($hora); $fin = horaSql($hora + $dur);
                if ($precio === false) {
                    $db->rollBack();
                    $error = 'Cancha no válida.';
                } elseif (hayCruce($db, $idCancha, $fecha, $ini, $fin, $id)) {
                    $db->rollBack();
                    $error = 'Ese horario ya está reservado.';
                } else {
                    $db->prepare('UPDATE reservas SET id_cancha=?, fecha=?, hora_inicio=?, hora_fin=?, total=? WHERE id=?')
                       ->execute([$idCancha, $fecha, $ini, $fin, $precio * $dur, $id]);
                    $db->commit();
                    flash('ok', 'Reserva modificada.');
                    redirigir('reservas.php');
                }
            }
        } catch (Throwable $ex) {
            if ($db->inTransaction()) { $db->rollBack(); }
            $error = 'Error al modificar la reserva.';
        }
    }
}
$stC = $db->prepare('SELECT id, nombre FROM canchas WHERE activa = 1 OR id = ? ORDER BY nombre');
$stC->execute([$r['id_cancha']]);
$canchas = $stC->fetchAll();
$canchaSel = (int)($_POST['cancha'] ?? $r['id_cancha']);
$horaAct = (int)substr($r['hora_inicio'], 0, 2);
$durAct = (int)substr($r['hora_fin'], 0, 2) - $horaAct;
$titulo = 'Modificar reserva';
require __DIR__ . '/includes/header.php';
?>
<h1>Modificar reserva #<?= (int)$id ?></h1>
<div class="tarjeta">
    <?php if ($error): ?><div class="alerta alerta-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="fila">
        <?= csrfCampo() ?><input type="hidden" name="id" value="<?= (int)$id ?>">
        <div class="campo"><label>Cancha</label><select name="cancha">
            <?php foreach ($canchas as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $canchaSel == $c['id'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option><?php endforeach; ?></select></div>
        <div class="campo"><label>Fecha</label><input type="date" name="fecha" min="<?= date('Y-m-d') ?>" value="<?= e($_POST['fecha'] ?? $r['fecha']) ?>" required></div>
        <div class="campo"><label>Hora</label><select name="hora">
            <?php for ($h = HORA_APERTURA; $h < HORA_CIERRE; $h++): ?><option value="<?= $h ?>" <?= (int)($_POST['hora'] ?? $horaAct) === $h ? 'selected' : '' ?>><?= sprintf('%02d', $h) ?>:00</option><?php endfor; ?></select></div>
        <div class="campo"><label>Duración</label><select name="duracion">
            <?php for ($i = 1; $i <= DURACION_MAXIMA; $i++): ?><option value="<?= $i ?>" <?= (int)($_POST['duracion'] ?? $durAct) === $i ? 'selected' : '' ?>><?= $i ?> h</option><?php endfor; ?></select></div>
        <button class="btn" type="submit">Guardar</button>
        <a class="btn btn-sec" href="reservas.php">Volver</a>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
