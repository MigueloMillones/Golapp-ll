<?php
/** dashboard.php - Panel de inicio (distinto para administrador y cliente). */
require_once __DIR__ . '/includes/auth.php';
requiereLogin();
$db = getDb();
$u = usuarioActual();
$titulo = 'Inicio';

if (esAdmin()) {
    $pend = $db->query("SELECT COUNT(*) FROM reservas WHERE estado = 'pendiente'")->fetchColumn();
    $hoy = $db->query("SELECT COUNT(*) FROM reservas WHERE fecha = CURDATE() AND estado IN ('pendiente','confirmada')")->fetchColumn();
    $canchas = $db->query('SELECT COUNT(*) FROM canchas WHERE activa = 1')->fetchColumn();
    $ingMes = $db->query("SELECT COALESCE(SUM(total),0) FROM reservas WHERE estado IN ('confirmada','finalizada')
                          AND fecha >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn();
} else {
    $st = $db->prepare("SELECT r.*, c.nombre AS cancha FROM reservas r JOIN canchas c ON c.id = r.id_cancha
                        WHERE r.id_usuario = ? AND r.fecha >= CURDATE() AND r.estado IN ('pendiente','confirmada')
                        ORDER BY r.fecha, r.hora_inicio LIMIT 5");
    $st->execute([$u['id']]);
    $proximas = $st->fetchAll();
}
require __DIR__ . '/includes/header.php';
?>
<h1>Hola, <?= e($u['nombre']) ?></h1>
<?php if (esAdmin()): ?>
    <div class="rejilla">
        <div class="stat"><b><?= (int)$pend ?></b><span>Reservas pendientes</span></div>
        <div class="stat"><b><?= (int)$hoy ?></b><span>Reservas de hoy</span></div>
        <div class="stat"><b><?= (int)$canchas ?></b><span>Canchas activas</span></div>
        <div class="stat"><b><?= moneda($ingMes) ?></b><span>Ingresos del mes</span></div>
    </div>
    <p><a class="btn" href="reservas.php?estado=pendiente">Ver pendientes</a>
       <a class="btn btn-sec" href="reporte.php">Ver reporte</a></p>
<?php else: ?>
    <h2>Tus próximas reservas</h2>
    <?php if (!$proximas): ?>
        <div class="tarjeta">Aún no tienes reservas próximas. <a href="calendario.php">Mira la disponibilidad</a>.</div>
    <?php else: ?>
        <div class="tabla-wrap"><table>
            <tr><th>Cancha</th><th>Fecha</th><th>Horario</th><th>Estado</th></tr>
            <?php foreach ($proximas as $r): ?>
                <tr><td><?= e($r['cancha']) ?></td><td><?= e($r['fecha']) ?></td>
                    <td><?= e(substr($r['hora_inicio'], 0, 5)) ?> - <?= e(substr($r['hora_fin'], 0, 5)) ?></td>
                    <td><?= insignia($r['estado']) ?></td></tr>
            <?php endforeach; ?>
        </table></div>
    <?php endif; ?>
    <p><a class="btn" href="nueva_reserva.php">Reservar una cancha</a></p>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
