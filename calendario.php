<?php
/** calendario.php - Disponibilidad de canchas por día (cuadrícula cancha x hora). */
require_once __DIR__ . '/includes/auth.php';
requiereLogin();
$db = getDb();

$fecha = $_GET['fecha'] ?? date('Y-m-d');
$d = DateTime::createFromFormat('Y-m-d', $fecha);
if (!$d || $d->format('Y-m-d') !== $fecha) { $fecha = date('Y-m-d'); }

$canchas = $db->query('SELECT id, nombre FROM canchas WHERE activa = 1 ORDER BY nombre')->fetchAll();
$st = $db->prepare("SELECT id_cancha, hora_inicio, hora_fin, estado FROM reservas
                    WHERE fecha = ? AND estado IN ('pendiente','confirmada')");
$st->execute([$fecha]);
// Mapa [cancha][hora] = estado
$ocupado = [];
foreach ($st->fetchAll() as $r) {
    for ($h = (int)substr($r['hora_inicio'], 0, 2); $h < (int)substr($r['hora_fin'], 0, 2); $h++) {
        $ocupado[$r['id_cancha']][$h] = $r['estado'];
    }
}
$ayer = date('Y-m-d', strtotime($fecha . ' -1 day'));
$manana = date('Y-m-d', strtotime($fecha . ' +1 day'));
$titulo = 'Calendario';
require __DIR__ . '/includes/header.php';
?>
<h1>Disponibilidad</h1>
<form method="get" class="fila tarjeta">
    <div class="campo"><label>Fecha</label><input type="date" name="fecha" value="<?= e($fecha) ?>" min="<?= date('Y-m-d') ?>"></div>
    <button class="btn" type="submit">Ver</button>
    <a class="btn btn-sec" href="?fecha=<?= e($ayer) ?>">&laquo; Anterior</a>
    <a class="btn btn-sec" href="?fecha=<?= e($manana) ?>">Siguiente &raquo;</a>
</form>
<div class="leyenda"><span>&#9632; Libre (clic para reservar)</span><span style="color:#b58900">&#9632; Pendiente</span><span style="color:#c0392b">&#9632; Reservada</span></div>
<div class="tabla-wrap"><table class="cal">
    <tr><th>Cancha</th><?php for ($h = HORA_APERTURA; $h < HORA_CIERRE; $h++): ?><th><?= sprintf('%02d', $h) ?>:00</th><?php endfor; ?></tr>
    <?php foreach ($canchas as $c): ?>
    <tr><th><?= e($c['nombre']) ?></th>
        <?php for ($h = HORA_APERTURA; $h < HORA_CIERRE; $h++):
            $estado = $ocupado[$c['id']][$h] ?? null;
            $pasado = $fecha < date('Y-m-d') || ($fecha === date('Y-m-d') && $h <= (int)date('G'));
            if ($estado): ?><td class="<?= e($estado) ?>"><?= $estado === 'pendiente' ? 'Pend.' : 'Reservada' ?></td>
            <?php elseif ($pasado): ?><td>&ndash;</td>
            <?php else: ?><td class="libre"><a href="nueva_reserva.php?cancha=<?= (int)$c['id'] ?>&fecha=<?= e($fecha) ?>&hora=<?= $h ?>">Libre</a></td>
        <?php endif; endfor; ?></tr>
    <?php endforeach; ?>
</table></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
