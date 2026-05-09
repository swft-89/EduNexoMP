<?php
session_start();
require_once '../config/conexion.php';

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'organizacion') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../organizacion/dashboard_organizacion.php');
    exit;
}

$idOrganizacion = (int) $_SESSION['usuario_id'];
$idDesafio = (int) ($_POST['id_desafio'] ?? 0);

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

if ($idDesafio <= 0) {
    $_SESSION['error'] = 'Desafío no válido.';
    header('Location: ../organizacion/dashboard_organizacion.php');
    exit;
}

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

$_SESSION['old_editar_desafio'] = [
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
    header('Location: ../organizacion/desafios/editar_desafio.php?id=' . $idDesafio);
    exit;
}

$stmt = $pdo->prepare("
    SELECT 1
    FROM desafio
    WHERE id_desafio = :id_desafio
      AND id_organizacion = :id_organizacion
    LIMIT 1
");
$stmt->execute([
    ':id_desafio' => $idDesafio,
    ':id_organizacion' => $idOrganizacion
]);

if (!$stmt->fetchColumn()) {
    $_SESSION['error'] = 'No tienes permiso para editar este desafío.';
    header('Location: ../organizacion/dashboard_organizacion.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        UPDATE desafio
        SET titulo = :titulo,
            descripcion = :descripcion,
            fecha_limite = :fecha_limite,
            estado = :estado,
            requisitos_especificos = :requisitos_especificos,
            modalidad = :modalidad,
            id_categoria = :id_categoria
        WHERE id_desafio = :id_desafio
          AND id_organizacion = :id_organizacion
    ");
    $stmt->execute([
        ':titulo' => $titulo,
        ':descripcion' => $descripcion,
        ':fecha_limite' => $fechaLimite !== '' ? $fechaLimite : null,
        ':estado' => $estado,
        ':requisitos_especificos' => $requisitos !== '' ? $requisitos : null,
        ':modalidad' => $modalidad !== '' ? $modalidad : null,
        ':id_categoria' => $idCategoria,
        ':id_desafio' => $idDesafio,
        ':id_organizacion' => $idOrganizacion
    ]);

    $stmt = $pdo->prepare("
        DELETE FROM desafio_habilidad
        WHERE id_desafio = :id_desafio
    ");
    $stmt->execute([':id_desafio' => $idDesafio]);

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
            $nivel = trim($niveles[$idHabilidad] ?? '');
            $obligatorio = !empty($obligatorios[$idHabilidad]) ? 1 : 0;

            $stmtHab->execute([
                ':id_desafio' => $idDesafio,
                ':id_habilidad' => $idHabilidad,
                ':nivel_requerido' => $nivel !== '' ? $nivel : null,
                ':obligatorio' => $obligatorio
            ]);
        }
    }

    $pdo->commit();

    unset($_SESSION['old_editar_desafio'], $_SESSION['error_form'], $_SESSION['error_fields']);

    $_SESSION['success'] = 'Desafío actualizado correctamente.';
    header('Location: ../organizacion/desafios/detalle_desafio_organizacion.php?id=' . $idDesafio);
    exit;
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error'] = 'No se pudo actualizar el desafío.';
    header('Location: ../organizacion/desafios/editar_desafio.php?id=' . $idDesafio);
    exit;
}