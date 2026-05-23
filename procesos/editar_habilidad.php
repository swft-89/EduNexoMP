<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/csrf.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../superadmin/categorias_superadmin.php');
    exit;
}

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'administrador') {
    header('Location: ../index.php');
    exit;
}

edunexo_require_csrf('../superadmin/categorias_superadmin.php');

$idHabilidad = (int) ($_POST['id_habilidad'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');
$categoriaHabilidad = trim($_POST['categoria_habilidad'] ?? '');
$idSuperadmin = (int) $_SESSION['usuario_id'];

if ($idHabilidad <= 0 || $nombre === '') {
    $_SESSION['error'] = 'Datos inválidos para editar la habilidad.';
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

    $stmtExiste = $pdo->prepare("
        SELECT id_habilidad
        FROM habilidad
        WHERE LOWER(nombre) = LOWER(:nombre)
          AND id_habilidad <> :id_habilidad
        LIMIT 1
    ");
    $stmtExiste->execute([
        ':nombre' => $nombre,
        ':id_habilidad' => $idHabilidad
    ]);

    if ($stmtExiste->fetch()) {
        throw new Exception('Ya existe otra habilidad con ese nombre.');
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        UPDATE habilidad
        SET nombre = :nombre,
            categoria_habilidad = :categoria_habilidad
        WHERE id_habilidad = :id_habilidad
    ");
    $stmt->execute([
        ':nombre' => $nombre,
        ':categoria_habilidad' => $categoriaHabilidad !== '' ? $categoriaHabilidad : null,
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
        ':tipo_evento' => 'Habilidad editada',
        ':descripcion' => 'Se editó la habilidad "' . $nombre . '"',
        ':usuario_nombre' => 'Superadministrador',
        ':usuario_rol' => 'Admin',
        ':id_usuario_relacionado' => $idSuperadmin
    ]);

    $pdo->commit();

    $_SESSION['success'] = 'Habilidad actualizada correctamente.';
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error'] = $e->getMessage();
}

header('Location: ../superadmin/categorias_superadmin.php');
exit;
