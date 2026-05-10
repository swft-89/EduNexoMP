<?php
session_start();
require_once '../config/conexion.php';

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'estudiante') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../estudiante/dashboard_estudiante.php');
    exit;
}

$idEstudiante = (int) $_SESSION['usuario_id'];
$idDesafio = isset($_POST['id_desafio']) ? (int) $_POST['id_desafio'] : 0;

$tituloPropuesta = trim($_POST['titulo_propuesta'] ?? '');
$descripcionBreve = trim($_POST['descripcion_breve'] ?? '');
$enlacePortafolio = trim($_POST['enlace_portafolio'] ?? '');

$errores = [];

$_SESSION['old_propuesta'] = [
    'titulo_propuesta' => $tituloPropuesta,
    'descripcion_breve' => $descripcionBreve,
    'enlace_portafolio' => $enlacePortafolio
];

if ($idDesafio <= 0) {
    $errores[] = 'Desafío no válido.';
}

if ($tituloPropuesta === '') {
    $errores[] = 'Debes agregar un título para tu propuesta.';
} elseif (mb_strlen($tituloPropuesta) < 6) {
    $errores[] = 'El título de la propuesta es demasiado corto.';
}

if ($enlacePortafolio !== '' && !filter_var($enlacePortafolio, FILTER_VALIDATE_URL)) {
    $errores[] = 'El enlace de portafolio debe ser una URL válida.';
}

if (empty($_FILES['archivo_propuesta']['name'])) {
    $errores[] = 'Debes subir un archivo de propuesta.';
} else {
    $archivo = $_FILES['archivo_propuesta'];

    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        $errores[] = 'Ocurrió un error al subir el archivo.';
    }

    if ($archivo['size'] > 5 * 1024 * 1024) {
        $errores[] = 'El archivo no puede superar los 5 MB.';
    }

    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $extensionesPermitidas = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];

    if (!in_array($extension, $extensionesPermitidas, true)) {
        $errores[] = 'El formato del archivo no está permitido.';
    }
}

if (!empty($errores)) {
    $_SESSION['error_form'] = $errores;
    header('Location: ../estudiante/crear_propuesta.php?id=' . $idDesafio);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT 1
        FROM desafio
        WHERE id_desafio = :id_desafio
          AND LOWER(COALESCE(estado, '')) = 'activo'
        LIMIT 1
    ");
    $stmt->execute([
        ':id_desafio' => $idDesafio
    ]);

    if (!$stmt->fetchColumn()) {
        throw new Exception('Este desafío no está disponible para recibir propuestas.');
    }

    $stmt = $pdo->prepare("
        SELECT 1
        FROM propuesta
        WHERE id_estudiante = :id_estudiante
          AND id_desafio = :id_desafio
        LIMIT 1
    ");
    $stmt->execute([
        ':id_estudiante' => $idEstudiante,
        ':id_desafio' => $idDesafio
    ]);

    if ($stmt->fetchColumn()) {
        throw new Exception('Ya enviaste una propuesta para este desafío.');
    }

    $stmt = $pdo->prepare("
        INSERT INTO propuesta (
            fecha_envio,
            estado,
            titulo_propuesta,
            descripcion_breve,
            enlace_portafolio,
            id_estudiante,
            id_desafio
        )
        VALUES (
            CURRENT_TIMESTAMP,
            'en revisión',
            :titulo_propuesta,
            :descripcion_breve,
            :enlace_portafolio,
            :id_estudiante,
            :id_desafio
        )
        RETURNING id_propuesta
    ");

    $stmt->execute([
        ':titulo_propuesta' => $tituloPropuesta,
        ':descripcion_breve' => $descripcionBreve !== '' ? $descripcionBreve : null,
        ':enlace_portafolio' => $enlacePortafolio !== '' ? $enlacePortafolio : null,
        ':id_estudiante' => $idEstudiante,
        ':id_desafio' => $idDesafio
    ]);

    $idPropuesta = (int) $stmt->fetchColumn();

    $archivo = $_FILES['archivo_propuesta'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

    $carpetaDestino = '../uploads/propuestas/';

    if (!is_dir($carpetaDestino)) {
        mkdir($carpetaDestino, 0777, true);
    }

    $nombreSeguro = 'propuesta_' . $idPropuesta . '_' . time() . '.' . $extension;
    $rutaDestino = $carpetaDestino . $nombreSeguro;
    $rutaBD = 'uploads/propuestas/' . $nombreSeguro;

    if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
        throw new Exception('No se pudo guardar el archivo de la propuesta.');
    }

    $stmt = $pdo->prepare("
        INSERT INTO documento_propuesta (
            nombre_archivo,
            tipo_archivo,
            url_archivo,
            fecha_subida,
            tamano_bytes,
            id_propuesta
        )
        VALUES (
            :nombre_archivo,
            :tipo_archivo,
            :url_archivo,
            CURRENT_TIMESTAMP,
            :tamano_bytes,
            :id_propuesta
        )
    ");

    $stmt->execute([
        ':nombre_archivo' => $archivo['name'],
        ':tipo_archivo' => $archivo['type'],
        ':url_archivo' => $rutaBD,
        ':tamano_bytes' => $archivo['size'],
        ':id_propuesta' => $idPropuesta
    ]);

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

    $stmt->execute([
        ':id_propuesta' => $idPropuesta
    ]);

    $pdo->commit();

    unset($_SESSION['old_propuesta'], $_SESSION['error_form']);

    $_SESSION['success'] = 'Tu propuesta fue enviada correctamente.';
    header('Location: ../estudiante/mis_propuestas.php');
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error_form'] = [$e->getMessage()];
    header('Location: ../estudiante/crear_propuesta.php?id=' . $idDesafio);
    exit;
}