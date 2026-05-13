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
$tituloPropuesta = trim($_POST['titulo_propuesta'] ?? '');
$descripcionBreve = trim($_POST['descripcion_breve'] ?? '');
$enlacePortafolio = trim($_POST['enlace_portafolio'] ?? '');

$_SESSION['old_propuesta_edit'] = [
    'titulo_propuesta' => $tituloPropuesta,
    'descripcion_breve' => $descripcionBreve,
    'enlace_portafolio' => $enlacePortafolio
];

$errores = [];

if ($idPropuesta <= 0) {
    $errores[] = 'Propuesta no vÃ¡lida.';
}

if ($tituloPropuesta === '') {
    $errores[] = 'Debes agregar un tÃ­tulo para tu propuesta.';
} elseif (mb_strlen($tituloPropuesta) < 6) {
    $errores[] = 'El tÃ­tulo de la propuesta es demasiado corto.';
}

if ($enlacePortafolio !== '' && !filter_var($enlacePortafolio, FILTER_VALIDATE_URL)) {
    $errores[] = 'El enlace de portafolio debe ser una URL vÃ¡lida.';
}

$hayArchivoNuevo = !empty($_FILES['archivo_propuesta']['name']);

if ($hayArchivoNuevo) {
    $archivo = $_FILES['archivo_propuesta'];

    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        $errores[] = 'OcurriÃ³ un error al subir el archivo.';
    }

    if ($archivo['size'] > 5 * 1024 * 1024) {
        $errores[] = 'El archivo no puede superar los 5 MB.';
    }

    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $extensionesPermitidas = ['pdf', 'ppt', 'pptx'];

    if (!in_array($extension, $extensionesPermitidas, true)) {
        $errores[] = 'Solo se permiten archivos PDF, PPT o PPTX.';
    }
}

if (!empty($errores)) {
    $_SESSION['error_form'] = $errores;
    header('Location: ../../estudiante/editar_propuesta.php?id=' . $idPropuesta);
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
        throw new RuntimeException('Solo puedes editar propuestas que aÃºn estÃ¡n en revisiÃ³n.');
    }

    $stmt = $pdo->prepare("
        UPDATE propuesta
        SET
            titulo_propuesta = :titulo_propuesta,
            descripcion_breve = :descripcion_breve,
            enlace_portafolio = :enlace_portafolio
        WHERE id_propuesta = :id_propuesta
          AND id_estudiante = :id_estudiante
    ");
    $stmt->execute([
        ':titulo_propuesta' => $tituloPropuesta,
        ':descripcion_breve' => $descripcionBreve !== '' ? $descripcionBreve : null,
        ':enlace_portafolio' => $enlacePortafolio !== '' ? $enlacePortafolio : null,
        ':id_propuesta' => $idPropuesta,
        ':id_estudiante' => $idEstudiante
    ]);

    $archivosAnteriores = [];

    if ($hayArchivoNuevo) {
        $stmt = $pdo->prepare("
            SELECT url_archivo
            FROM documento_propuesta
            WHERE id_propuesta = :id_propuesta
        ");
        $stmt->execute([':id_propuesta' => $idPropuesta]);
        $archivosAnteriores = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $pdo->prepare("
            DELETE FROM documento_propuesta
            WHERE id_propuesta = :id_propuesta
        ");
        $stmt->execute([':id_propuesta' => $idPropuesta]);

        $archivo = $_FILES['archivo_propuesta'];
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $carpetaDestino = '../../uploads/propuestas/';

        if (!is_dir($carpetaDestino)) {
            mkdir($carpetaDestino, 0777, true);
        }

        $nombreSeguro = 'propuesta_' . $idPropuesta . '_' . time() . '.' . $extension;
        $rutaDestino = $carpetaDestino . $nombreSeguro;
        $rutaBD = 'uploads/propuestas/' . $nombreSeguro;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            throw new RuntimeException('No se pudo guardar el nuevo archivo de la propuesta.');
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
    }

    $pdo->commit();

    foreach ($archivosAnteriores as $archivoAnterior) {
        edunexo_unlink_uploaded_proposal_file($archivoAnterior);
    }

    unset($_SESSION['old_propuesta_edit'], $_SESSION['error_form']);
    $_SESSION['success'] = 'Propuesta actualizada correctamente.';
    header('Location: ../../estudiante/mis_propuestas.php');
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error_form'] = [$e->getMessage()];
    header('Location: ../../estudiante/editar_propuesta.php?id=' . $idPropuesta);
    exit;
}
