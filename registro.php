<?php
/** registro.php - Registro de clientes. La contraseña se guarda con password_hash (bcrypt). */
require_once __DIR__ . '/includes/auth.php';
if (usuarioActual()) { redirigir('dashboard.php'); }

$error = '';
$datos = ['nombre' => '', 'correo' => '', 'telefono' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    $datos['nombre'] = trim($_POST['nombre'] ?? '');
    $datos['correo'] = strtolower(trim($_POST['correo'] ?? ''));
    $datos['telefono'] = trim($_POST['telefono'] ?? '');
    $pass = $_POST['password'] ?? '';

    if ($datos['nombre'] === '' || strlen($datos["nombre"]) > 100) {
        $error = 'Ingresa tu nombre (máximo 100 caracteres).';
    } elseif (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo no es válido.';
    } elseif (!preg_match('/^[0-9+ ]{7,20}$/', $datos['telefono'])) {
        $error = 'El teléfono no es válido.';
    } elseif (strlen($pass) < 8 || !preg_match('/[A-Za-z]/', $pass) || !preg_match('/[0-9]/', $pass)) {
        $error = 'La contraseña debe tener mínimo 8 caracteres, con letras y números.';
    } elseif ($pass !== ($_POST['password2'] ?? '')) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $db = getDb();
        $st = $db->prepare('SELECT COUNT(*) FROM usuarios WHERE correo = ?');
        $st->execute([$datos['correo']]);
        if ($st->fetchColumn() > 0) {
            $error = 'Ese correo ya está registrado.';
        } else {
            $ins = $db->prepare("INSERT INTO usuarios (nombre, correo, contrasena, telefono, rol) VALUES (?,?,?,?, 'cliente')");
            $ins->execute([$datos['nombre'], $datos['correo'], password_hash($pass, PASSWORD_DEFAULT), $datos['telefono']]);
            flash('ok', 'Cuenta creada. Ya puedes iniciar sesión.');
            redirigir('login.php');
        }
    }
}
$titulo = 'Registro';
require __DIR__ . '/includes/header.php';
?>
<div class="auth tarjeta">
    <h1>Crear cuenta</h1>
    <?php if ($error): ?><div class="alerta alerta-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" id="formRegistro">
        <?= csrfCampo() ?>
        <p><label>Nombre completo</label><input type="text" name="nombre" value="<?= e($datos['nombre']) ?>" required maxlength="100"></p>
        <p><label>Correo</label><input type="email" name="correo" value="<?= e($datos['correo']) ?>" required></p>
        <p><label>Teléfono</label><input type="text" name="telefono" value="<?= e($datos['telefono']) ?>" required></p>
        <p><label>Contraseña</label><input type="password" id="password" name="password" required>
           <small class="ayuda">Mínimo 8 caracteres, con letras y números.</small></p>
        <p><label>Confirmar contraseña</label><input type="password" id="password2" name="password2" required></p>
        <button class="btn" type="submit">Registrarme</button>
        <a href="login.php">Ya tengo cuenta</a>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
