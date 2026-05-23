<?php
session_start();
require_once '../../config/conexion.php';
require_once '../../includes/csrf.php';

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'estudiante') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard_estudiante.php');
    exit;
}

edunexo_require_csrf('../../estudiante/habilidades_estudiante.php');

$idEstudiante = (int) $_SESSION['usuario_id'];
$habilidadesSeleccionadas = $_POST['habilidades'] ?? [];
$niveles = $_POST['nivel'] ?? [];

/* Validar que exista el estudiante */
$stmt = $pdo->prepare("
    SELECT 1
    FROM estudiante
    WHERE id_estudiante = :id_estudiante
    LIMIT 1
");
$stmt->execute([':id_estudiante' => $idEstudiante]);

if (!$stmt->fetchColumn()) {
    $_SESSION['error'] = 'Estudiante no válido.';
    header('Location: ../../estudiante/dashboard_estudiante.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        DELETE FROM estudiante_habilidad
        WHERE id_estudiante = :id_estudiante
    ");
    $stmt->execute([':id_estudiante' => $idEstudiante]);

    if (!empty($habilidadesSeleccionadas)) {
        $stmt = $pdo->prepare("
            INSERT INTO estudiante_habilidad (
                id_estudiante,
                id_habilidad,
                nivel
            )
            VALUES (
                :id_estudiante,
                :id_habilidad,
                :nivel
            )
        ");

        foreach ($habilidadesSeleccionadas as $idHabilidad) {
            $idHabilidad = (int) $idHabilidad;
            $nivel = trim($niveles[$idHabilidad] ?? '');

            $stmt->execute([
                ':id_estudiante' => $idEstudiante,
                ':id_habilidad' => $idHabilidad,
                ':nivel' => $nivel !== '' ? $nivel : null
            ]);
        }
    }

    $pdo->commit();

    $_SESSION['success'] = 'Tus habilidades fueron actualizadas correctamente.';
    header('Location: ../../estudiante/habilidades_estudiante.php');
    exit;
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error'] = 'No se pudieron guardar tus habilidades.';
    header('Location: ../../estudiante/habilidades_estudiante.php');
    exit;
}
