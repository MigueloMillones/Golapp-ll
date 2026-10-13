<?php
/**
 * config.php - Configuración general de GolApp.
 * Ajusta estos valores según tu entorno (XAMPP/MAMP).
 */
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'golapp');
define('DB_USER', 'root');
define('DB_PASS', '');          // XAMPP: vacío. MAMP: 'root'.

define('HORA_APERTURA', 6);     // Primera hora reservable (06:00)
define('HORA_CIERRE', 22);      // Hora de cierre (22:00)
define('DURACION_MAXIMA', 3);   // Horas máximas por reserva

date_default_timezone_set('America/Bogota');
