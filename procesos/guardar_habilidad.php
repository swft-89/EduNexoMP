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

$nombre = trim($_POST['nombre'] ?? '');
$categoriaHabilidad = trim($_POST['categoria_habilidad'] ?? '');
$idSuperadmin = (int) $_SESSION['usuario_id'];

$_SESSION['old_habilidad'] = [
    'nombre' => $nombre,
    'categoria_habilidad' => $categoriaHabilidad
];

if ($nombre === '') {
    $_SESSION['error'] = 'El nombre de la habilidad es obligatorio.';
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
        LIMIT 1
    ");
    $stmtExiste->execute([
        ':nombre' => $nombre
    ]);

    if ($stmtExiste->fetch()) {
        throw new Exception('Ya existe una habilidad con ese nombre.');
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO habilidad (
            nombre,
            categoria_habilidad
        ) VALUES (
            :nombre,
            :categoria_habilidad
        )
    ");
    $stmt->execute([
        ':nombre' => $nombre,
        ':categoria_habilidad' => $categoriaHabilidad !== '' ? $categoriaHabilidad : null
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
        ':tipo_evento' => 'Habilidad creada',
        ':descripcion' => 'Se creó la habilidad "' . $nombre . '"',
        ':usuario_nombre' => 'Superadministrador',
        ':usuario_rol' => 'Admin',
        ':id_usuario_relacionado' => $idSuperadmin
    ]);

    $pdo->commit();

    unset($_SESSION['old_habilidad']);
    $_SESSION['success'] = 'Habilidad creada correctamente.';
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error'] = $e->getMessage();
}

header('Location: ../superadmin/categorias_superadmin.php');
exit;
