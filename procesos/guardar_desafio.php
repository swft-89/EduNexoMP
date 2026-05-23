<?php
session_start();
require_once '../config/conexion.php';
require_once '../includes/csrf.php';

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'organizacion') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard_organizacion.php');
    exit;
}

edunexo_require_csrf('../organizacion/desafios/crear_desafio.php');

$idOrganizacion = (int) $_SESSION['usuario_id'];

$titulo = trim($_POST['titulo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$requisitos = trim($_POST['requisitos_especificos'] ?? '');
$modalidad = trim($_POST['modalidad'] ?? '');
$estado = trim($_POST['estado'] ?? 'activo');
$idCategoria = (int) ($_POST['id_categoria'] ?? 0);
$fechaLimite = trim($_POST['fecha_limite'] ?? '');

$habilidadesSeleccionadas = array_unique(array_map('intval', $_POST['habilidades'] ?? []));
$niveles = $_POST['nivel_requerido'] ?? [];
$obligatorios = $_POST['obligatorio'] ?? [];

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
    'fecha_limite' => $fechaLimite,
    'habilidades' => $habilidadesSeleccionadas,
    'nivel_requerido' => $niveles,
    'obligatorio' => $obligatorios
];

if (!empty($errores)) {
    $_SESSION['error_form'] = $errores;
    $_SESSION['error_fields'] = $erroresCampos;
    header('Location: ../organizacion/desafios/crear_desafio.php');
    exit;
}

try {
    $pdo->beginTransaction();

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

    $idDesafioCreado = (int) $stmt->fetchColumn();

    if (!empty($habilidadesSeleccionadas)) {
        $stmtHab = $pdo->prepare("
            INSERT INTO desafio_habilidad (
                id_desafio,
                id_habilidad,
                nivel_requerido,
                obligatorio
            )
            VALUES (
                :id_desafio,
                :id_habilidad,
                :nivel_requerido,
                :obligatorio
            )
        ");

        foreach ($habilidadesSeleccionadas as $idHabilidad) {
            $idHabilidad = (int) $idHabilidad;
            $nivel = trim($niveles[$idHabilidad] ?? '');
            $obligatorio = !empty($obligatorios[$idHabilidad]) ? 1 : 0;

            $stmtHab->execute([
                ':id_desafio' => $idDesafioCreado,
                ':id_habilidad' => $idHabilidad,
                ':nivel_requerido' => $nivel !== '' ? $nivel : null,
                ':obligatorio' => $obligatorio
            ]);
        }
    }

    $pdo->commit();

    unset($_SESSION['old_crear_desafio'], $_SESSION['error_form'], $_SESSION['error_fields']);

    $_SESSION['success'] = 'Desafío creado correctamente.';
    header('Location: ../organizacion/desafios/detalle_desafio_organizacion.php?id=' . $idDesafioCreado);
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error_form'] = ['No se pudo crear el desafío. Intenta nuevamente.'];
    $_SESSION['error_fields'] = [];
    header('Location: ../organizacion/desafios/crear_desafio.php');
    exit;
}
