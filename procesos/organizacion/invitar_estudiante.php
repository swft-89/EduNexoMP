<?php
session_start();
require_once '../../config/conexion.php';
require_once '../../includes/csrf.php';

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'organizacion') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../organizacion/dashboard_organizacion.php');
    exit;
}

edunexo_require_csrf('../../organizacion/dashboard_organizacion.php');

$idOrganizacion = (int) $_SESSION['usuario_id'];
$idDesafio = isset($_POST['id_desafio']) ? (int) $_POST['id_desafio'] : 0;
$idEstudiante = isset($_POST['id_estudiante']) ? (int) $_POST['id_estudiante'] : 0;

if ($idDesafio <= 0 || $idEstudiante <= 0) {
    $_SESSION['error'] = 'No se pudo enviar la invitación.';
    header('Location: ../../organizacion/dashboard_organizacion.php');
    exit;
}

try {
    /*
        Verificar que el desafío pertenezca a la organización
    */
    $stmt = $pdo->prepare("
        SELECT 
            d.titulo,
            o.nombre_empresa
        FROM desafio d
        INNER JOIN organizacion o
            ON d.id_organizacion = o.id_organizacion
        WHERE d.id_desafio = :id_desafio
          AND d.id_organizacion = :id_organizacion
        LIMIT 1
    ");
    $stmt->execute([
        ':id_desafio' => $idDesafio,
        ':id_organizacion' => $idOrganizacion
    ]);
    $desafio = $stmt->fetch();

    if (!$desafio) {
        $_SESSION['error'] = 'No tienes permiso para invitar a este desafío.';
        header('Location: ../../organizacion/dashboard_organizacion.php');
        exit;
    }

    /*
        Verificar que el estudiante exista
    */
    $stmt = $pdo->prepare("
        SELECT 1
        FROM estudiante
        WHERE id_estudiante = :id_estudiante
        LIMIT 1
    ");
    $stmt->execute([
        ':id_estudiante' => $idEstudiante
    ]);

    if (!$stmt->fetchColumn()) {
        $_SESSION['error'] = 'Estudiante no encontrado.';
        header('Location: ../../organizacion/desafios/talentos_desafio.php?id=' . $idDesafio);
        exit;
    }

    /*
        Evitar invitar si ya se postuló
    */
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
        $_SESSION['error'] = 'Este estudiante ya se postuló a este desafío.';
        header('Location: ../../organizacion/desafios/talentos_desafio.php?id=' . $idDesafio);
        exit;
    }

    /*
        Evitar invitación duplicada reciente/existente
    */
    $stmt = $pdo->prepare("
        SELECT 1
        FROM notificacion
        WHERE id_usuario = :id_estudiante
          AND tipo = 'invitacion_desafio'
          AND mensaje LIKE :patron
          AND leida = FALSE
        LIMIT 1
    ");
    $stmt->execute([
        ':id_estudiante' => $idEstudiante,
        ':patron' => '%ID_DESAFIO:' . $idDesafio . '%'
    ]);

    if ($stmt->fetchColumn()) {
        $_SESSION['error'] = 'Ya existe una invitación pendiente para este estudiante.';
        header('Location: ../../organizacion/desafios/talentos_desafio.php?id=' . $idDesafio);
        exit;
    }

    $mensaje = 'La organización ' . $desafio['nombre_empresa'] .
        ' te invitó a postularte al desafío "' . $desafio['titulo'] .
        '". ID_DESAFIO:' . $idDesafio;

    $stmt = $pdo->prepare("
        INSERT INTO notificacion (
            tipo,
            mensaje,
            id_usuario
        )
        VALUES (
            :tipo,
            :mensaje,
            :id_usuario
        )
    ");
    $stmt->execute([
        ':tipo' => 'invitacion_desafio',
        ':mensaje' => $mensaje,
        ':id_usuario' => $idEstudiante
    ]);

    $_SESSION['success'] = 'Invitación enviada correctamente.';
    header('Location: ../../organizacion/desafios/talentos_desafio.php?id=' . $idDesafio);
    exit;

} catch (PDOException $e) {
    $_SESSION['error'] = 'No se pudo enviar la invitación.';
    header('Location: ../../organizacion/desafios/talentos_desafio.php?id=' . $idDesafio);
    exit;
}
