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
$idDesafio = (int) ($_POST['id_desafio'] ?? 0);
$habilidadesSeleccionadas = $_POST['habilidades'] ?? [];
$niveles = $_POST['nivel_requerido'] ?? [];
$obligatorios = $_POST['obligatorio'] ?? [];

if ($idDesafio <= 0) {
    $_SESSION['error'] = 'Desafío no válido.';
    header('Location: ../dashboard_organizacion.php');
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
    $_SESSION['error'] = 'No tienes permiso para modificar este desafío.';
    header('Location: ../dashboard_organizacion.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        DELETE FROM desafio_habilidad
        WHERE id_desafio = :id_desafio
    ");
    $stmt->execute([':id_desafio' => $idDesafio]);

    if (!empty($habilidadesSeleccionadas)) {
        $stmt = $pdo->prepare("
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

            $stmt->execute([
                ':id_desafio' => $idDesafio,
                ':id_habilidad' => $idHabilidad,
                ':nivel_requerido' => $nivel !== '' ? $nivel : null,
                ':obligatorio' => $obligatorio
            ]);
        }
    }

    $pdo->commit();

    $_SESSION['success'] = 'Habilidades del desafío actualizadas.';
    header('Location: ../detalle_desafio_organizacion.php?id=' . $idDesafio);
    exit;
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error'] = 'No se pudieron guardar las habilidades.';
    header('Location: ../habilidades_desafio.php?id=' . $idDesafio);
    exit;
}