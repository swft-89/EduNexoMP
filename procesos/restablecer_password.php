<?php
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/password_reset_schema.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../recuperar_password.php');
    exit;
}

edunexo_require_csrf('../recuperar_password.php');

$correo = trim($_POST['correo'] ?? '');
$codigo = preg_replace('/\D+/', '', $_POST['codigo'] ?? '');
$nuevaContrasena = trim($_POST['nueva_contrasena'] ?? '');
$confirmarContrasena = trim($_POST['confirmar_contrasena'] ?? '');

$_SESSION['password_reset_email'] = $correo;

if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Ingresa un correo electronico valido.';
    header('Location: ../recuperar_password.php');
    exit;
}

if (strlen($codigo) !== 6) {
    $_SESSION['error'] = 'Ingresa el codigo de 6 digitos.';
    header('Location: ../recuperar_password.php');
    exit;
}

if ($nuevaContrasena !== $confirmarContrasena) {
    $_SESSION['error'] = 'Las contrasenas no coinciden.';
    header('Location: ../recuperar_password.php');
    exit;
}

if (!edunexo_is_valid_password($nuevaContrasena)) {
    $_SESSION['error'] = 'La contrasena debe tener al menos 8 caracteres e incluir letras y numeros.';
    header('Location: ../recuperar_password.php');
    exit;
}

try {
    edunexo_ensure_password_reset_table($pdo);

    $stmt = $pdo->prepare("
        SELECT id_usuario
        FROM usuario
        WHERE correo_electronico = :correo
        LIMIT 1
    ");
    $stmt->execute([':correo' => $correo]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        throw new RuntimeException('El codigo es invalido o ya expiro.');
    }

    $stmt = $pdo->prepare("
        SELECT id_reset, codigo_hash
        FROM password_reset
        WHERE id_usuario = :id_usuario
          AND usado = FALSE
          AND fecha_expiracion >= CURRENT_TIMESTAMP
        ORDER BY fecha_creacion DESC
        LIMIT 1
    ");
    $stmt->execute([':id_usuario' => $usuario['id_usuario']]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reset || !password_verify($codigo, $reset['codigo_hash'])) {
        throw new RuntimeException('El codigo es invalido o ya expiro.');
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        UPDATE usuario
        SET hash_contrasena = :hash_contrasena
        WHERE id_usuario = :id_usuario
    ");
    $stmt->execute([
        ':hash_contrasena' => password_hash($nuevaContrasena, PASSWORD_DEFAULT),
        ':id_usuario' => $usuario['id_usuario']
    ]);

    $stmt = $pdo->prepare("
        UPDATE password_reset
        SET usado = TRUE
        WHERE id_usuario = :id_usuario
    ");
    $stmt->execute([':id_usuario' => $usuario['id_usuario']]);

    $pdo->commit();

    unset($_SESSION['password_reset_email']);
    $_SESSION['success'] = 'Tu contrasena fue actualizada. Ya puedes iniciar sesion.';
    $_SESSION['auth_modal'] = 'login';
    header('Location: ../index.php');
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error'] = $e->getMessage();
    header('Location: ../recuperar_password.php');
    exit;
}
