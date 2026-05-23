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

$idCategoria = (int) ($_POST['id_categoria'] ?? 0);
$idSuperadmin = (int) $_SESSION['usuario_id'];

if ($idCategoria <= 0) {
    $_SESSION['error'] = 'Categoría inválida.';
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

    $stmtCat = $pdo->prepare("
        SELECT nombre_categoria
        FROM categoria
        WHERE id_categoria = :id_categoria
        LIMIT 1
    ");
    $stmtCat->execute([
        ':id_categoria' => $idCategoria
    ]);

    $categoria = $stmtCat->fetch(PDO::FETCH_ASSOC);

    if (!$categoria) {
        throw new Exception('No se encontró la categoría.');
    }

    $stmtUso = $pdo->prepare("
        SELECT COUNT(*)
        FROM desafio
        WHERE id_categoria = :id_categoria
    ");
    $stmtUso->execute([
        ':id_categoria' => $idCategoria
    ]);

    $enUso = (int) $stmtUso->fetchColumn();

    if ($enUso > 0) {
        throw new Exception('No se puede eliminar la categoría porque tiene desafíos asociados.');
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        DELETE FROM categoria
        WHERE id_categoria = :id_categoria
    ");
    $stmt->execute([
        ':id_categoria' => $idCategoria
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
        ':tipo_evento' => 'Categoría eliminada',
        ':descripcion' => 'Se eliminó la categoría "' . ($categoria['nombre_categoria'] ?? '') . '"',
        ':usuario_nombre' => 'Superadministrador',
        ':usuario_rol' => 'Admin',
        ':id_usuario_relacionado' => $idSuperadmin
    ]);

    $pdo->commit();

    $_SESSION['success'] = 'Categoría eliminada correctamente.';
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error'] = $e->getMessage();
}

header('Location: ../superadmin/categorias_superadmin.php');
exit;
