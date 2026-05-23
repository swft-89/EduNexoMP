<?php
session_start();
require_once '../config/conexion.php';
require_once '../includes/csrf.php';
require_once '../includes/propuesta_utils.php';

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'organizacion') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../organizacion/propuestas_organizacion.php');
    exit;
}

edunexo_require_csrf('../organizacion/propuestas_organizacion.php');

$idOrganizacion = (int) $_SESSION['usuario_id'];
$idPropuesta = isset($_POST['id_propuesta']) ? (int) $_POST['id_propuesta'] : 0;
$estadoRecibido = trim($_POST['estado'] ?? '');
$feedback = trim($_POST['feedback'] ?? '');

$estadoNormalizado = edunexo_normalize_estado_propuesta($estadoRecibido);
$estadosValidos = [
    'en revision' => 'en revisión',
    'aceptada' => 'aceptada',
    'rechazada' => 'rechazada'
];

if ($idPropuesta <= 0 || !isset($estadosValidos[$estadoNormalizado])) {
    $_SESSION['error'] = 'No se pudo actualizar la propuesta.';
    header('Location: ../organizacion/propuestas_organizacion.php');
    exit;
}

$nuevoEstado = $estadosValidos[$estadoNormalizado];

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
            d.id_desafio,
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
        $_SESSION['error'] = 'La propuesta no pertenece a tu organizaciÃ³n.';
        header('Location: ../organizacion/propuestas_organizacion.php');
        exit;
    }

    $pdo->beginTransaction();

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

    if ($nuevoEstado === 'aceptada') {
        $stmt = $pdo->prepare("
            UPDATE desafio
            SET estado = 'cerrado'
            WHERE id_desafio = :id_desafio
              AND id_organizacion = :id_organizacion
        ");
        $stmt->execute([
            ':id_desafio' => $propuesta['id_desafio'],
            ':id_organizacion' => $idOrganizacion
        ]);

        $stmt = $pdo->prepare("
            SELECT id_conversacion
            FROM conversacion
            WHERE id_propuesta = :id_propuesta
            LIMIT 1
        ");
        $stmt->execute([':id_propuesta' => $idPropuesta]);
        $idConversacion = $stmt->fetchColumn();

        if ($idConversacion) {
            $stmt = $pdo->prepare("
                UPDATE conversacion
                SET activa = TRUE
                WHERE id_conversacion = :id_conversacion
            ");
            $stmt->execute([':id_conversacion' => $idConversacion]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO conversacion (
                    fecha_inicio,
                    activa,
                    id_propuesta
                )
                VALUES (
                    CURRENT_TIMESTAMP,
                    TRUE,
                    :id_propuesta
                )
            ");
            $stmt->execute([':id_propuesta' => $idPropuesta]);
        }
    } else {
        $stmt = $pdo->prepare("
            UPDATE conversacion
            SET activa = FALSE
            WHERE id_propuesta = :id_propuesta
        ");
        $stmt->execute([':id_propuesta' => $idPropuesta]);
    }

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
        ':mensaje' => 'Tu propuesta para el desafÃ­o "' . $propuesta['titulo_desafio'] . '" cambiÃ³ a "' . $nuevoEstado . '". ID_PROPUESTA:' . $idPropuesta,
        ':id_usuario' => $propuesta['id_estudiante']
    ]);

    $pdo->commit();

    $_SESSION['success'] = 'Propuesta actualizada correctamente.';
    header('Location: ../organizacion/propuestas_organizacion.php');
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error'] = 'OcurriÃ³ un error al actualizar la propuesta.';
    header('Location: ../organizacion/propuestas_organizacion.php');
    exit;
}
