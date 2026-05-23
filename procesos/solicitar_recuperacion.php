<?php
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/password_reset_schema.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../recuperar_password.php');
    exit;
}

edunexo_require_csrf('../recuperar_password.php');

$correo = trim($_POST['correo'] ?? '');
$_SESSION['password_reset_email'] = $correo;

if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Ingresa un correo electronico valido.';
    header('Location: ../recuperar_password.php');
    exit;
}

try {
    edunexo_ensure_password_reset_table($pdo);

    $stmt = $pdo->prepare("
        SELECT id_usuario, correo_electronico, estado
        FROM usuario
        WHERE correo_electronico = :correo
        LIMIT 1
    ");
    $stmt->execute([':correo' => $correo]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    $mensajeGenerico = 'Si el correo esta registrado, recibiras un codigo para recuperar tu contrasena.';

    if (!$usuario || strtolower((string) ($usuario['estado'] ?? 'activo')) !== 'activo') {
        $_SESSION['success'] = $mensajeGenerico;
        header('Location: ../recuperar_password.php');
        exit;
    }

    $codigo = (string) random_int(100000, 999999);
    $codigoHash = password_hash($codigo, PASSWORD_DEFAULT);

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        UPDATE password_reset
        SET usado = TRUE
        WHERE id_usuario = :id_usuario
          AND usado = FALSE
    ");
    $stmt->execute([':id_usuario' => $usuario['id_usuario']]);

    $stmt = $pdo->prepare("
        INSERT INTO password_reset (
            id_usuario,
            codigo_hash,
            fecha_expiracion
        ) VALUES (
            :id_usuario,
            :codigo_hash,
            CURRENT_TIMESTAMP + INTERVAL '15 minutes'
        )
    ");
    $stmt->execute([
        ':id_usuario' => $usuario['id_usuario'],
        ':codigo_hash' => $codigoHash
    ]);

    $pdo->commit();

    $asunto = 'Codigo de recuperacion EduNexo MP';
    $mensaje = "Tu codigo de recuperacion es: {$codigo}\n\nEste codigo vence en 15 minutos.";
    $headers = "From: no-reply@edunexo.local\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    $enviado = false;

    if (function_exists('mail')) {
        $enviado = @mail($usuario['correo_electronico'], $asunto, $mensaje, $headers);
    }

    $_SESSION['success'] = $enviado
        ? 'Te enviamos un codigo de recuperacion. Revisa tu correo.'
        : 'No hay SMTP configurado en local, asi que se muestra un codigo de prueba.';

    if (!$enviado) {
        $_SESSION['password_reset_dev_code'] = $codigo;
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error'] = 'No se pudo generar el codigo de recuperacion.';
}

header('Location: ../recuperar_password.php');
exit;
