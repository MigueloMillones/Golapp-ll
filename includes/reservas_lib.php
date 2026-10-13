<?php
/**
 * reservas_lib.php - Lógica de negocio de reservas e inventario.
 */

/** Indica si ya existe una reserva activa que se cruce con el horario. */
function hayCruce(PDO $db, int $idCancha, string $fecha, string $ini, string $fin, ?int $excluirId = null): bool
{
    $sql = "SELECT COUNT(*) FROM reservas
            WHERE id_cancha = ? AND fecha = ? AND estado IN ('pendiente','confirmada')
              AND hora_inicio < ? AND hora_fin > ?";
    $params = [$idCancha, $fecha, $fin, $ini];
    if ($excluirId) {
        $sql .= ' AND id <> ?';
        $params[] = $excluirId;
    }
    $st = $db->prepare($sql);
    $st->execute($params);
    return (int)$st->fetchColumn() > 0;
}

/** Valida fecha, hora y duración. Devuelve mensaje de error o null si es válido. */
function validarHorario(string $fecha, int $hora, int $duracion): ?string
{
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    if (!$d || $d->format('Y-m-d') !== $fecha) {
        return 'La fecha no es válida.';
    }
    if ($fecha < date('Y-m-d')) {
        return 'No se pueden reservar fechas pasadas.';
    }
    if ($hora < HORA_APERTURA || $hora >= HORA_CIERRE) {
        return 'La hora debe estar entre ' . HORA_APERTURA . ':00 y ' . HORA_CIERRE . ':00.';
    }
    if ($duracion < 1 || $duracion > DURACION_MAXIMA) {
        return 'La duración debe ser de 1 a ' . DURACION_MAXIMA . ' horas.';
    }
    if ($hora + $duracion > HORA_CIERRE) {
        return 'La reserva excede la hora de cierre (' . HORA_CIERRE . ':00).';
    }
    if ($fecha === date('Y-m-d') && $hora <= (int)date('G')) {
        return 'Esa hora ya pasó.';
    }
    return null;
}

/**
 * Crea una reserva dentro de una transacción. Bloquea la fila de la cancha
 * para que dos usuarios no puedan reservar el mismo horario a la vez.
 * @return array [bool exito, string mensaje]
 */
function crearReserva(PDO $db, int $idUsuario, int $idCancha, string $fecha, int $hora, int $duracion, string $estado): array
{
    $error = validarHorario($fecha, $hora, $duracion);
    if ($error) {
        return [false, $error];
    }
    if (!in_array($estado, ['pendiente', 'confirmada'], true)) {
        return [false, 'El estado de la reserva no es válido.'];
    }
    $ini = horaSql($hora);
    $fin = horaSql($hora + $duracion);

    try {
        $db->beginTransaction();
        $st = $db->prepare('SELECT precio_hora FROM canchas WHERE id = ? AND activa = 1 FOR UPDATE');
        $st->execute([$idCancha]);
        $precio = $st->fetchColumn();
        if ($precio === false) {
            $db->rollBack();
            return [false, 'La cancha no existe o está inactiva.'];
        }
        if (hayCruce($db, $idCancha, $fecha, $ini, $fin)) {
            $db->rollBack();
            return [false, 'Ese horario ya está reservado. Elige otro.'];
        }
        $ins = $db->prepare('INSERT INTO reservas (id_usuario, id_cancha, fecha, hora_inicio, hora_fin, estado, total)
                             VALUES (?,?,?,?,?,?,?)');
        $ins->execute([$idUsuario, $idCancha, $fecha, $ini, $fin, $estado, $precio * $duracion]);
        $db->commit();
        return [true, 'Reserva creada correctamente.'];
    } catch (Throwable $ex) {
        if ($db->inTransaction()) { $db->rollBack(); }
        return [false, 'Error al crear la reserva.'];
    }
}

/** Unidades de un producto disponibles en una franja (stock - lo ya asignado). */
function stockDisponible(PDO $db, int $idProducto, string $fecha, string $ini, string $fin, ?int $excluirReserva = null): int
{
    $sql = "SELECT COALESCE(SUM(pr.cantidad),0)
            FROM prestamos pr JOIN reservas r ON r.id = pr.id_reserva
            WHERE pr.id_producto = ? AND pr.devuelto = 0
              AND r.estado IN ('pendiente','confirmada') AND r.fecha = ?
              AND r.hora_inicio < ? AND r.hora_fin > ?";
    $params = [$idProducto, $fecha, $fin, $ini];
    if ($excluirReserva) {
        $sql .= ' AND r.id <> ?';
        $params[] = $excluirReserva;
    }
    $usado = $db->prepare($sql);
    $usado->execute($params);
    $total = $db->prepare('SELECT stock_total FROM productos WHERE id = ?');
    $total->execute([$idProducto]);
    return max(0, (int)$total->fetchColumn() - (int)$usado->fetchColumn());
}
