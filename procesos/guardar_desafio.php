<?php
session_start();
require_once '../config/conexion.php';

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'organizacion') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard_organizacion.php');
    exit;
}

$idOrganizacion = (int) $_SESSION['usuario_id'];

$titulo = trim($_POST['titulo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$requisitos = trim($_POST['requisitos_especificos'] ?? '');
$modalidad = trim($_POST['modalidad'] ?? '');
$estado = trim($_POST['estado'] ?? 'activo');
$idCategoria = (int) ($_POST['id_categoria'] ?? 0);
$fechaLimite = trim($_POST['fecha_limite'] ?? '');

$errores = [];
$erroresCampos = [];

if ($titulo === '') {
    $errores[] = 'El título del desafío es obligatorio.';
    $erroresCampos['titulo'] = true;
}

if ($descripcion === '') {
    $errores[] = 'La descripción es obligatoria.';
    $erroresCampos['descripcion'] = true;
}

if ($idCategoria <= 0) {
    $errores[] = 'Debes seleccionar una categoría.';
    $erroresCampos['id_categoria'] = true;
}

if ($estado === '') {
    $errores[] = 'Debes seleccionar un estado.';
    $erroresCampos['estado'] = true;
}

if ($fechaLimite !== '') {
    $hoy = date('Y-m-d');
    if ($fechaLimite < $hoy) {
        $errores[] = 'La fecha límite no puede ser anterior al día de hoy.';
        $erroresCampos['fecha_limite'] = true;
    }
}

$_SESSION['old_crear_desafio'] = [
    'titulo' => $titulo,
    'descripcion' => $descripcion,
    'requisitos_especificos' => $requisitos,
    'modalidad' => $modalidad,
    'estado' => $estado,
    'id_categoria' => $idCategoria,
    'fecha_limite' => $fechaLimite
];

if (!empty($errores)) {
    $_SESSION['error_form'] = $errores;
    $_SESSION['error_fields'] = $erroresCampos;
    header('Location: ../crear_desafio.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $stmtOrg = $pdo->prepare("
        SELECT nombre_empresa
        FROM organizacion
        WHERE id_organizacion = :id_organizacion
        LIMIT 1
    ");
    $stmtOrg->execute([
        ':id_organizacion' => $idOrganizacion
    ]);

    $organizacion = $stmtOrg->fetch(PDO::FETCH_ASSOC);

    if (!$organizacion) {
        throw new Exception('No se encontró la organización.');
    }

    $stmt = $pdo->prepare("
        INSERT INTO desafio (
            titulo,
            descripcion,
            fecha_limite,
            estado,
            requisitos_especificos,
            modalidad,
            id_organizacion,
            id_categoria
        )
        VALUES (
            :titulo,
            :descripcion,
            :fecha_limite,
            :estado,
            :requisitos_especificos,
            :modalidad,
            :id_organizacion,
            :id_categoria
        )
        RETURNING id_desafio
    ");
    $stmt->execute([
        ':titulo' => $titulo,
        ':descripcion' => $descripcion,
        ':fecha_limite' => $fechaLimite !== '' ? $fechaLimite : null,
        ':estado' => $estado,
        ':requisitos_especificos' => $requisitos !== '' ? $requisitos : null,
        ':modalidad' => $modalidad !== '' ? $modalidad : null,
        ':id_organizacion' => $idOrganizacion,
        ':id_categoria' => $idCategoria
    ]);

    $idDesafio = (int) $stmt->fetchColumn();

    if ($idDesafio <= 0) {
        throw new Exception('No se pudo crear el desafío.');
    }

    $stmtAudit = $pdo->prepare("
        INSERT INTO auditoria_admin (
            tipo_evento,
            descripcion,
            usuario_nombre,
            usuario_rol,
            id_usuario_relacionado
        ) VALUES (
            :tipo_evento,
            :descripcion,
            :usuario_nombre,
            :usuario_rol,
            :id_usuario_relacionado
        )
    ");
    $stmtAudit->execute([
        ':tipo_evento' => 'Nuevo desafío',
        ':descripcion' => 'Se publicó el desafío "' . $titulo . '"',
        ':usuario_nombre' => $organizacion['nombre_empresa'] ?? 'Organización',
        ':usuario_rol' => 'Org.',
        ':id_usuario_relacionado' => $idOrganizacion
    ]);

    $pdo->commit();

    unset($_SESSION['old_crear_desafio'], $_SESSION['error_form'], $_SESSION['error_fields']);

    $_SESSION['success'] = 'Desafío creado correctamente.';
    header('Location: ../dashboard_organizacion.php');
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error_form'] = ['No se pudo crear el desafío. Intenta nuevamente.'];
    $_SESSION['error_fields'] = [];
    header('Location: ../crear_desafio.php');
    exit;
}