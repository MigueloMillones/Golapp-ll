<?php
/**
 * db.php - Conexión PDO a MySQL (patrón singleton).
 * Usa consultas preparadas en todo el sistema para evitar inyección SQL.
 */
require_once __DIR__ . '/config.php';

function getDb(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            $pdo->exec("SET time_zone = '-05:00'");
        } catch (PDOException $ex) {
            http_response_code(500);
            exit('No se pudo conectar a la base de datos. Revisa includes/config.php');
        }
    }
    return $pdo;
}
