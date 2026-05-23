<?php
session_start();
require_once '../config/conexion.php';
require_once '../includes/csrf.php';

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'organizacion') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../organizacion/dashboard_organizacion.php');
    exit;
}

edunexo_require_csrf('../organizacion/dashboard_organizacion.php');

$idOrganizacion = (int) $_SESSION['usuario_id'];
$idDesafio = (int) ($_POST['id_desafio'] ?? 0);
$estado = trim($_POST['estado'] ?? '');

$estadosValidos = ['activo', 'cerrado'];

if ($idDesafio <= 0 || !in_array($estado, $estadosValidos, true)) {
    $_SESSION['error'] = 'Estado de desafío no válido.';
    header('Location: ../organizacion/desafios/detalle_desafio_organizacion.php?id=' . $idDesafio);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE desafio
        SET estado = :estado
        WHERE id_desafio = :id_desafio
          AND id_organizacion = :id_organizacion
    ");
    $stmt->execute([
        ':estado' => $estado,
        ':id_desafio' => $idDesafio,
        ':id_organizacion' => $idOrganizacion
    ]);

    if ($stmt->rowCount() === 0) {
        $_SESSION['error'] = 'No se pudo actualizar el estado del desafío.';
        header('Location: ../organizacion/desafios/detalle_desafio_organizacion.php?id=' . $idDesafio);
        exit;
    }

    $_SESSION['success'] = 'Estado del desafío actualizado.';
    header('Location: ../organizacion/desafios/detalle_desafio_organizacion.php?id=' . $idDesafio);
    exit;
} catch (PDOException $e) {
    $_SESSION['error'] = 'Ocurrió un error al cambiar el estado.';
    header('Location: ../organizacion/desafios/detalle_desafio_organizacion.php?id=' . $idDesafio);
    exit;
}
