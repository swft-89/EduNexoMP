<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/csrf.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../superadmin/desafio/desafios_superadmin.php');
    exit;
}

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'administrador') {
    header('Location: ../index.php');
    exit;
}

$idSuperadmin = (int) $_SESSION['usuario_id'];
$idDesafio = (int) ($_POST['id_desafio'] ?? 0);
$nuevoEstado = trim($_POST['nuevo_estado'] ?? '');

edunexo_require_csrf('../superadmin/desafio/desafios_superadmin.php');

$estadosPermitidos = ['activo', 'pausado', 'cerrado'];

if ($idDesafio <= 0 || !in_array($nuevoEstado, $estadosPermitidos, true)) {
    $_SESSION['error'] = 'Solicitud inválida.';
    header('Location: ../superadmin/desafio/desafios_superadmin.php');
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

    $pdo->beginTransaction();

    $stmtDesafio = $pdo->prepare("
        SELECT
            d.id_desafio,
            d.titulo,
            o.nombre_empresa,
            o.id_organizacion
        FROM desafio d
        INNER JOIN organizacion o ON o.id_organizacion = d.id_organizacion
        WHERE d.id_desafio = :id_desafio
        LIMIT 1
    ");
    $stmtDesafio->execute([
        ':id_desafio' => $idDesafio
    ]);

    $desafio = $stmtDesafio->fetch(PDO::FETCH_ASSOC);

    if (!$desafio) {
        throw new Exception('No se encontró el desafío.');
    }

    $stmtUpdate = $pdo->prepare("
        UPDATE desafio
        SET estado = :estado
        WHERE id_desafio = :id_desafio
    ");
    $stmtUpdate->execute([
        ':estado' => $nuevoEstado,
        ':id_desafio' => $idDesafio
    ]);

    $tipoEvento = 'Estado desafío';
    $descripcion = 'El desafío "' . ($desafio['titulo'] ?? 'Sin título') . '" cambió a estado "' . $nuevoEstado . '"';

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
        ':usuario_nombre' => $desafio['nombre_empresa'] ?? 'Organización',
        ':usuario_rol' => 'Org.',
        ':id_usuario_relacionado' => $desafio['id_organizacion']
    ]);

    $pdo->commit();

    $_SESSION['success'] = 'Estado del desafío actualizado correctamente.';
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error'] = $e->getMessage();
}

header('Location: ../superadmin/desafio/desafios_superadmin.php');
exit;
