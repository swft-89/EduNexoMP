<?php
session_start();
require_once '../config/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard_estudiante.php');
    exit;
}

$idEstudiante = (int) $_SESSION['usuario_id'];
$idDesafio = isset($_POST['id_desafio']) ? (int) $_POST['id_desafio'] : 0;
$redirect = $_POST['redirect'] ?? 'dashboard_estudiante.php';

if ($idDesafio <= 0) {
    $_SESSION['error'] = 'Desafío no válido.';
    header('Location: ../' . $redirect);
    exit;
}

try {
    $pdo->beginTransaction();

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
        $pdo->rollBack();
        $_SESSION['error'] = 'Ya te postulaste a este desafío.';
        header('Location: ../' . $redirect);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO propuesta (
            id_estudiante,
            id_desafio,
            estado
        )
        VALUES (
            :id_estudiante,
            :id_desafio,
            :estado
        )
        RETURNING id_propuesta
    ");
    $stmt->execute([
        ':id_estudiante' => $idEstudiante,
        ':id_desafio' => $idDesafio,
        ':estado' => 'en revisión'
    ]);

    $idPropuesta = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        INSERT INTO conversacion (
            id_propuesta,
            activa
        )
        VALUES (
            :id_propuesta,
            TRUE
        )
    ");
    $stmt->execute([
        ':id_propuesta' => $idPropuesta
    ]);

    $pdo->commit();

    $_SESSION['success'] = 'Tu postulación fue enviada correctamente.';
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error'] = 'No se pudo registrar la postulación.';
}

header('Location: ../' . $redirect);
exit;