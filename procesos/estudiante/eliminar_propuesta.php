<?php
session_start();
require_once '../../config/conexion.php';
require_once '../../includes/propuesta_utils.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'estudiante') {
    header('Location: ../../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../estudiante/mis_propuestas.php');
    exit;
}

$idEstudiante = (int) $_SESSION['usuario_id'];
$idPropuesta = (int) ($_POST['id_propuesta'] ?? 0);

if ($idPropuesta <= 0) {
    $_SESSION['error'] = 'Propuesta no vÃ¡lida.';
    header('Location: ../../estudiante/mis_propuestas.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT id_propuesta, estado, fecha_respuesta
        FROM propuesta
        WHERE id_propuesta = :id_propuesta
          AND id_estudiante = :id_estudiante
        LIMIT 1
    ");
    $stmt->execute([
        ':id_propuesta' => $idPropuesta,
        ':id_estudiante' => $idEstudiante
    ]);
    $propuesta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$propuesta) {
        throw new RuntimeException('No se encontrÃ³ la propuesta.');
    }

    if (!edunexo_propuesta_editable($propuesta['estado'] ?? '') || !empty($propuesta['fecha_respuesta'])) {
        throw new RuntimeException('Solo puedes eliminar propuestas que aÃºn estÃ¡n en revisiÃ³n.');
    }

    $stmt = $pdo->prepare("
        SELECT url_archivo
        FROM documento_propuesta
        WHERE id_propuesta = :id_propuesta
    ");
    $stmt->execute([':id_propuesta' => $idPropuesta]);
    $archivos = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->prepare("
        SELECT id_conversacion
        FROM conversacion
        WHERE id_propuesta = :id_propuesta
    ");
    $stmt->execute([':id_propuesta' => $idPropuesta]);
    $conversaciones = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($conversaciones)) {
        $placeholders = implode(',', array_fill(0, count($conversaciones), '?'));

        $stmt = $pdo->prepare("DELETE FROM mensaje WHERE id_conversacion IN ($placeholders)");
        $stmt->execute($conversaciones);
    }

    $stmt = $pdo->prepare("DELETE FROM conversacion WHERE id_propuesta = :id_propuesta");
    $stmt->execute([':id_propuesta' => $idPropuesta]);

    $stmt = $pdo->prepare("DELETE FROM documento_propuesta WHERE id_propuesta = :id_propuesta");
    $stmt->execute([':id_propuesta' => $idPropuesta]);

    $stmt = $pdo->prepare("
        DELETE FROM propuesta
        WHERE id_propuesta = :id_propuesta
          AND id_estudiante = :id_estudiante
    ");
    $stmt->execute([
        ':id_propuesta' => $idPropuesta,
        ':id_estudiante' => $idEstudiante
    ]);

    $pdo->commit();

    foreach ($archivos as $archivo) {
        edunexo_unlink_uploaded_proposal_file($archivo);
    }

    $_SESSION['success'] = 'Propuesta eliminada correctamente.';
    header('Location: ../../estudiante/mis_propuestas.php');
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error'] = $e->getMessage();
    header('Location: ../../estudiante/mis_propuestas.php');
    exit;
}
