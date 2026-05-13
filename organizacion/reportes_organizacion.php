<?php
require_once '../includes/session_organizacion.php';
require_once '../config/conexion.php';
require_once '../includes/report_chart_helpers.php';

$idUsuario = (int) $_SESSION['usuario_id'];

function reporte_org_estado(?string $estado): string
{
    $estado = mb_strtolower(trim($estado ?? ''));

    if (str_contains($estado, 'acept')) {
        return 'Aceptadas';
    }

    if (str_contains($estado, 'rechaz')) {
        return 'Rechazadas';
    }

    if (str_contains($estado, 'revision') || str_contains($estado, 'revisión') || str_contains($estado, 'pendiente')) {
        return 'En revisión';
    }

    return $estado !== '' ? ucfirst($estado) : 'Sin estado';
}

$stmt = $pdo->prepare("
    SELECT nombre_empresa, sector
    FROM organizacion
    WHERE id_organizacion = :id
    LIMIT 1
");
$stmt->execute([':id' => $idUsuario]);
$organizacion = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM desafio WHERE id_organizacion = :id");
$stmt->execute([':id' => $idUsuario]);
$totalDesafios = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM desafio
    WHERE id_organizacion = :id
      AND LOWER(COALESCE(estado, '')) = 'activo'
");
$stmt->execute([':id' => $idUsuario]);
$totalActivos = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM propuesta p
    INNER JOIN desafio d ON p.id_desafio = d.id_desafio
    WHERE d.id_organizacion = :id
");
$stmt->execute([':id' => $idUsuario]);
$totalPropuestas = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT c.id_conversacion)
    FROM conversacion c
    INNER JOIN propuesta p ON c.id_propuesta = p.id_propuesta
    INNER JOIN desafio d ON p.id_desafio = d.id_desafio
    WHERE d.id_organizacion = :id
");
$stmt->execute([':id' => $idUsuario]);
$totalConversaciones = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT p.estado, COUNT(*) AS total
    FROM propuesta p
    INNER JOIN desafio d ON p.id_desafio = d.id_desafio
    WHERE d.id_organizacion = :id
    GROUP BY p.estado
");
$stmt->execute([':id' => $idUsuario]);
$estadoConteos = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $clave = reporte_org_estado($row['estado'] ?? '');
    $estadoConteos[$clave] = ($estadoConteos[$clave] ?? 0) + (int) $row['total'];
}

$mesesDesafios = edunexo_report_months();
$stmt = $pdo->prepare("
    SELECT TO_CHAR(DATE_TRUNC('month', fecha_publicacion), 'YYYY-MM') AS mes, COUNT(*) AS total
    FROM desafio
    WHERE id_organizacion = :id
      AND fecha_publicacion >= DATE_TRUNC('month', CURRENT_DATE) - INTERVAL '5 months'
    GROUP BY 1
    ORDER BY mes
");
$stmt->execute([':id' => $idUsuario]);
$mesesDesafios = edunexo_report_fill_months($mesesDesafios, $stmt->fetchAll(PDO::FETCH_ASSOC));

$mesesPropuestas = edunexo_report_months();
$stmt = $pdo->prepare("
    SELECT TO_CHAR(DATE_TRUNC('month', p.fecha_envio), 'YYYY-MM') AS mes, COUNT(*) AS total
    FROM propuesta p
    INNER JOIN desafio d ON p.id_desafio = d.id_desafio
    WHERE d.id_organizacion = :id
      AND p.fecha_envio >= DATE_TRUNC('month', CURRENT_DATE) - INTERVAL '5 months'
    GROUP BY 1
    ORDER BY mes
");
$stmt->execute([':id' => $idUsuario]);
$mesesPropuestas = edunexo_report_fill_months($mesesPropuestas, $stmt->fetchAll(PDO::FETCH_ASSOC));
$labelsMeses = edunexo_report_labels($mesesDesafios);
$valoresDesafios = edunexo_report_values($mesesDesafios);
$valoresPropuestas = edunexo_report_values($mesesPropuestas);
$maxActividad = edunexo_report_max($valoresDesafios, $valoresPropuestas);
$maxEstados = edunexo_report_max(array_values($estadoConteos ?: [0]));

$stmt = $pdo->prepare("
    SELECT
        d.id_desafio,
        d.titulo,
        d.estado,
        d.fecha_publicacion,
        d.fecha_limite,
        c.nombre_categoria,
        COUNT(p.id_propuesta) AS propuestas_recibidas,
        COUNT(CASE WHEN LOWER(COALESCE(p.estado, '')) LIKE '%acept%' THEN 1 END) AS propuestas_aceptadas
    FROM desafio d
    INNER JOIN categoria c ON d.id_categoria = c.id_categoria
    LEFT JOIN propuesta p ON d.id_desafio = p.id_desafio
    WHERE d.id_organizacion = :id
    GROUP BY d.id_desafio, d.titulo, d.estado, d.fecha_publicacion, d.fecha_limite, c.nombre_categoria
    ORDER BY d.fecha_publicacion DESC
    LIMIT 10
");
$stmt->execute([':id' => $idUsuario]);
$desafios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes organización - EduNexo MP</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/org/dashboard_organizacion.css">
    <link rel="stylesheet" href="../assets/css/reportes.css">
    <link rel="stylesheet" href="../assets/css/dark.css?v=dark-fix-2">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
<div class="app-layout org-layout">
    <?php include __DIR__ . '/../includes/sidebar_organizacion.php'; ?>

    <section class="app-content org-content report-page">
        <?php include __DIR__ . '/../includes/app_topbar.php'; ?>

        <div class="report-hero">
            <div>
                <h1>Reportes</h1>
                <p>Indicadores de tus desafíos publicados, propuestas recibidas y conversaciones activas.</p>
            </div>
            <div class="report-actions">
                <a class="btn btn-primary" href="../procesos/reportes/exportar_reporte.php">
                    <i class="bi bi-download"></i>
                    Descargar CSV
                </a>
                <button class="btn btn-nav" type="button" onclick="window.print()">
                    <i class="bi bi-printer"></i>
                    Imprimir
                </button>
            </div>
        </div>

        <div class="report-summary-grid">
            <article class="report-card">
                <div>
                    <span>Desafíos publicados</span>
                    <strong><?php echo $totalDesafios; ?></strong>
                    <p>Total histórico</p>
                </div>
                <i class="bi bi-briefcase"></i>
            </article>
            <article class="report-card">
                <div>
                    <span>Desafíos activos</span>
                    <strong><?php echo $totalActivos; ?></strong>
                    <p>Disponibles para postular</p>
                </div>
                <i class="bi bi-lightning"></i>
            </article>
            <article class="report-card">
                <div>
                    <span>Propuestas recibidas</span>
                    <strong><?php echo $totalPropuestas; ?></strong>
                    <p>Postulaciones acumuladas</p>
                </div>
                <i class="bi bi-send"></i>
            </article>
            <article class="report-card">
                <div>
                    <span>Conversaciones</span>
                    <strong><?php echo $totalConversaciones; ?></strong>
                    <p>Chats con estudiantes</p>
                </div>
                <i class="bi bi-chat"></i>
            </article>
        </div>

        <div class="report-chart-grid">
            <section class="report-panel report-chart-card">
                <div class="report-panel-head">
                    <div>
                        <h2>Actividad mensual</h2>
                        <p>Desafíos publicados y propuestas recibidas.</p>
                    </div>
                </div>
                <div class="report-bar-chart" aria-label="Gráfica de actividad mensual">
                    <?php foreach ($labelsMeses as $index => $label): ?>
                        <div class="report-bar-group">
                            <div class="report-bar-track">
                                <div class="report-bar secondary" title="Desafíos: <?php echo (int) $valoresDesafios[$index]; ?>" style="height: <?php echo edunexo_report_bar_percent((int) $valoresDesafios[$index], $maxActividad); ?>%"></div>
                                <div class="report-bar" title="Propuestas: <?php echo (int) $valoresPropuestas[$index]; ?>" style="height: <?php echo edunexo_report_bar_percent((int) $valoresPropuestas[$index], $maxActividad); ?>%"></div>
                            </div>
                            <span class="report-bar-label"><?php echo htmlspecialchars($label); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="report-chart-legend">
                    <span><i class="secondary"></i> Desafíos</span>
                    <span><i></i> Propuestas</span>
                </div>
            </section>

            <section class="report-panel report-chart-card">
                <div class="report-panel-head">
                    <div>
                        <h2>Estados de propuestas</h2>
                        <p>Resultado acumulado de postulaciones recibidas.</p>
                    </div>
                </div>
                <div class="report-progress-list">
                    <?php foreach (($estadoConteos ?: ['Sin propuestas' => 0]) as $estado => $total): ?>
                        <?php $porcentaje = edunexo_report_bar_percent((int) $total, $maxEstados); ?>
                        <div class="report-progress-row">
                            <span><?php echo htmlspecialchars($estado); ?></span>
                            <div class="report-progress-track">
                                <div class="report-progress-fill" style="width: <?php echo $porcentaje; ?>%"></div>
                            </div>
                            <strong><?php echo (int) $total; ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>

        <section class="report-panel">
            <div class="report-panel-head">
                <div>
                    <h2>Estado de propuestas</h2>
                    <p><?php echo htmlspecialchars($organizacion['nombre_empresa'] ?? 'Organización'); ?> · <?php echo htmlspecialchars($organizacion['sector'] ?? 'Sector no registrado'); ?></p>
                </div>
            </div>
            <div class="report-status-list">
                <?php foreach (($estadoConteos ?: ['Sin propuestas' => 0]) as $estado => $total): ?>
                    <article class="report-status-item">
                        <span><?php echo htmlspecialchars($estado); ?></span>
                        <strong><?php echo (int) $total; ?></strong>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="report-panel">
            <div class="report-panel-head">
                <div>
                    <h2>Desempeño por desafío</h2>
                    <p>Últimos desafíos publicados y volumen de propuestas.</p>
                </div>
            </div>

            <?php if (!empty($desafios)): ?>
                <div class="report-table-wrap">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Desafío</th>
                                <th>Categoría</th>
                                <th>Estado</th>
                                <th>Publicación</th>
                                <th>Fecha límite</th>
                                <th>Propuestas</th>
                                <th>Aceptadas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($desafios as $desafio): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($desafio['titulo'] ?? 'Sin título'); ?></td>
                                    <td><?php echo htmlspecialchars($desafio['nombre_categoria'] ?? 'Sin categoría'); ?></td>
                                    <td><?php echo htmlspecialchars($desafio['estado'] ?? 'Sin estado'); ?></td>
                                    <td><?php echo !empty($desafio['fecha_publicacion']) ? htmlspecialchars(date('d/m/Y', strtotime($desafio['fecha_publicacion']))) : 'No registrada'; ?></td>
                                    <td><?php echo !empty($desafio['fecha_limite']) ? htmlspecialchars(date('d/m/Y', strtotime($desafio['fecha_limite']))) : 'Sin límite'; ?></td>
                                    <td><?php echo (int) $desafio['propuestas_recibidas']; ?></td>
                                    <td><?php echo (int) $desafio['propuestas_aceptadas']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="report-empty">Aún no hay desafíos para reportar.</div>
            <?php endif; ?>
        </section>
    </section>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
