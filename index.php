<?php
// index.php - Punto de entrada: redirige según el estado de la sesión.
require_once __DIR__ . '/includes/auth.php';
redirigir(usuarioActual() ? 'dashboard.php' : 'login.php');
