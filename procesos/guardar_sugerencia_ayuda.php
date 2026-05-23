<?php
session_start();
require_once '../config/conexion.php';
require_once '../includes/csrf.php';
require_once '../includes/help_schema.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../ayuda.php');
    exit;
}

edunexo_require_csrf('../ayuda.php');

edunexo_ensure_help_tables($pdo);

$idUsuario = (int) $_SESSION['usuario_id'];
$tipo = trim($_POST['tipo'] ?? '');
$titulo = trim($_POST['titulo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$categoriaHabilidad = trim($_POST['categoria_habilidad'] ?? '');

$tiposValidos = ['habilidad', 'categoria', 'ayuda_general'];

$_SESSION['old_ayuda'] = [
    'tipo' => $tipo,
    'titulo' => $titulo,
    'descripcion' => $descripcion,
    'categoria_habilidad' => $categoriaHabilidad
];

if (!in_array($tipo, $tiposValidos, true) || $titulo === '') {
    $_SESSION['error'] = 'Selecciona un tipo de sugerencia y escribe un titulo.';
    header('Location: ../ayuda.php');
    exit;
}

if (mb_strlen($titulo) > 150 || mb_strlen($categoriaHabilidad) > 100 || mb_strlen($descripcion) > 2000) {
    $_SESSION['error'] = 'La sugerencia excede la longitud permitida.';
    header('Location: ../ayuda.php');
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO ayuda_sugerencia (
            tipo,
            titulo,
            descripcion,
            categoria_habilidad,
            id_usuario
        )
        VALUES (
            :tipo,
            :titulo,
            :descripcion,
            :categoria_habilidad,
            :id_usuario
        )
    ");
    $stmt->execute([
        ':tipo' => $tipo,
        ':titulo' => $titulo,
        ':descripcion' => $descripcion !== '' ? $descripcion : null,
        ':categoria_habilidad' => $categoriaHabilidad !== '' ? $categoriaHabilidad : null,
        ':id_usuario' => $idUsuario
    ]);

    unset($_SESSION['old_ayuda']);
    $_SESSION['success'] = 'Tu sugerencia fue enviada para revision.';
} catch (Throwable $e) {
    $_SESSION['error'] = 'No se pudo enviar la sugerencia.';
}

header('Location: ../ayuda.php');
exit;
