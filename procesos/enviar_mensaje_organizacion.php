<?php
session_start();
require_once '../config/conexion.php';

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'organizacion') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../organizacion/chat_organizacion.php');
    exit;
}

$idUsuario = (int) $_SESSION['usuario_id'];
$idConversacion = isset($_POST['id_conversacion']) ? (int) $_POST['id_conversacion'] : 0;
$contenido = trim($_POST['contenido'] ?? '');

if ($idConversacion <= 0 || $contenido === '') {
    $_SESSION['error'] = 'No se pudo enviar el mensaje.';
    header('Location: ../organizacion/chat_organizacion.php?id=' . $idConversacion);
    exit;
}

$stmt = $pdo->prepare("
    SELECT 1
    FROM conversacion c
    INNER JOIN propuesta p
        ON c.id_propuesta = p.id_propuesta
    INNER JOIN desafio d
        ON p.id_desafio = d.id_desafio
    WHERE c.id_conversacion = :id_conversacion
      AND d.id_organizacion = :id_organizacion
    LIMIT 1
");
$stmt->execute([
    ':id_conversacion' => $idConversacion,
    ':id_organizacion' => $idUsuario
]);

if (!$stmt->fetchColumn()) {
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

    header('Location: ../organizacion/chat_organizacion.php?id=' . $idConversacion);
    exit;
} catch (PDOException $e) {
    $_SESSION['error'] = 'No se pudo enviar el mensaje.';
    header('Location: ../organizacion/chat_organizacion.php?id=' . $idConversacion);
    exit;
}