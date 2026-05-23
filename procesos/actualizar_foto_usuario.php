<?php
session_start();
require_once '../config/conexion.php';
require_once '../includes/csrf.php';
require_once '../includes/profile_photo.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../superadmin/usuarios/usuarios_superadmin.php');
    exit;
}

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'administrador') {
    header('Location: ../index.php');
    exit;
}

$idSuperadmin = (int) $_SESSION['usuario_id'];
$idUsuario = (int) ($_POST['id_usuario'] ?? 0);
$redirect = $_POST['redirect'] ?? '../superadmin/usuarios/usuarios_superadmin.php';
$redirectPermitido = '../superadmin/usuarios/detalle_usuario_superadmin.php?id=' . $idUsuario;

if ($redirect !== $redirectPermitido) {
    $redirect = '../superadmin/usuarios/usuarios_superadmin.php';
}

edunexo_require_csrf($redirect);

if ($idUsuario <= 0) {
    $_SESSION['error'] = 'Usuario inválido.';
    header('Location: ../superadmin/usuarios/usuarios_superadmin.php');
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT tipo_admin
        FROM administrador
        WHERE id_admin = :id_admin
        LIMIT 1
    ");
    $stmt->execute([':id_admin' => $idSuperadmin]);
    $actor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$actor || ($actor['tipo_admin'] ?? '') !== 'superadmin') {
        throw new RuntimeException('No tienes permisos para actualizar fotos de perfil.');
    }

    $stmt = $pdo->prepare("
        SELECT rol
        FROM usuario
        WHERE id_usuario = :id_usuario
        LIMIT 1
    ");
    $stmt->execute([':id_usuario' => $idUsuario]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        throw new RuntimeException('No se encontró el usuario.');
    }

    $fotoUrl = edunexo_upload_profile_photo('foto_perfil', 'usuario_' . $idUsuario);

    if (($usuario['rol'] ?? '') === 'estudiante') {
        $stmt = $pdo->prepare("UPDATE estudiante SET foto_url = :foto_url WHERE id_estudiante = :id_usuario");
    } elseif (($usuario['rol'] ?? '') === 'organizacion') {
        $stmt = $pdo->prepare("UPDATE organizacion SET foto_url = :foto_url WHERE id_organizacion = :id_usuario");
    } elseif (($usuario['rol'] ?? '') === 'administrador') {
        $stmt = $pdo->prepare("UPDATE administrador SET foto_url = :foto_url WHERE id_admin = :id_usuario");
    } else {
        throw new RuntimeException('Rol de usuario no soportado.');
    }

    $stmt->execute([
        ':foto_url' => $fotoUrl,
        ':id_usuario' => $idUsuario
    ]);

    $_SESSION['success'] = 'Foto de perfil actualizada correctamente.';
} catch (Throwable $e) {
    $_SESSION['error'] = $e->getMessage();
}

header('Location: ' . $redirect);
exit;
