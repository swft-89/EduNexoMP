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

$nombre = trim($_POST['nombre_categoria'] ?? '');
$descripcion = trim($_POST['descripcion_categoria'] ?? '');
$idSuperadmin = (int) $_SESSION['usuario_id'];

$_SESSION['old_categoria'] = [
    'nombre_categoria' => $nombre,
    'descripcion_categoria' => $descripcion
];

if ($nombre === '') {
    $_SESSION['error'] = 'El nombre de la categoría es obligatorio.';
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
        SELECT id_categoria
        FROM categoria
        WHERE LOWER(nombre_categoria) = LOWER(:nombre)
        LIMIT 1
    ");
    $stmtExiste->execute([
        ':nombre' => $nombre
    ]);

    if ($stmtExiste->fetch()) {
        throw new Exception('Ya existe una categoría con ese nombre.');
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO categoria (
            nombre_categoria,
            descripcion_categoria
        ) VALUES (
            :nombre_categoria,
            :descripcion_categoria
        )
    ");
    $stmt->execute([
        ':nombre_categoria' => $nombre,
        ':descripcion_categoria' => $descripcion !== '' ? $descripcion : null
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
        ':tipo_evento' => 'Categoría creada',
        ':descripcion' => 'Se creó la categoría "' . $nombre . '"',
        ':usuario_nombre' => 'Superadministrador',
        ':usuario_rol' => 'Admin',
        ':id_usuario_relacionado' => $idSuperadmin
    ]);

    $pdo->commit();

    unset($_SESSION['old_categoria']);
    $_SESSION['success'] = 'Categoría creada correctamente.';
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error'] = $e->getMessage();
}

header('Location: ../superadmin/categorias_superadmin.php');
exit;