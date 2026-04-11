<?php
require_once __DIR__ . '/../config/conexion.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard_superadmin.php');
    exit;
}

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'administrador') {
    header('Location: ../index.php');
    exit;
}

$idAdminObjetivo = (int) ($_POST['id_admin'] ?? 0);
$idSuperadmin = (int) $_SESSION['usuario_id'];

if ($idAdminObjetivo <= 0) {
    $_SESSION['error'] = "Solicitud inválida.";
    header('Location: ../dashboard_superadmin.php');
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
        throw new Exception("No tienes permisos para realizar esta acción.");
    }

    $pdo->beginTransaction();

    $stmtObjetivo = $pdo->prepare("
        SELECT nombre, apellido_paterno, apellido_materno, estado_solicitud
        FROM administrador
        WHERE id_admin = :id_admin
        LIMIT 1
    ");
    $stmtObjetivo->execute([
        ':id_admin' => $idAdminObjetivo
    ]);

    $adminObjetivo = $stmtObjetivo->fetch(PDO::FETCH_ASSOC);

    if (!$adminObjetivo) {
        throw new Exception("No se encontró el administrador objetivo.");
    }

    $nombreAdminObjetivo = trim(
        ($adminObjetivo['nombre'] ?? '') . ' ' .
        ($adminObjetivo['apellido_paterno'] ?? '') . ' ' .
        ($adminObjetivo['apellido_materno'] ?? '')
    );

    $stmt = $pdo->prepare("
        UPDATE administrador
        SET estado_solicitud = 'aprobado',
            autorizado_por = :autorizado_por,
            fecha_autorizacion = CURRENT_TIMESTAMP
        WHERE id_admin = :id_admin
    ");
    $stmt->execute([
        ':autorizado_por' => $idSuperadmin,
        ':id_admin' => $idAdminObjetivo
    ]);

    $stmt = $pdo->prepare("
        UPDATE usuario
        SET estado = 'activo'
        WHERE id_usuario = :id_usuario
    ");
    $stmt->execute([
        ':id_usuario' => $idAdminObjetivo
    ]);

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
        ':tipo_evento' => 'Verificación',
        ':descripcion' => 'Solicitud de administrador aprobada',
        ':usuario_nombre' => $nombreAdminObjetivo !== '' ? $nombreAdminObjetivo : 'Administrador',
        ':usuario_rol' => 'Admin',
        ':id_usuario_relacionado' => $idAdminObjetivo
    ]);

    $pdo->commit();

    $_SESSION['success'] = "Administrador aprobado correctamente.";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error'] = $e->getMessage();
}

header('Location: ../dashboard_superadmin.php');
exit;