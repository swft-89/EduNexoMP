<?php
session_start();
require_once '../config/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../index.php');
    exit;
}

$idUsuario = (int) $_SESSION['usuario_id'];
$rol = $_SESSION['rol'] ?? '';
$idNotificacion = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($idNotificacion <= 0) {
    header('Location: ../index.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT id_notificacion, tipo, mensaje
    FROM notificacion
    WHERE id_notificacion = :id_notificacion
      AND id_usuario = :id_usuario
    LIMIT 1
");
$stmt->execute([
    ':id_notificacion' => $idNotificacion,
    ':id_usuario' => $idUsuario
]);
$notificacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$notificacion) {
    header('Location: ../index.php');
    exit;
}

$stmt = $pdo->prepare("
    UPDATE notificacion
    SET leida = TRUE
    WHERE id_notificacion = :id_notificacion
      AND id_usuario = :id_usuario
");
$stmt->execute([
    ':id_notificacion' => $idNotificacion,
    ':id_usuario' => $idUsuario
]);

function extraerIdNotificacion(string $mensaje, string $clave): ?int
{
    if (preg_match('/' . preg_quote($clave, '/') . ':(\d+)/', $mensaje, $matches)) {
        return (int) $matches[1];
    }

    return null;
}

$tipo = $notificacion['tipo'] ?? '';
$mensaje = $notificacion['mensaje'] ?? '';
$redirect = '../index.php';

if ($tipo === 'invitacion_desafio') {
    $idDesafio = extraerIdNotificacion($mensaje, 'ID_DESAFIO');
    $redirect = $idDesafio
        ? '../estudiante/detalle_desafio.php?id=' . $idDesafio
        : '../estudiante/dashboard_estudiante.php';
} elseif ($tipo === 'propuesta_recibida') {
    $redirect = '../organizacion/propuestas_organizacion.php';
} elseif ($tipo === 'propuesta_actualizada') {
    $redirect = '../estudiante/mis_propuestas.php';
} elseif ($tipo === 'mensaje_chat') {
    $idConversacion = extraerIdNotificacion($mensaje, 'ID_CONVERSACION');

    if ($rol === 'organizacion') {
        $redirect = '../organizacion/chat_organizacion.php' . ($idConversacion ? '?id=' . $idConversacion : '');
    } else {
        $redirect = '../estudiante/chat.php' . ($idConversacion ? '?id=' . $idConversacion : '');
    }
} elseif ($rol === 'organizacion') {
    $redirect = '../organizacion/dashboard_organizacion.php';
} elseif ($rol === 'estudiante') {
    $redirect = '../estudiante/dashboard_estudiante.php';
} elseif ($rol === 'administrador') {
    $redirect = '../superadmin/dashboard_superadmin.php';
}

header('Location: ' . $redirect);
exit;
