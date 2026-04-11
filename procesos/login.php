<?php
session_start();
require_once __DIR__ . '/../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$correo = trim($_POST['correo'] ?? '');
$contrasena = trim($_POST['contrasena'] ?? '');

if ($correo === '' || $contrasena === '') {
    $_SESSION['error'] = "Debes ingresar correo y contraseña.";
    header('Location: ../index.php');
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id_usuario, correo_electronico, hash_contrasena, rol, estado
        FROM usuario
        WHERE correo_electronico = :correo
        LIMIT 1
    ");
    $stmt->execute([':correo' => $correo]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        throw new Exception("Correo o contraseña incorrectos.");
    }

    if (!password_verify($contrasena, $usuario['hash_contrasena'])) {
        throw new Exception("Correo o contraseña incorrectos.");
    }

    if ($usuario['rol'] === 'administrador') {
        $stmtAdmin = $pdo->prepare("
            SELECT tipo_admin, estado_solicitud
            FROM administrador
            WHERE id_admin = :id_admin
            LIMIT 1
        ");
        $stmtAdmin->execute([
            ':id_admin' => $usuario['id_usuario']
        ]);

        $admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

        if (!$admin) {
            throw new Exception("No se encontró el perfil del administrador.");
        }

        if ($admin['estado_solicitud'] === 'pendiente') {
            throw new Exception("Tu solicitud de administrador aún está pendiente de autorización.");
        }

        if ($admin['estado_solicitud'] === 'rechazado') {
            throw new Exception("Tu solicitud de administrador fue rechazada.");
        }
    }

    if (($usuario['estado'] ?? 'activo') !== 'activo') {
        throw new Exception("Tu cuenta no está activa.");
    }

    $_SESSION['usuario_id'] = $usuario['id_usuario'];
    $_SESSION['correo'] = $usuario['correo_electronico'];
    $_SESSION['rol'] = $usuario['rol'];

    if ($usuario['rol'] === 'estudiante') {
        header('Location: ../estudiante/dashboard_estudiante.php');
        exit;
    }

    if ($usuario['rol'] === 'organizacion') {
        header('Location: ../organizacion/dashboard_organizacion.php');
        exit;
    }

    if ($usuario['rol'] === 'administrador') {
        if (($admin['tipo_admin'] ?? 'admin') === 'superadmin') {
            header('Location: ../superadmin/dashboard_superadmin.php');
            exit;
        }

        header('Location: ../admin/dashboard_admin.php');
        exit;
    }

    header('Location: ../index.php');
    exit;

} catch (Throwable $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: ../index.php');
    exit;
}