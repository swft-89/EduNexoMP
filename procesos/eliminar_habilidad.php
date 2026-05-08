<?php
require_once __DIR__ . '/../config/conexion.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../superadmin/categorias_superadmin.php');
    exit;
}

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'administrador') {
    header('Location: ../index.php');
    exit;
}

$idHabilidad = (int) ($_POST['id_habilidad'] ?? 0);
$idSuperadmin = (int) $_SESSION['usuario_id'];

if ($idHabilidad <= 0) {
    $_SESSION['error'] = 'Habilidad inválida.';
    header('Location: ../superadmin/categorias_superadmin.php');
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

    $stmtHab = $pdo->prepare("
        SELECT nombre
        FROM habilidad
        WHERE id_habilidad = :id_habilidad
        LIMIT 1
    ");
    $stmtHab->execute([
        ':id_habilidad' => $idHabilidad
    ]);

    $habilidad = $stmtHab->fetch(PDO::FETCH_ASSOC);

    if (!$habilidad) {
        throw new Exception('No se encontró la habilidad.');
    }

    $stmtUsoEst = $pdo->prepare("
        SELECT COUNT(*)
        FROM estudiante_habilidad
        WHERE id_habilidad = :id_habilidad
    ");
    $stmtUsoEst->execute([
        ':id_habilidad' => $idHabilidad
    ]);
    $enEstudiantes = (int) $stmtUsoEst->fetchColumn();

    $stmtUsoDes = $pdo->prepare("
        SELECT COUNT(*)
        FROM desafio_habilidad
        WHERE id_habilidad = :id_habilidad
    ");
    $stmtUsoDes->execute([
        ':id_habilidad' => $idHabilidad
    ]);
    $enDesafios = (int) $stmtUsoDes->fetchColumn();

    if ($enEstudiantes > 0 || $enDesafios > 0) {
        throw new Exception('No se puede eliminar la habilidad porque está en uso en estudiantes o desafíos.');
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        DELETE FROM habilidad
        WHERE id_habilidad = :id_habilidad
    ");
    $stmt->execute([
        ':id_habilidad' => $idHabilidad
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
        ':tipo_evento' => 'Habilidad eliminada',
        ':descripcion' => 'Se eliminó la habilidad "' . ($habilidad['nombre'] ?? '') . '"',
        ':usuario_nombre' => 'Superadministrador',
        ':usuario_rol' => 'Admin',
        ':id_usuario_relacionado' => $idSuperadmin
    ]);

    $pdo->commit();

    $_SESSION['success'] = 'Habilidad eliminada correctamente.';
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error'] = $e->getMessage();
}

header('Location: ../superadmin/categorias_superadmin.php');
exit;