<?php
require_once __DIR__ . '/../config/conexion.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../superadmin/usuarios_superadmin.php');
    exit;
}

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'administrador') {
    header('Location: ../index.php');
    exit;
}

$idSuperadmin = (int) $_SESSION['usuario_id'];
$idUsuarioObjetivo = (int) ($_POST['id_usuario'] ?? 0);
$nuevoEstado = trim($_POST['nuevo_estado'] ?? '');

$estadosPermitidos = ['activo', 'suspendido'];

if ($idUsuarioObjetivo <= 0 || !in_array($nuevoEstado, $estadosPermitidos, true)) {
    $_SESSION['error'] = 'Solicitud inválida.';
    header('Location: ../usuarios_superadmin.php');
    exit;
}

try {
    $stmtActor = $pdo->prepare("
        SELECT tipo_admin
        FROM administrador
        WHERE id_admin = :id_admin
        LIMIT 1
    ");
    $stmtActor->execute([
        ':id_admin' => $idSuperadmin
    ]);

    $actor = $stmtActor->fetch(PDO::FETCH_ASSOC);

    if (!$actor || ($actor['tipo_admin'] ?? '') !== 'superadmin') {
        throw new Exception('No tienes permisos para realizar esta acción.');
    }

    if ($idUsuarioObjetivo === $idSuperadmin) {
        throw new Exception('No puedes cambiar el estado de tu propia cuenta desde aquí.');
    }

    $pdo->beginTransaction();

    $stmtUsuario = $pdo->prepare("
        SELECT 
            u.id_usuario,
            u.correo_electronico,
            u.rol,
            CASE
                WHEN u.rol = 'estudiante' THEN CONCAT_WS(' ', e.nombre, e.apellido_paterno, e.apellido_materno)
                WHEN u.rol = 'organizacion' THEN o.nombre_empresa
                WHEN u.rol = 'administrador' THEN CONCAT_WS(' ', a.nombre, a.apellido_paterno, a.apellido_materno)
                ELSE u.correo_electronico
            END AS nombre_mostrar
        FROM usuario u
        LEFT JOIN estudiante e ON e.id_estudiante = u.id_usuario
        LEFT JOIN organizacion o ON o.id_organizacion = u.id_usuario
        LEFT JOIN administrador a ON a.id_admin = u.id_usuario
        WHERE u.id_usuario = :id_usuario
        LIMIT 1
    ");
    $stmtUsuario->execute([
        ':id_usuario' => $idUsuarioObjetivo
    ]);

    $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        throw new Exception('No se encontró el usuario.');
    }

    if (($usuario['rol'] ?? '') === 'administrador') {
        $stmtTipoAdmin = $pdo->prepare("
            SELECT tipo_admin
            FROM administrador
            WHERE id_admin = :id_admin
            LIMIT 1
        ");
        $stmtTipoAdmin->execute([
            ':id_admin' => $idUsuarioObjetivo
        ]);

        $adminObjetivo = $stmtTipoAdmin->fetch(PDO::FETCH_ASSOC);

        if (($adminObjetivo['tipo_admin'] ?? '') === 'superadmin') {
            throw new Exception('No puedes suspender ni modificar a otro superadmin desde esta vista.');
        }
    }

    $stmtUpdate = $pdo->prepare("
        UPDATE usuario
        SET estado = :estado
        WHERE id_usuario = :id_usuario
    ");
    $stmtUpdate->execute([
        ':estado' => $nuevoEstado,
        ':id_usuario' => $idUsuarioObjetivo
    ]);

    $tipoEvento = $nuevoEstado === 'suspendido' ? 'Usuario suspendido' : 'Usuario reactivado';
    $descripcion = $nuevoEstado === 'suspendido'
        ? 'Se suspendió la cuenta del usuario'
        : 'Se reactivó la cuenta del usuario';

    $rolMostrar = match ($usuario['rol'] ?? '') {
        'estudiante' => 'Est.',
        'organizacion' => 'Org.',
        'administrador' => 'Admin',
        default => ''
    };

    $stmtAudit = $pdo->prepare("
        INSERT INTO auditoria_admin (
            tipo_evento,
            descripcion,
            usuario_nombre,
            usuario_rol,
            id_usuario_relacionado
        ) VALUES (
            :tipo_evento,
            :descripcion,
            :usuario_nombre,
            :usuario_rol,
            :id_usuario_relacionado
        )
    ");
    $stmtAudit->execute([
        ':tipo_evento' => $tipoEvento,
        ':descripcion' => $descripcion,
        ':usuario_nombre' => $usuario['nombre_mostrar'] ?: $usuario['correo_electronico'],
        ':usuario_rol' => $rolMostrar,
        ':id_usuario_relacionado' => $idUsuarioObjetivo
    ]);

    $pdo->commit();

    $_SESSION['success'] = $nuevoEstado === 'suspendido'
        ? 'Usuario suspendido correctamente.'
        : 'Usuario reactivado correctamente.';
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error'] = $e->getMessage();
}

header('Location: ../superadmin/usuarios_superadmin.php');
exit;