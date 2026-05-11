<?php
session_start();
require_once '../../config/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../estudiante/dashboard_estudiante.php');
    exit;
}

$idEstudiante = (int) $_SESSION['usuario_id'];
$idDesafio = isset($_POST['id_desafio']) ? (int) $_POST['id_desafio'] : 0;
$redirect = $_POST['redirect'] ?? '../estudiante/dashboard_estudiante.php';

if ($idDesafio <= 0) {
    $_SESSION['error'] = 'Desafío no válido.';
    header('Location: ../' . $redirect);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 1
        FROM favoritos
        WHERE id_estudiante = :id_estudiante
          AND id_desafio = :id_desafio
        LIMIT 1
    ");
    $stmt->execute([
        ':id_estudiante' => $idEstudiante,
        ':id_desafio' => $idDesafio
    ]);

    $existe = $stmt->fetchColumn();

    if ($existe) {
        $stmt = $pdo->prepare("
            DELETE FROM favoritos
            WHERE id_estudiante = :id_estudiante
              AND id_desafio = :id_desafio
        ");
        $stmt->execute([
            ':id_estudiante' => $idEstudiante,
            ':id_desafio' => $idDesafio
        ]);

        $_SESSION['success'] = 'Desafío eliminado de favoritos.';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO favoritos (id_estudiante, id_desafio)
            VALUES (:id_estudiante, :id_desafio)
        ");
        $stmt->execute([
            ':id_estudiante' => $idEstudiante,
            ':id_desafio' => $idDesafio
        ]);

        $_SESSION['success'] = 'Desafío agregado a favoritos.';
    }
} catch (PDOException $e) {
    $_SESSION['error'] = 'No se pudo actualizar favoritos.';
}

header('Location: ../' . $redirect);
exit;