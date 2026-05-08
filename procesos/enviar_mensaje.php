<?php
session_start();
require_once '../config/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../estudiante/chat.php');
    exit;
}

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
    SELECT 1
    FROM conversacion c
    INNER JOIN propuesta p
        ON c.id_propuesta = p.id_propuesta
    WHERE c.id_conversacion = :id_conversacion
      AND p.id_estudiante = :id_estudiante
    LIMIT 1
");
$stmt->execute([
    ':id_conversacion' => $idConversacion,
    ':id_estudiante' => $idUsuario
]);

if (!$stmt->fetchColumn()) {
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

    header('Location: ../estudiante/chat.php?id=' . $idConversacion);
    exit;
} catch (PDOException $e) {
    $_SESSION['error'] = 'No se pudo enviar el mensaje.';
    header('Location: ../estudiante/chat.php?id=' . $idConversacion);
    exit;
}