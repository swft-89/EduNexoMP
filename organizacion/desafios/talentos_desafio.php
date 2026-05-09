<?php
require_once '../../includes/session_organizacion.php';
require_once '../../config/conexion.php';

$idUsuario = $_SESSION['usuario_id'];
$idDesafio = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($idDesafio <= 0) {
    header('Location: dashboard_organizacion.php');
    exit;
}

/* Validar desafío */
$stmt = $pdo->prepare("
    SELECT 
        d.id_desafio,
        d.titulo,
        d.descripcion,
        d.estado,
        c.nombre_categoria
    FROM desafio d
    INNER JOIN categoria c
        ON d.id_categoria = c.id_categoria
    WHERE d.id_desafio = :id_desafio
      AND d.id_organizacion = :id_organizacion
    LIMIT 1
");
$stmt->execute([
    ':id_desafio' => $idDesafio,
    ':id_organizacion' => $idUsuario
]);
$desafio = $stmt->fetch();

if (!$desafio) {
    $_SESSION['error'] = 'Desafío no encontrado.';
    header('Location: dashboard_organizacion.php');
    exit;
}

/* Total de habilidades requeridas */
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT id_habilidad)
    FROM desafio_habilidad
    WHERE id_desafio = :id_desafio
");
$stmt->execute([':id_desafio' => $idDesafio]);
$totalHabilidades = (int) $stmt->fetchColumn();

/* Talentos compatibles */
$stmt = $pdo->prepare("
    SELECT
        e.id_estudiante,
        e.nombre,
        e.apellido_paterno,
        e.apellido_materno,
        e.carrera,
        e.semestre,
        e.telefono,
        u.correo_electronico,

        COUNT(DISTINCT eh.id_habilidad) AS habilidades_coincidentes,

        CASE
            WHEN :total_habilidades = 0 THEN 0
            ELSE ROUND(
                COUNT(DISTINCT eh.id_habilidad) * 100.0 / :total_habilidades
            )
        END AS match_porcentaje

    FROM estudiante e
    INNER JOIN usuario u
        ON e.id_estudiante = u.id_usuario

    LEFT JOIN estudiante_habilidad eh
        ON e.id_estudiante = eh.id_estudiante
       AND eh.id_habilidad IN (
            SELECT id_habilidad
            FROM desafio_habilidad
            WHERE id_desafio = :id_desafio
       )

    WHERE u.estado = 'activo'

    GROUP BY
        e.id_estudiante,
        e.nombre,
        e.apellido_paterno,
        e.apellido_materno,
        e.carrera,
        e.semestre,
        e.telefono,
        u.correo_electronico

    ORDER BY match_porcentaje DESC, habilidades_coincidentes DESC
");
$stmt->execute([
    ':id_desafio' => $idDesafio,
    ':total_habilidades' => $totalHabilidades
]);
$talentos = $stmt->fetchAll();

/* Habilidades coincidentes por estudiante */
$habilidadesCoincidentes = [];

if (!empty($talentos)) {
    foreach ($talentos as $talento) {
        $stmt = $pdo->prepare("
            SELECT h.nombre
            FROM estudiante_habilidad eh
            INNER JOIN habilidad h
                ON eh.id_habilidad = h.id_habilidad
            INNER JOIN desafio_habilidad dh
                ON eh.id_habilidad = dh.id_habilidad
            WHERE eh.id_estudiante = :id_estudiante
              AND dh.id_desafio = :id_desafio
            ORDER BY h.nombre ASC
        ");
        $stmt->execute([
            ':id_estudiante' => $talento['id_estudiante'],
            ':id_desafio' => $idDesafio
        ]);

        $habilidadesCoincidentes[$talento['id_estudiante']] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Talentos compatibles - EduNexo MP</title>

    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/dashboard_organizacion.css">
    <link rel="stylesheet" href="../../assets/css/org/talentos.css">
    <link rel="stylesheet" href="../../assets/css/dark.css">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<div class="app-layout org-layout">
    <aside class="sidebar org-sidebar">
        <div class="sidebar-top">
            <div class="logo-mini">EN</div>
            <span>EduNexo MP</span>
        </div>

        <nav class="sidebar-menu">
            <a href="dashboard_organizacion.php">
                <i class="bi bi-house-door"></i> Mis desafíos
            </a>
            <a href="propuestas_organizacion.php">
                <i class="bi bi-file-earmark-text"></i> Propuestas
            </a>
            <a href="chat_organizacion.php">
                <i class="bi bi-chat"></i> Chat
            </a>
        </nav>
    </aside>

    <section class="app-content org-content">
        <div class="org-page-wrap">
            <div class="org-page-head">
                <div>
                    <h1>Talentos compatibles</h1>
                    <p>
                        <?php echo htmlspecialchars($desafio['titulo']); ?>
                        ·
                        <?php echo htmlspecialchars($desafio['nombre_categoria']); ?>
                    </p>
                </div>

                <a href="detalle_desafio_organizacion.php?id=<?php echo (int) $idDesafio; ?>" class="btn btn-nav">
                    Volver
                </a>
            </div>

            <?php if ($totalHabilidades === 0): ?>
                <div class="talentos-warning">
                    <i class="bi bi-exclamation-circle"></i>
                    <div>
                        <strong>Este desafío aún no tiene habilidades asignadas.</strong>
                        <p>Agrega habilidades para calcular compatibilidad real con los estudiantes.</p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($talentos)): ?>
                <div class="talentos-grid">
                    <?php foreach ($talentos as $talento): ?>
                        <?php
                        $nombreCompleto = trim(
                            $talento['nombre'] . ' ' .
                            $talento['apellido_paterno'] . ' ' .
                            ($talento['apellido_materno'] ?? '')
                        );

                        $match = (int) $talento['match_porcentaje'];
                        $tags = $habilidadesCoincidentes[$talento['id_estudiante']] ?? [];
                        ?>

                        <article class="talento-card">
                            <div class="talento-header">
                                <div class="talento-avatar">
                                    <?php echo htmlspecialchars(strtoupper(substr($talento['nombre'], 0, 1))); ?>
                                </div>

                                <div>
                                    <h3><?php echo htmlspecialchars($nombreCompleto); ?></h3>
                                    <p><?php echo htmlspecialchars($talento['carrera']); ?> · <?php echo (int) $talento['semestre']; ?>° semestre</p>
                                </div>
                            </div>

                            <div class="talento-match">
                                <div class="talento-bar">
                                    <div class="talento-progress" style="width: <?php echo $match; ?>%;"></div>
                                </div>
                                <strong><?php echo $match; ?>% match</strong>
                            </div>

                            <div class="talento-info">
                                <p><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($talento['correo_electronico']); ?></p>
                                <p><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($talento['telefono'] ?? 'No registrado'); ?></p>
                            </div>

                            <div class="talento-tags">
                                <?php if (!empty($tags)): ?>
                                    <?php foreach ($tags as $tag): ?>
                                        <span><?php echo htmlspecialchars($tag); ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span>Sin coincidencias registradas</span>
                                <?php endif; ?>
                            </div>
                        </article>

                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="org-empty-card">
                    <i class="bi bi-people"></i>
                    <h3>No hay estudiantes disponibles</h3>
                    <p>Aún no hay estudiantes registrados o con habilidades asignadas.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php if ($success): ?>
<script>
window.edunexoSuccess = <?php echo json_encode($success); ?>;
</script>
<?php endif; ?>

<?php if ($error): ?>
<script>
window.edunexoError = <?php echo json_encode($error); ?>;
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/main.js"></script>
</body>
</html>