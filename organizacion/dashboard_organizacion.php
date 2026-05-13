<?php
require_once '../includes/session_organizacion.php';
require_once '../config/conexion.php';

$idUsuario = $_SESSION['usuario_id'];

/* Datos de la org */
$stmt = $pdo->prepare("
    SELECT
        u.correo_electronico,
        o.id_organizacion,
        o.nombre_empresa,
        o.representante,
        o.telefono_contacto,
        o.sector
    FROM usuario u
    INNER JOIN organizacion o
        ON u.id_usuario = o.id_organizacion
    WHERE u.id_usuario = :id
    LIMIT 1
");
$stmt->execute([':id' => $idUsuario]);
$organizacion = $stmt->fetch();

if (!$organizacion) {
    session_unset();
    session_destroy();
    header('Location: ../index.php');
    exit;
}

/* Metricas de inicio */
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM desafio
    WHERE id_organizacion = :id_organizacion
      AND LOWER(COALESCE(estado, '')) = 'activo'
");
$stmt->execute([':id_organizacion' => $idUsuario]);
$totalDesafiosActivos = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM propuesta p
    INNER JOIN desafio d
        ON p.id_desafio = d.id_desafio
    WHERE d.id_organizacion = :id_organizacion
");
$stmt->execute([':id_organizacion' => $idUsuario]);
$totalPropuestas = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM propuesta p
    INNER JOIN desafio d
        ON p.id_desafio = d.id_desafio
    WHERE d.id_organizacion = :id_organizacion
      AND LOWER(COALESCE(p.estado, '')) IN ('en revisión', 'pendiente')
");
$stmt->execute([':id_organizacion' => $idUsuario]);
$totalPendientes = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT
        AVG(
            CASE
                WHEN stats.total_habilidades = 0 THEN 0
                ELSE (stats.coincidentes * 100.0 / stats.total_habilidades)
            END
        ) AS match_promedio
    FROM (
        SELECT
            p.id_propuesta,
            COUNT(DISTINCT dh.id_habilidad) AS total_habilidades,
            COUNT(DISTINCT CASE
                WHEN eh.id_habilidad IS NOT NULL THEN dh.id_habilidad
            END) AS coincidentes
        FROM propuesta p
        INNER JOIN desafio d
            ON p.id_desafio = d.id_desafio
        LEFT JOIN desafio_habilidad dh
            ON d.id_desafio = dh.id_desafio
        LEFT JOIN estudiante_habilidad eh
            ON eh.id_habilidad = dh.id_habilidad
           AND eh.id_estudiante = p.id_estudiante
        WHERE d.id_organizacion = :id_organizacion
        GROUP BY p.id_propuesta
    ) AS stats
");
$stmt->execute([':id_organizacion' => $idUsuario]);
$matchPromedio = $stmt->fetchColumn();
$matchPromedio = $matchPromedio !== null ? round($matchPromedio) : 0;

/* Desafios avtivos/recientes */
$stmt = $pdo->prepare("
    SELECT
        d.id_desafio,
        d.titulo,
        d.descripcion,
        d.estado,
        d.fecha_limite,
        c.nombre_categoria,
        COUNT(p.id_propuesta) AS propuestas_recibidas
    FROM desafio d
    INNER JOIN categoria c
        ON d.id_categoria = c.id_categoria
    LEFT JOIN propuesta p
        ON d.id_desafio = p.id_desafio
    WHERE d.id_organizacion = :id_organizacion
    GROUP BY
        d.id_desafio,
        d.titulo,
        d.descripcion,
        d.estado,
        d.fecha_limite,
        c.nombre_categoria
    ORDER BY d.fecha_publicacion DESC
    LIMIT 3
");
$stmt->execute([':id_organizacion' => $idUsuario]);
$desafios = $stmt->fetchAll();

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Organización - EduNexo MP</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/org/dashboard_organizacion.css">
    <link rel="stylesheet" href="../assets/css/dark.css?v=dark-fix-2">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<div class="app-layout org-layout">
    <?php include __DIR__ . '/../includes/sidebar_organizacion.php'; ?>

    <section class="app-content org-content">
        <?php include __DIR__ . '/../includes/app_topbar.php'; ?>

        <div class="org-header">
            <div>
                <h1>Panel de Control</h1>
                <p>Gestiona tus desafíos, revisa propuestas y conecta con talento estudiantil</p>
            </div>

            <a href="crear_desafio.php" class="btn btn-primary org-create-btn">
                <i class="bi bi-plus-lg"></i>
                Crear nuevo desafío
            </a>
        </div>

        <div class="org-summary-grid">
            <article class="org-summary-card card-blue-soft">
                <div>
                    <span>Desafíos activos</span>
                    <strong><?php echo $totalDesafiosActivos; ?></strong>
                </div>
                <div class="org-summary-icon icon-blue">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
            </article>

            <article class="org-summary-card card-sky-soft">
                <div>
                    <span>Propuestas recibidas</span>
                    <strong><?php echo $totalPropuestas; ?></strong>
                </div>
                <div class="org-summary-icon icon-sky">
                    <i class="bi bi-people"></i>
                </div>
            </article>

            <article class="org-summary-card card-green-soft">
                <div>
                    <span>Propuestas pendientes</span>
                    <strong><?php echo $totalPendientes; ?></strong>
                </div>
                <div class="org-summary-icon icon-green">
                    <i class="bi bi-clock"></i>
                </div>
            </article>

            <article class="org-summary-card card-orange-soft">
                <div>
                    <span>Match promedio</span>
                    <strong><?php echo $matchPromedio; ?>%</strong>
                </div>
                <div class="org-summary-icon icon-orange">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
            </article>
        </div>

        <div class="org-section-head">
            <h2>Desafíos activos</h2>
            <a href="#" class="org-view-all">
                Ver todos <i class="bi bi-eye"></i>
            </a>
        </div>

        <div class="org-challenges-grid">
            <?php if (!empty($desafios)): ?>
                <?php foreach ($desafios as $desafio): ?>
                    <?php
                    $estado = strtolower(trim($desafio['estado'] ?? ''));
                    $estadoClase = str_replace(' ', '-', $estado);

                    if ($estado === 'activo') {
                        $estadoTexto = 'Activo';
                    } elseif ($estado === 'cerrado') {
                        $estadoTexto = 'Cerrado';
                    } else {
                        $estadoTexto = !empty($desafio['estado']) ? $desafio['estado'] : 'Sin estado';
                    }
                    ?>
                    <article class="org-challenge-card">
                        <div class="org-challenge-head">
                            <h3><?php echo htmlspecialchars($desafio['titulo']); ?></h3>
                            <span class="org-status-badge estado-<?php echo htmlspecialchars($estadoClase); ?>">
                                <?php echo htmlspecialchars($estadoTexto); ?>
                            </span>
                        </div>

                        <p class="org-challenge-desc">
                            <?php echo htmlspecialchars(mb_strimwidth($desafio['descripcion'], 0, 90, '...')); ?>
                        </p>

                        <div class="org-challenge-meta">
                            <div>
                                <i class="bi bi-file-earmark-text"></i>
                                <span>Categoría: <?php echo htmlspecialchars($desafio['nombre_categoria']); ?></span>
                            </div>

                            <div>
                                <i class="bi bi-journal-text"></i>
                                <span><?php echo (int) $desafio['propuestas_recibidas']; ?> propuestas recibidas</span>
                            </div>

                            <div>
                                <i class="bi bi-calendar-event"></i>
                                <span>
                                    Cierra:
                                    <?php
                                    echo !empty($desafio['fecha_limite'])
                                        ? htmlspecialchars(date('d \d\e F \d\e Y', strtotime($desafio['fecha_limite'])))
                                        : 'Sin definir';
                                    ?>
                                </span>
                            </div>
                        </div>

                        <div class="org-challenge-actions">
                            <a href="./desafios/detalle_desafio_organizacion.php?id=<?php echo (int) $desafio['id_desafio']; ?>" class="btn btn-nav">
                                Ver detalle
                            </a>
                            <a href="./desafios/editar_desafio.php?id=<?php echo (int) $desafio['id_desafio']; ?>" class="btn btn-primary">
                                Editar
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <article class="org-empty-card">
                    <i class="bi bi-folder2-open"></i>
                    <h3>Aún no has publicado desafíos</h3>
                    <p>Cuando registres tu primer desafío, aparecerá aquí en el panel principal.</p>
                </article>
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
<script src="../assets/js/main.js"></script>
</body>
</html>
