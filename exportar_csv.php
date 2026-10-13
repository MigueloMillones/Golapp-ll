<?php
/** exportar_csv.php - Descarga el detalle de reservas del periodo en CSV (compatible con Excel). */
require_once __DIR__ . '/includes/auth.php';
requiereAdmin();
$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-t');
foreach (['desde', 'hasta'] as $k) {
    $d = DateTime::createFromFormat('Y-m-d', $$k);
    if (!$d || $d->format('Y-m-d') !== $$k) { exit('Fechas inválidas.'); }
}
if ($desde > $hasta) { [$desde, $hasta] = [$hasta, $desde]; }
$st = getDb()->prepare("SELECT r.id, r.fecha, r.hora_inicio, r.hora_fin, c.nombre AS cancha, u.nombre AS cliente, r.estado, r.total
    FROM reservas r JOIN canchas c ON c.id = r.id_cancha JOIN usuarios u ON u.id = r.id_usuario
    WHERE r.fecha BETWEEN ? AND ? ORDER BY r.fecha, r.hora_inicio");
$st->execute([$desde, $hasta]);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="reporte_reservas_' . $desde . '_' . $hasta . '.csv"');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");   // BOM para que Excel lea bien las tildes
fputcsv($out, ['ID', 'Fecha', 'Hora inicio', 'Hora fin', 'Cancha', 'Cliente', 'Estado', 'Total']);
foreach ($st->fetchAll() as $r) { fputcsv($out, $r); }
fclose($out);
