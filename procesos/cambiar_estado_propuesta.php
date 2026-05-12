<?php
session_start();
require_once '../config/conexion.php';

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'organizacion') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../organizacion/propuestas_organizacion.php');
    exit;
}

$idOrganizacion = (int) $_SESSION['usuario_id'];
$idPropuesta = isset($_POST['id_propuesta']) ? (int) $_POST['id_propuesta'] : 0;
$nuevoEstado = trim($_POST['estado'] ?? '');
$feedback = trim($_POST['feedback'] ?? '');

$estadosValidos = ['en revisión', 'aceptada', 'rechazada'];

if ($idPropuesta <= 0 || !in_array($nuevoEstado, $estadosValidos, true)) {
    $_SESSION['error'] = 'No se pudo actualizar la propuesta.';
    header('Location: ../organizacion/propuestas_organizacion.php');
    exit;
}

if (mb_strlen($feedback) > 1000) {
    $_SESSION['error'] = 'El feedback no puede exceder 1000 caracteres.';
    header('Location: ../organizacion/propuestas_organizacion.php');
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT
            p.id_estudiante,
            p.estado AS estado_actual,
            d.titulo AS titulo_desafio
        FROM propuesta p
        INNER JOIN desafio d
            ON p.id_desafio = d.id_desafio
        WHERE p.id_propuesta = :id_propuesta
          AND d.id_organizacion = :id_organizacion
        LIMIT 1
    ");
    $stmt->execute([
        ':id_propuesta' => $idPropuesta,
        ':id_organizacion' => $idOrganizacion
    ]);

    $propuesta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$propuesta) {
        $_SESSION['error'] = 'La propuesta no pertenece a tu organización.';
        header('Location: ../organizacion/propuestas_organizacion.php');
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE propuesta
        SET estado = :estado,
            feedback = :feedback,
            fecha_respuesta = CURRENT_TIMESTAMP
        WHERE id_propuesta = :id_propuesta
    ");
    $stmt->execute([
        ':estado' => $nuevoEstado,
        ':feedback' => $feedback !== '' ? $feedback : null,
        ':id_propuesta' => $idPropuesta
    ]);

    $stmt = $pdo->prepare("
        INSERT INTO notificacion (
            tipo,
            mensaje,
            id_usuario
        )
        VALUES (
            'propuesta_actualizada',
            :mensaje,
            :id_usuario
        )
    ");
    $stmt->execute([
        ':mensaje' => 'Tu propuesta para el desafío "' . $propuesta['titulo_desafio'] . '" cambió a "' . $nuevoEstado . '". ID_PROPUESTA:' . $idPropuesta,
        ':id_usuario' => $propuesta['id_estudiante']
    ]);

    $_SESSION['success'] = 'Propuesta actualizada correctamente.';
    header('Location: ../organizacion/propuestas_organizacion.php');
    exit;

} catch (PDOException $e) {
    $_SESSION['error'] = 'Ocurrió un error al actualizar la propuesta.';
    header('Location: ../organizacion/propuestas_organizacion.php');
    exit;
}
