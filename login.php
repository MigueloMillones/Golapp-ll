<?php
/** login.php - Inicio de sesión con password_verify y bloqueo tras intentos fallidos. */
require_once __DIR__ . '/includes/auth.php';
if (usuarioActual()) { redirigir('dashboard.php'); }

$error = '';
$correo = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    $correo = strtolower(trim($_POST['correo'] ?? ''));
    $pass = $_POST['password'] ?? '';

    if (($_SESSION['bloqueo_hasta'] ?? 0) > time()) {
        $error = 'Demasiados intentos. Espera un minuto.';
    } else {
        $st = getDb()->prepare('SELECT id, nombre, contrasena, rol FROM usuarios WHERE correo = ?');
        $st->execute([$correo]);
        $u = $st->fetch();
        if ($u && password_verify($pass, $u['contrasena'])) {
            session_regenerate_id(true);
            $_SESSION['usuario'] = ['id' => (int)$u['id'], 'nombre' => $u['nombre'], 'rol' => $u['rol']];
            unset($_SESSION['intentos'], $_SESSION['bloqueo_hasta']);
            redirigir('dashboard.php');
        }
        $_SESSION['intentos'] = ($_SESSION['intentos'] ?? 0) + 1;
        if ($_SESSION['intentos'] >= 5) {
            $_SESSION['bloqueo_hasta'] = time() + 60;
            $_SESSION['intentos'] = 0;
        }
        $error = 'Correo o contraseña incorrectos.';
    }
}
$titulo = 'Iniciar sesión';
require __DIR__ . '/includes/header.php';
?>
<div class="auth tarjeta">
    <h1>Iniciar sesión</h1>
    <?php if ($error): ?><div class="alerta alerta-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" autocomplete="on">
        <?= csrfCampo() ?>
        <p><label for="correo">Correo</label><input type="email" id="correo" name="correo" value="<?= e($correo) ?>" required></p>
        <p><label for="password">Contraseña</label><input type="password" id="password" name="password" required></p>
        <button class="btn" type="submit">Entrar</button>
        <a href="registro.php">Crear cuenta</a>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
