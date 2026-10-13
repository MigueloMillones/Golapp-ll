<?php
/** asignar_implementos.php - Asocia implementos (balones, petos...) a una reserva validando el stock disponible. */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/reservas_lib.php';
requiereAdmin();
$db = getDb();
$id = (int)($_GET['reserva'] ?? $_POST['reserva'] ?? 0);
$st = $db->prepare('SELECT r.*, c.nombre AS cancha FROM reservas r JOIN canchas c ON c.id = r.id_cancha WHERE r.id = ?');
$st->execute([$id]);
$r = $st->fetch();
if (!$r) { flash('error', 'La reserva no existe.'); redirigir('reservas.php'); }
if (!in_array($r['estado'], ['pendiente', 'confirmada'], true)) {
    flash('error', 'Solo se asignan implementos a reservas pendientes o confirmadas.');
    redirigir('reservas.php');
}

$productos = $db->query('SELECT * FROM productos ORDER BY nombre')->fetchAll();
$act = $db->prepare('SELECT id_producto, cantidad, devuelto FROM prestamos WHERE id_reserva = ?');
$act->execute([$id]);
$actuales = [];
foreach ($act->fetchAll() as $a) { $actuales[$a['id_producto']] = $a; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    $cant = $_POST['cant'] ?? [];
    $dev = $_POST['dev'] ?? [];
    try {
        $db->beginTransaction();
        $lock = $db->prepare('SELECT estado FROM reservas WHERE id = ? FOR UPDATE');
        $lock->execute([$id]);
        $est = $lock->fetchColumn();
        if (!in_array($est, ['pendiente', 'confirmada'], true)) {
            $db->rollBack();
            $error = 'La reserva ya no admite implementos.';
        } else {
            $nuevos = [];
            $lockProd = $db->prepare('SELECT id FROM productos WHERE id = ? FOR UPDATE');
            foreach ($productos as $p) {
                $c = max(0, (int)($cant[$p['id']] ?? 0));
                if ($c <= 0) { continue; }
                $lockProd->execute([$p['id']]);
                $disp = stockDisponible($db, (int)$p['id'], $r['fecha'], $r['hora_inicio'], $r['hora_fin'], $id);
                if ($c > $disp) {
                    $db->rollBack();
                    $error = 'Solo hay ' . $disp . ' de "' . $p['nombre'] . '" disponibles en ese horario.';
                    $nuevos = [];
                    break;
                }
                $nuevos[] = [$p['id'], $c, isset($dev[$p['id']]) ? 1 : 0];
            }
            if (!$error) {
                $db->prepare('DELETE FROM prestamos WHERE id_reserva = ?')->execute([$id]);
                $ins = $db->prepare('INSERT INTO prestamos (id_reserva, id_producto, cantidad, devuelto) VALUES (?,?,?,?)');
                foreach ($nuevos as $n) { $ins->execute([$id, $n[0], $n[1], $n[2]]); }
                $db->commit();
                flash('ok', 'Implementos asignados a la reserva.');
                redirigir('reservas.php');
            }
        }
    } catch (Throwable $ex) {
        if ($db->inTransaction()) { $db->rollBack(); }
        $error = 'Error al guardar los implementos.';
    }
}
$titulo = 'Implementos de la reserva';
require __DIR__ . '/includes/header.php';
?>
<h1>Implementos - reserva #<?= (int)$id ?></h1>
<p><?= e($r['cancha']) ?> &middot; <?= e($r['fecha']) ?> &middot; <?= e(substr($r['hora_inicio'], 0, 5)) ?> - <?= e(substr($r['hora_fin'], 0, 5)) ?></p>
<?php if ($error): ?><div class="alerta alerta-error"><?= e($error) ?></div><?php endif; ?>
<form method="post">
    <?= csrfCampo() ?><input type="hidden" name="reserva" value="<?= (int)$id ?>">
    <div class="tabla-wrap"><table>
        <tr><th>Implemento</th><th>Disponible en este horario</th><th>Cantidad</th><th>Devuelto</th></tr>
        <?php foreach ($productos as $p):
            $disp = stockDisponible($db, (int)$p['id'], $r['fecha'], $r['hora_inicio'], $r['hora_fin'], $id); ?>
        <tr><td><?= e($p['nombre']) ?></td><td><?= $disp ?></td>
            <td><input type="number" min="0" max="<?= $disp ?>" name="cant[<?= (int)$p['id'] ?>]" value="<?= (int)($actuales[$p['id']]['cantidad'] ?? 0) ?>"></td>
            <td><input type="checkbox" name="dev[<?= (int)$p['id'] ?>]" <?= !empty($actuales[$p['id']]['devuelto']) ? 'checked' : '' ?>></td></tr>
        <?php endforeach; ?>
    </table></div>
    <p><button class="btn" type="submit">Guardar</button> <a class="btn btn-sec" href="reservas.php">Volver</a></p>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
