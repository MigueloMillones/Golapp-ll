<?php
/** reporte.php - Reporte de gestión: reservas e ingresos por cancha en un periodo (visual + exportable a CSV). */
require_once __DIR__ . '/includes/auth.php';
requiereAdmin();
$db = getDb();

$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-t');
foreach (['desde', 'hasta'] as $k) {
    $d = DateTime::createFromFormat('Y-m-d', $$k);
    if (!$d || $d->format('Y-m-d') !== $$k) { $$k = ($k === 'desde') ? date('Y-m-01') : date('Y-m-t'); }
}
if ($desde > $hasta) { [$desde, $hasta] = [$hasta, $desde]; }

$st = $db->prepare("SELECT c.nombre,
        COUNT(r.id) AS total,
        COALESCE(SUM(r.estado IN ('confirmada','finalizada')), 0) AS efectivas,
        COALESCE(SUM(r.estado = 'cancelada'), 0) AS canceladas,
        COALESCE(SUM(CASE WHEN r.estado IN ('confirmada','finalizada') THEN r.total END), 0) AS ingresos
    FROM canchas c LEFT JOIN reservas r ON r.id_cancha = c.id AND r.fecha BETWEEN ? AND ?
    GROUP BY c.id, c.nombre ORDER BY ingresos DESC");
$st->execute([$desde, $hasta]);
$filas = $st->fetchAll();
$totRes = array_sum(array_column($filas, 'total'));
$totIng = array_sum(array_column($filas, 'ingresos'));
$maxIng = max(1, ...array_map('floatval', array_column($filas, 'ingresos') ?: [1]));
$titulo = 'Reporte';
require __DIR__ . '/includes/header.php';
?>
<h1>Reporte de gestión</h1>
<form method="get" class="fila tarjeta">
    <div class="campo"><label>Desde</label><input type="date" name="desde" value="<?= e($desde) ?>"></div>
    <div class="campo"><label>Hasta</label><input type="date" name="hasta" value="<?= e($hasta) ?>"></div>
    <button class="btn" type="submit">Generar</button>
    <a class="btn btn-sec" href="exportar_csv.php?desde=<?= e($desde) ?>&hasta=<?= e($hasta) ?>">Exportar CSV</a>
</form>
<div class="rejilla">
    <div class="stat"><b><?= (int)$totRes ?></b><span>Reservas en el periodo</span></div>
    <div class="stat"><b><?= moneda($totIng) ?></b><span>Ingresos (confirmadas y finalizadas)</span></div>
</div>
<h2>Ingresos por cancha</h2>
<div class="tarjeta">
    <?php foreach ($filas as $f): ?>
        <p style="margin:6px 0"><?= e($f['nombre']) ?> &mdash; <b><?= moneda($f['ingresos']) ?></b></p>
        <div class="barra-graf"><span style="width:<?= round($f['ingresos'] / $maxIng * 100) ?>%"></span></div>
    <?php endforeach; ?>
</div>
<div class="tabla-wrap"><table>
    <tr><th>Cancha</th><th>Reservas</th><th>Efectivas</th><th>Canceladas</th><th>Ingresos</th></tr>
    <?php foreach ($filas as $f): ?>
    <tr><td><?= e($f['nombre']) ?></td><td><?= (int)$f['total'] ?></td><td><?= (int)$f['efectivas'] ?></td>
        <td><?= (int)$f['canceladas'] ?></td><td><?= moneda($f['ingresos']) ?></td></tr>
    <?php endforeach; ?>
</table></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
