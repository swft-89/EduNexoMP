<?php
session_start();
require_once __DIR__ . '/../config/conexion.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'estudiante') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../estudiante/dashboard_estudiante.php');
    exit;
}

$idEstudiante = (int) $_SESSION['usuario_id'];
$idDesafio = (int) ($_POST['id_desafio'] ?? 0);

if ($idDesafio <= 0) {
    $_SESSION['error'] = 'Desafio no valido.';
    header('Location: ../estudiante/dashboard_estudiante.php');
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id_desafio
        FROM desafio
        WHERE id_desafio = :id_desafio
          AND LOWER(COALESCE(estado, '')) = 'activo'
        LIMIT 1
    ");
    $stmt->execute([':id_desafio' => $idDesafio]);

    if (!$stmt->fetchColumn()) {
        throw new RuntimeException('Este desafio no esta disponible para recibir propuestas.');
    }

    $stmt = $pdo->prepare("
        SELECT 1
        FROM propuesta
        WHERE id_estudiante = :id_estudiante
          AND id_desafio = :id_desafio
        LIMIT 1
    ");
    $stmt->execute([
        ':id_estudiante' => $idEstudiante,
        ':id_desafio' => $idDesafio
    ]);

    if ($stmt->fetchColumn()) {
        $_SESSION['error'] = 'Ya enviaste una propuesta para este desafio.';
        header('Location: ../estudiante/mis_propuestas.php');
        exit;
    }

    $_SESSION['success'] = 'Completa el formulario para enviar tu propuesta.';
    header('Location: ../estudiante/crear_propuesta.php?id=' . $idDesafio);
    exit;
} catch (Throwable $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: ../estudiante/dashboard_estudiante.php');
    exit;
}
