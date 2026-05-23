<?php
session_start();
require_once '../../config/conexion.php';
require_once '../../includes/csrf.php';
require_once '../../includes/help_schema.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'administrador') {
    header('Location: ../../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../ayuda.php');
    exit;
}

edunexo_require_csrf('../../ayuda.php');

edunexo_ensure_help_tables($pdo);

$idAdmin = (int) $_SESSION['usuario_id'];
$idSugerencia = (int) ($_POST['id_sugerencia'] ?? 0);
$accion = trim($_POST['accion'] ?? '');
$respuesta = trim($_POST['respuesta_admin'] ?? '');

if ($idSugerencia <= 0 || !in_array($accion, ['aprobar', 'rechazar'], true)) {
    $_SESSION['error'] = 'No se pudo revisar la sugerencia.';
    header('Location: ../../ayuda.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT *
        FROM ayuda_sugerencia
        WHERE id_sugerencia = :id_sugerencia
        LIMIT 1
    ");
    $stmt->execute([':id_sugerencia' => $idSugerencia]);
    $sugerencia = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sugerencia || ($sugerencia['estado'] ?? '') !== 'pendiente') {
        throw new RuntimeException('La sugerencia ya fue revisada o no existe.');
    }

    $nuevoEstado = $accion === 'aprobar' ? 'aprobada' : 'rechazada';

    if ($accion === 'aprobar') {
        if ($sugerencia['tipo'] === 'habilidad') {
            $stmtExiste = $pdo->prepare("
                SELECT id_habilidad
                FROM habilidad
                WHERE LOWER(nombre) = LOWER(:nombre)
                LIMIT 1
            ");
            $stmtExiste->execute([':nombre' => $sugerencia['titulo']]);

            if (!$stmtExiste->fetchColumn()) {
                $stmtInsert = $pdo->prepare("
                    INSERT INTO habilidad (nombre, categoria_habilidad)
                    VALUES (:nombre, :categoria_habilidad)
                ");
                $stmtInsert->execute([
                    ':nombre' => $sugerencia['titulo'],
                    ':categoria_habilidad' => !empty($sugerencia['categoria_habilidad'])
                        ? $sugerencia['categoria_habilidad']
                        : null
                ]);
            }
        }

        if ($sugerencia['tipo'] === 'categoria') {
            $stmtExiste = $pdo->prepare("
                SELECT id_categoria
                FROM categoria
                WHERE LOWER(nombre_categoria) = LOWER(:nombre)
                LIMIT 1
            ");
            $stmtExiste->execute([':nombre' => $sugerencia['titulo']]);

            if (!$stmtExiste->fetchColumn()) {
                $stmtInsert = $pdo->prepare("
                    INSERT INTO categoria (nombre_categoria, descripcion_categoria)
                    VALUES (:nombre_categoria, :descripcion_categoria)
                ");
                $stmtInsert->execute([
                    ':nombre_categoria' => $sugerencia['titulo'],
                    ':descripcion_categoria' => !empty($sugerencia['descripcion'])
                        ? $sugerencia['descripcion']
                        : null
                ]);
            }
        }
    }

    $stmt = $pdo->prepare("
        UPDATE ayuda_sugerencia
        SET
            estado = :estado,
            respuesta_admin = :respuesta_admin,
            revisado_por = :revisado_por,
            fecha_revision = CURRENT_TIMESTAMP
        WHERE id_sugerencia = :id_sugerencia
    ");
    $stmt->execute([
        ':estado' => $nuevoEstado,
        ':respuesta_admin' => $respuesta !== '' ? $respuesta : null,
        ':revisado_por' => $idAdmin,
        ':id_sugerencia' => $idSugerencia
    ]);

    if (!empty($sugerencia['id_usuario'])) {
        $stmt = $pdo->prepare("
            INSERT INTO notificacion (tipo, mensaje, id_usuario)
            VALUES ('ayuda_sugerencia', :mensaje, :id_usuario)
        ");
        $stmt->execute([
            ':mensaje' => 'Tu sugerencia "' . $sugerencia['titulo'] . '" fue ' . $nuevoEstado . '.',
            ':id_usuario' => $sugerencia['id_usuario']
        ]);
    }

    $pdo->commit();

    $_SESSION['success'] = $accion === 'aprobar'
        ? 'Sugerencia aprobada correctamente.'
        : 'Sugerencia rechazada correctamente.';
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error'] = $e->getMessage();
}

header('Location: ../../ayuda.php');
exit;
