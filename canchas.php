<?php
/** canchas.php - CRUD de canchas con precio por hora (solo administrador). */
require_once __DIR__ . '/includes/auth.php';
requiereAdmin();
$db = getDb();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'guardar') {
        $id = (int)($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $idTipo = (int)($_POST['id_tipo'] ?? 0);
        $precio = (float)($_POST['precio_hora'] ?? 0);
        $activa = isset($_POST['activa']) ? 1 : 0;
        if ($nombre === '' || $idTipo <= 0 || $precio <= 0) {
            flash('error', 'Completa nombre, tipo y un precio mayor que cero.');
        } elseif ($id) {
            $db->prepare('UPDATE canchas SET nombre=?, id_tipo=?, precio_hora=?, activa=? WHERE id=?')->execute([$nombre, $idTipo, $precio, $activa, $id]);
            flash('ok', 'Cancha actualizada.');
        } else {
            $db->prepare('INSERT INTO canchas (nombre, id_tipo, precio_hora, activa) VALUES (?,?,?,?)')->execute([$nombre, $idTipo, $precio, $activa]);
            flash('ok', 'Cancha creada.');
        }
    } elseif ($accion === 'eliminar') {
        try {
            $db->prepare('DELETE FROM canchas WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
            flash('ok', 'Cancha eliminada.');
        } catch (PDOException $ex) {
            flash('error', 'No se puede eliminar: tiene reservas. Desactívala en su lugar.');
        }
    }
    redirigir('canchas.php');
}
$edit = [];
if (isset($_GET['editar'])) {
    $st = $db->prepare('SELECT * FROM canchas WHERE id = ?');
    $st->execute([(int)$_GET['editar']]);
    $edit = $st->fetch() ?: [];
}
$tipos = $db->query('SELECT id, nombre FROM tipos_cancha ORDER BY nombre')->fetchAll();
$canchas = $db->query('SELECT c.*, t.nombre AS tipo FROM canchas c JOIN tipos_cancha t ON t.id = c.id_tipo ORDER BY c.nombre')->fetchAll();
$titulo = 'Canchas';
require __DIR__ . '/includes/header.php';
?>
<h1>Canchas</h1>
<?php if (!$tipos): ?>
    <div class="alerta alerta-error">Primero crea un <a href="tipos.php">tipo de cancha</a>.</div>
<?php endif; ?>
<div class="tarjeta">
    <form method="post" class="fila">
        <?= csrfCampo() ?><input type="hidden" name="accion" value="guardar">
        <input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
        <div class="campo"><label>Nombre</label><input type="text" name="nombre" required maxlength="60" value="<?= e($edit['nombre'] ?? '') ?>"></div>
        <div class="campo"><label>Tipo</label><select name="id_tipo" required>
            <option value="">Selecciona...</option>
            <?php foreach ($tipos as $t): ?>
                <option value="<?= (int)$t['id'] ?>" <?= ($edit['id_tipo'] ?? 0) == $t['id'] ? 'selected' : '' ?>><?= e($t['nombre']) ?></option>
            <?php endforeach; ?></select></div>
        <div class="campo"><label>Precio por hora (COP)</label><input type="number" name="precio_hora" min="1000" step="500" required value="<?= e($edit['precio_hora'] ?? '') ?>"></div>
        <div class="campo"><label><input type="checkbox" name="activa" <?= ($edit['activa'] ?? 1) ? 'checked' : '' ?>> Activa</label></div>
        <button class="btn" type="submit"><?= $edit ? 'Actualizar' : 'Agregar' ?></button>
        <?php if ($edit): ?><a class="btn btn-sec" href="canchas.php">Cancelar</a><?php endif; ?>
    </form>
</div>
<div class="tabla-wrap"><table>
    <tr><th>Nombre</th><th>Tipo</th><th>Precio/hora</th><th>Estado</th><th>Acciones</th></tr>
    <?php foreach ($canchas as $c): ?>
    <tr><td><?= e($c['nombre']) ?></td><td><?= e($c['tipo']) ?></td><td><?= moneda($c['precio_hora']) ?></td>
        <td><?= $c['activa'] ? 'Activa' : 'Inactiva' ?></td>
        <td class="acciones"><a class="btn btn-peq" href="canchas.php?editar=<?= (int)$c['id'] ?>">Editar</a>
            <form method="post"><?= csrfCampo() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <button class="btn btn-peq btn-rojo" data-confirm="¿Eliminar esta cancha?">Eliminar</button></form></td></tr>
    <?php endforeach; ?>
</table></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
