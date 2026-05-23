<?php
session_start();
require_once '../config/conexion.php';
require_once '../includes/csrf.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../estudiante/chat.php');
    exit;
}

edunexo_require_csrf('../estudiante/chat.php');

$idUsuario = (int) $_SESSION['usuario_id'];
$idConversacion = isset($_POST['id_conversacion']) ? (int) $_POST['id_conversacion'] : 0;
$contenido = trim($_POST['contenido'] ?? '');

if ($idConversacion <= 0 || $contenido === '') {
    $_SESSION['error'] = 'No se pudo enviar el mensaje.';
    header('Location: ../estudiante/chat.php?id=' . $idConversacion);
    exit;
}

/* Validar que la conversación sí pertenezca al estudiante */
$stmt = $pdo->prepare("
    SELECT d.id_organizacion, d.titulo
    FROM conversacion c
    INNER JOIN propuesta p
        ON c.id_propuesta = p.id_propuesta
    INNER JOIN desafio d
        ON p.id_desafio = d.id_desafio
    WHERE c.id_conversacion = :id_conversacion
      AND p.id_estudiante = :id_estudiante
      AND c.activa = TRUE
      AND LOWER(COALESCE(p.estado, '')) = 'aceptada'
    LIMIT 1
");
$stmt->execute([
    ':id_conversacion' => $idConversacion,
    ':id_estudiante' => $idUsuario
]);
$conversacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$conversacion) {
    $_SESSION['error'] = 'Conversación no válida.';
    header('Location: ../estudiante/chat.php');
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
        ':id_usuario' => $conversacion['id_organizacion']
    ]);

    header('Location: ../estudiante/chat.php?id=' . $idConversacion);
    exit;
} catch (PDOException $e) {
    $_SESSION['error'] = 'No se pudo enviar el mensaje.';
    header('Location: ../estudiante/chat.php?id=' . $idConversacion);
    exit;
}
