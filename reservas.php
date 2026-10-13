<?php
/** reservas.php - Lista de reservas. Admin: todas y gestiona estados. Cliente: solo las suyas. */
require_once __DIR__ . '/includes/auth.php';
requiereLogin();
$db = getDb();
$u = usuarioActual();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    $id = (int)($_POST['id'] ?? 0);
    $accion = $_POST['accion'] ?? '';
    $mapa = ['confirmar' => 'confirmada', 'cancelar' => 'cancelada', 'finalizar' => 'finalizada'];
    if (isset($mapa[$accion])) {
        if (esAdmin()) {
            $permitido = ['confirmar' => ['pendiente'], 'cancelar' => ['pendiente', 'confirmada'], 'finalizar' => ['confirmada']];
            $okEstados = $permitido[$accion];
            $in = implode(',', array_fill(0, count($okEstados), '?'));
            $up = $db->prepare("UPDATE reservas SET estado = ? WHERE id = ? AND estado IN ($in)");
            $up->execute(array_merge([$mapa[$accion], $id], $okEstados));
            flash($up->rowCount() ? 'ok' : 'error', $up->rowCount() ? 'Reserva actualizada.' : 'No se pudo actualizar esa reserva.');
        } elseif ($accion === 'cancelar') {   // El cliente solo cancela sus reservas pendientes
            $n = $db->prepare("UPDATE reservas SET estado = 'cancelada' WHERE id = ? AND id_usuario = ? AND estado = 'pendiente'");
            $n->execute([$id, $u['id']]);
            flash($n->rowCount() ? 'ok' : 'error', $n->rowCount() ? 'Reserva cancelada.' : 'No se pudo cancelar esa reserva.');
        }
    }
    redirigir('reservas.php' . (isset($_GET['estado']) ? '?estado=' . urlencode($_GET['estado']) : ''));
}
$filtroEstado = $_GET['estado'] ?? '';
$sql = "SELECT r.*, c.nombre AS cancha, us.nombre AS cliente
        FROM reservas r JOIN canchas c ON c.id = r.id_cancha JOIN usuarios us ON us.id = r.id_usuario WHERE 1=1";
$params = [];
if (!esAdmin()) { $sql .= ' AND r.id_usuario = ?'; $params[] = $u['id']; }
if (in_array($filtroEstado, ['pendiente', 'confirmada', 'cancelada', 'finalizada'], true)) { $sql .= ' AND r.estado = ?'; $params[] = $filtroEstado; }
$sql .= ' ORDER BY r.fecha DESC, r.hora_inicio DESC LIMIT 200';
$st = $db->prepare($sql);
$st->execute($params);
$reservas = $st->fetchAll();
$titulo = esAdmin() ? 'Reservas' : 'Mis reservas';
require __DIR__ . '/includes/header.php';

function botonAccion(string $accion, int $id, string $texto, string $clase = '', string $confirmar = ''): string
{
    return '<form method="post">' . csrfCampo() . '<input type="hidden" name="accion" value="' . $accion . '"><input type="hidden" name="id" value="' . $id . '">'
         . '<button class="btn btn-peq ' . $clase . '"' . ($confirmar ? ' data-confirm="' . $confirmar . '"' : '') . '>' . $texto . '</button></form>';
}
?>
<h1><?= e($titulo) ?></h1>
<form method="get" class="fila tarjeta">
    <div class="campo"><label>Estado</label><select name="estado">
        <option value="">Todos</option>
        <?php foreach (['pendiente', 'confirmada', 'cancelada', 'finalizada'] as $s): ?>
            <option value="<?= $s ?>" <?= $filtroEstado === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?></select></div>
    <button class="btn" type="submit">Filtrar</button>
    <a class="btn btn-sec" href="nueva_reserva.php">+ Nueva reserva</a>
</form>
<div class="tabla-wrap"><table>
    <tr><th>#</th><?php if (esAdmin()): ?><th>Cliente</th><?php endif; ?><th>Cancha</th><th>Fecha</th><th>Horario</th><th>Total</th><th>Estado</th><th>Acciones</th></tr>
    <?php if (!$reservas): ?><tr><td colspan="<?= esAdmin() ? 8 : 7 ?>">No hay reservas.</td></tr><?php endif; ?>
    <?php foreach ($reservas as $r): ?>
    <tr><td><?= (int)$r['id'] ?></td><?php if (esAdmin()): ?><td><?= e($r['cliente']) ?></td><?php endif; ?>
        <td><?= e($r['cancha']) ?></td><td><?= e($r['fecha']) ?></td>
        <td><?= e(substr($r['hora_inicio'], 0, 5)) ?> - <?= e(substr($r['hora_fin'], 0, 5)) ?></td>
        <td><?= moneda($r['total']) ?></td><td><?= insignia($r['estado']) ?></td>
        <td class="acciones">
        <?php if (esAdmin()): ?>
            <?php if ($r['estado'] === 'pendiente') echo botonAccion('confirmar', $r['id'], 'Confirmar'); ?>
            <?php if (in_array($r['estado'], ['pendiente', 'confirmada'], true)): ?>
                <a class="btn btn-peq btn-sec" href="editar_reserva.php?id=<?= (int)$r['id'] ?>">Modificar</a>
                <a class="btn btn-peq btn-sec" href="asignar_implementos.php?reserva=<?= (int)$r['id'] ?>">Implementos</a>
                <?php echo botonAccion('cancelar', $r['id'], 'Cancelar', 'btn-rojo', '¿Cancelar esta reserva?'); ?>
            <?php endif; ?>
            <?php if ($r['estado'] === 'confirmada') echo botonAccion('finalizar', $r['id'], 'Finalizar'); ?>
        <?php elseif ($r['estado'] === 'pendiente'): echo botonAccion('cancelar', $r['id'], 'Cancelar', 'btn-rojo', '¿Cancelar tu reserva?'); endif; ?>
        </td></tr>
    <?php endforeach; ?>
</table></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
