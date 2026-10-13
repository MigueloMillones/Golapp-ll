-- carga_10000_reservas.sql - Genera 10.000 reservas de prueba para medir el rendimiento (CP-27).
-- Importar DESPUES de golapp.sql. Para limpiar: DELETE FROM reservas WHERE id_usuario = 2 AND creado_en < '2000-01-01';
USE golapp;
INSERT INTO reservas (id_usuario, id_cancha, fecha, hora_inicio, hora_fin, estado, total, creado_en)
SELECT 2,
       1 + (n.i % 3),
       DATE_ADD('2026-01-01', INTERVAL (n.i DIV 3) % 365 DAY),
       MAKETIME(6 + (n.i % 15), 0, 0),
       MAKETIME(7 + (n.i % 15), 0, 0),
       ELT(1 + (n.i % 4), 'pendiente', 'confirmada', 'cancelada', 'finalizada'),
       60000,
       '1999-01-01 00:00:00'
FROM (SELECT a.d + b.d * 10 + c.d * 100 + e.d * 1000 AS i
      FROM (SELECT 0 d UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
           (SELECT 0 d UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b,
           (SELECT 0 d UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) c,
           (SELECT 0 d UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) e) n;
