<?php
/** inventario.php - Gestión de implementos y stock con disponibilidad en tiempo real (solo administrador). */
require_once __DIR__ . '/includes/auth.php';
requiereAdmin();
$db = getDb();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'guardar') {
        $id = (int)($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $stock = (int)($_POST['stock_total'] ?? -1);
        if ($nombre === '' || $stock < 0) {
            flash('error', 'Ingresa un nombre y un stock válido (0 o más).');
        } elseif ($id) {
            $db->prepare('UPDATE productos SET nombre=?, stock_total=? WHERE id=?')->execute([$nombre, $stock, $id]);
            flash('ok', 'Implemento actualizado.');
        } else {
            $db->prepare('INSERT INTO productos (nombre, stock_total) VALUES (?,?)')->execute([$nombre, $stock]);
            flash('ok', 'Implemento agregado.');
        }
    } elseif ($accion === 'eliminar') {
        try {
            $db->prepare('DELETE FROM productos WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
            flash('ok', 'Implemento eliminado.');
        } catch (PDOException $ex) {
            flash('error', 'No se puede eliminar: está asociado a reservas.');
        }
    }
    redirigir('inventario.php');
}
$edit = [];
if (isset($_GET['editar'])) {
    $st = $db->prepare('SELECT * FROM productos WHERE id = ?');
    $st->execute([(int)$_GET['editar']]);
    $edit = $st->fetch() ?: [];
}
// "En uso ahora": implementos de reservas confirmadas que están ocurriendo en este momento.
$productos = $db->query("SELECT p.*, COALESCE((
        SELECT SUM(pr.cantidad) FROM prestamos pr JOIN reservas r ON r.id = pr.id_reserva
        WHERE pr.id_producto = p.id AND pr.devuelto = 0 AND r.estado = 'confirmada'
          AND r.fecha = CURDATE() AND CURTIME() >= r.hora_inicio AND CURTIME() < r.hora_fin), 0) AS en_uso
    FROM productos p ORDER BY p.nombre")->fetchAll();
$titulo = 'Inventario';
require __DIR__ . '/includes/header.php';
?>
<h1>Inventario de implementos</h1>
<div class="tarjeta">
    <form method="post" class="fila">
        <?= csrfCampo() ?><input type="hidden" name="accion" value="guardar">
        <input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
        <div class="campo"><label>Implemento</label><input type="text" name="nombre" required maxlength="80" value="<?= e($edit['nombre'] ?? '') ?>"></div>
        <div class="campo"><label>Stock total</label><input type="number" name="stock_total" min="0" required value="<?= e($edit['stock_total'] ?? '') ?>"></div>
        <button class="btn" type="submit"><?= $edit ? 'Actualizar' : 'Agregar' ?></button>
        <?php if ($edit): ?><a class="btn btn-sec" href="inventario.php">Cancelar</a><?php endif; ?>
    </form>
</div>
<div class="tabla-wrap"><table>
    <tr><th>Implemento</th><th>Stock total</th><th>En uso ahora</th><th>Disponible ahora</th><th>Acciones</th></tr>
    <?php foreach ($productos as $p): ?>
    <tr><td><?= e($p['nombre']) ?></td><td><?= (int)$p['stock_total'] ?></td><td><?= (int)$p['en_uso'] ?></td>
        <td><?= max(0, (int)$p['stock_total'] - (int)$p['en_uso']) ?></td>
        <td class="acciones"><a class="btn btn-peq" href="inventario.php?editar=<?= (int)$p['id'] ?>">Editar</a>
            <form method="post"><?= csrfCampo() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
            <button class="btn btn-peq btn-rojo" data-confirm="¿Eliminar este implemento?">Eliminar</button></form></td></tr>
    <?php endforeach; ?>
</table></div>
<p><small class="ayuda">Para asignar implementos a una reserva, entra a <a href="reservas.php">Reservas</a> y pulsa "Implementos".</small></p>
<?php require __DIR__ . '/includes/footer.php'; ?>
