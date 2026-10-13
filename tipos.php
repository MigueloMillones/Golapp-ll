<?php
/** tipos.php - CRUD de tipos de cancha (solo administrador). */
require_once __DIR__ . '/includes/auth.php';
requiereAdmin();
$db = getDb();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'guardar') {
        $id = (int)($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $desc = trim($_POST['descripcion'] ?? '');
        if ($nombre === '') {
            flash('error', 'El nombre es obligatorio.');
        } elseif ($id) {
            $db->prepare('UPDATE tipos_cancha SET nombre = ?, descripcion = ? WHERE id = ?')->execute([$nombre, $desc, $id]);
            flash('ok', 'Tipo actualizado.');
        } else {
            $db->prepare('INSERT INTO tipos_cancha (nombre, descripcion) VALUES (?,?)')->execute([$nombre, $desc]);
            flash('ok', 'Tipo creado.');
        }
    } elseif ($accion === 'eliminar') {
        try {
            $db->prepare('DELETE FROM tipos_cancha WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
            flash('ok', 'Tipo eliminado.');
        } catch (PDOException $ex) {
            flash('error', 'No se puede eliminar: hay canchas de este tipo.');
        }
    }
    redirigir('tipos.php');
}
$edit = [];
if (isset($_GET['editar'])) {
    $st = $db->prepare('SELECT * FROM tipos_cancha WHERE id = ?');
    $st->execute([(int)$_GET['editar']]);
    $edit = $st->fetch() ?: [];
}
$tipos = $db->query('SELECT * FROM tipos_cancha ORDER BY nombre')->fetchAll();
$titulo = 'Tipos de cancha';
require __DIR__ . '/includes/header.php';
?>
<h1>Tipos de cancha</h1>
<div class="tarjeta">
    <form method="post" class="fila">
        <?= csrfCampo() ?><input type="hidden" name="accion" value="guardar">
        <input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
        <div class="campo"><label>Nombre</label><input type="text" name="nombre" required maxlength="60" value="<?= e($edit['nombre'] ?? '') ?>"></div>
        <div class="campo"><label>Descripción</label><input type="text" name="descripcion" maxlength="200" value="<?= e($edit['descripcion'] ?? '') ?>"></div>
        <button class="btn" type="submit"><?= $edit ? 'Actualizar' : 'Agregar' ?></button>
        <?php if ($edit): ?><a class="btn btn-sec" href="tipos.php">Cancelar</a><?php endif; ?>
    </form>
</div>
<div class="tabla-wrap"><table>
    <tr><th>Nombre</th><th>Descripción</th><th>Acciones</th></tr>
    <?php foreach ($tipos as $t): ?>
    <tr><td><?= e($t['nombre']) ?></td><td><?= e($t['descripcion']) ?></td>
        <td class="acciones"><a class="btn btn-peq" href="tipos.php?editar=<?= (int)$t['id'] ?>">Editar</a>
            <form method="post"><?= csrfCampo() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
            <button class="btn btn-peq btn-rojo" data-confirm="¿Eliminar este tipo?">Eliminar</button></form></td></tr>
    <?php endforeach; ?>
</table></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
