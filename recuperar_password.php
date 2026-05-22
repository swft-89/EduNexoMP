<?php
session_start();

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
$devCode = $_SESSION['password_reset_dev_code'] ?? null;
$resetEmail = $_SESSION['password_reset_email'] ?? '';
unset($_SESSION['success'], $_SESSION['error'], $_SESSION['password_reset_dev_code']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña | EduNexo MP</title>
    <link rel="stylesheet" href="assets/css/style.css?v=recovery-1">
    <link rel="stylesheet" href="assets/css/dark.css?v=recovery-1">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
<main class="recovery-page">
    <section class="recovery-card">
        <a class="recovery-back" href="index.php">
            <i class="bi bi-arrow-left"></i>
            Volver al inicio
        </a>

        <div class="auth-icon">
            <i class="bi bi-shield-lock"></i>
        </div>

        <h1>Recuperar contraseña</h1>
        <p>Ingresa tu correo y te enviaremos un código temporal para crear una nueva contraseña.</p>

        <form class="auth-form" method="POST" action="procesos/solicitar_recuperacion.php">
            <div class="auth-group">
                <label for="correo">Correo electrónico</label>
                <input
                    type="email"
                    id="correo"
                    name="correo"
                    value="<?php echo htmlspecialchars($resetEmail); ?>"
                    placeholder="tu@email.com"
                    required
                >
            </div>

            <button type="submit" class="auth-submit">Enviar código</button>
        </form>

        <div class="recovery-divider"></div>

        <h2>Ya tengo un código</h2>
        <form class="auth-form" method="POST" action="procesos/restablecer_password.php">
            <div class="auth-group">
                <label for="reset_correo">Correo electrónico</label>
                <input
                    type="email"
                    id="reset_correo"
                    name="correo"
                    value="<?php echo htmlspecialchars($resetEmail); ?>"
                    placeholder="tu@email.com"
                    required
                >
            </div>

            <div class="auth-group">
                <label for="codigo">Código</label>
                <input
                    type="text"
                    id="codigo"
                    name="codigo"
                    inputmode="numeric"
                    maxlength="6"
                    placeholder="000000"
                    required
                >
            </div>

            <div class="auth-group">
                <label for="nueva_contrasena">Nueva contraseña</label>
                <div class="password-field">
                    <input
                        type="password"
                        id="nueva_contrasena"
                        name="nueva_contrasena"
                        placeholder="Mínimo 8 caracteres"
                        required
                    >
                    <button type="button" class="password-toggle" data-password-toggle="nueva_contrasena" aria-label="Mostrar contraseña">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <div class="auth-group">
                <label for="confirmar_contrasena">Confirmar contraseña</label>
                <div class="password-field">
                    <input
                        type="password"
                        id="confirmar_contrasena"
                        name="confirmar_contrasena"
                        placeholder="Repite tu contraseña"
                        required
                    >
                    <button type="button" class="password-toggle" data-password-toggle="confirmar_contrasena" aria-label="Mostrar contraseña">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="auth-submit">Cambiar contraseña</button>
        </form>
    </section>
</main>

<?php if ($success): ?>
<script>
window.edunexoSuccess = <?php echo json_encode($success); ?>;
</script>
<?php endif; ?>

<?php if ($error): ?>
<script>
window.edunexoError = <?php echo json_encode($error); ?>;
</script>
<?php endif; ?>

<?php if ($devCode): ?>
<script>
window.edunexoRecoveryCode = <?php echo json_encode($devCode); ?>;
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/main.js?v=recovery-1"></script>
</body>
</html>
