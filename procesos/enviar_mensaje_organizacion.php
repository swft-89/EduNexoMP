<?php
session_start();
require_once '../config/conexion.php';
require_once '../includes/csrf.php';

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'organizacion') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../organizacion/chat_organizacion.php');
    exit;
}

edunexo_require_csrf('../organizacion/chat_organizacion.php');

$idUsuario = (int) $_SESSION['usuario_id'];
$idConversacion = isset($_POST['id_conversacion']) ? (int) $_POST['id_conversacion'] : 0;
$contenido = trim($_POST['contenido'] ?? '');

if ($idConversacion <= 0 || $contenido === '') {
    $_SESSION['error'] = 'No se pudo enviar el mensaje.';
    header('Location: ../organizacion/chat_organizacion.php?id=' . $idConversacion);
    exit;
}

$stmt = $pdo->prepare("
    SELECT p.id_estudiante, d.titulo
    FROM conversacion c
    INNER JOIN propuesta p
        ON c.id_propuesta = p.id_propuesta
    INNER JOIN desafio d
        ON p.id_desafio = d.id_desafio
    WHERE c.id_conversacion = :id_conversacion
      AND d.id_organizacion = :id_organizacion
      AND c.activa = TRUE
      AND LOWER(COALESCE(p.estado, '')) = 'aceptada'
    LIMIT 1
");
$stmt->execute([
    ':id_conversacion' => $idConversacion,
    ':id_organizacion' => $idUsuario
]);
$conversacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$conversacion) {
    $_SESSION['error'] = 'Conversación no válida.';
    header('Location: ../organizacion/chat_organizacion.php');
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO mensaje (
            contenido,
            id_conversacion,
            id_emisor
        )
        VALUES (
            :contenido,
            :id_conversacion,
            :id_emisor
        )
    ");
    $stmt->execute([
        ':contenido' => $contenido,
        ':id_conversacion' => $idConversacion,
        ':id_emisor' => $idUsuario
    ]);

    $stmt = $pdo->prepare("
        INSERT INTO notificacion (
            tipo,
            mensaje,
            id_usuario
        )
        VALUES (
            'mensaje_chat',
            :mensaje,
            :id_usuario
        )
    ");
    $stmt->execute([
        ':mensaje' => 'Tienes un nuevo mensaje sobre el desafío "' . $conversacion['titulo'] . '". ID_CONVERSACION:' . $idConversacion,
        ':id_usuario' => $conversacion['id_estudiante']
    ]);

    header('Location: ../organizacion/chat_organizacion.php?id=' . $idConversacion);
    exit;
} catch (PDOException $e) {
    $_SESSION['error'] = 'No se pudo enviar el mensaje.';
    header('Location: ../organizacion/chat_organizacion.php?id=' . $idConversacion);
    exit;
}
