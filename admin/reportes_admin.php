<?php
require_once __DIR__ . '/../includes/session_admin.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/report_chart_helpers.php';

$totalUsuarios = (int) $pdo->query("SELECT COUNT(*) FROM usuario")->fetchColumn();
$totalEstudiantes = (int) $pdo->query("SELECT COUNT(*) FROM usuario WHERE rol = 'estudiante'")->fetchColumn();
$totalOrganizaciones = (int) $pdo->query("SELECT COUNT(*) FROM usuario WHERE rol = 'organizacion'")->fetchColumn();
$totalAdmins = (int) $pdo->query("SELECT COUNT(*) FROM usuario WHERE rol = 'administrador'")->fetchColumn();
$totalDesafios = (int) $pdo->query("SELECT COUNT(*) FROM desafio")->fetchColumn();
$totalPropuestas = (int) $pdo->query("SELECT COUNT(*) FROM propuesta")->fetchColumn();

$stmt = $pdo->query("
    SELECT rol, COUNT(*) AS total
    FROM usuario
    GROUP BY rol
    ORDER BY rol
");
$usuariosPorRol = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT estado, COUNT(*) AS total
    FROM propuesta
    GROUP BY estado
    ORDER BY total DESC
");
$propuestasPorEstado = $stmt->fetchAll(PDO::FETCH_ASSOC);

$mesesUsuarios = edunexo_report_months();
$stmt = $pdo->query("
    SELECT TO_CHAR(DATE_TRUNC('month', fecha_registro), 'YYYY-MM') AS mes, COUNT(*) AS total
    FROM usuario
    WHERE fecha_registro >= DATE_TRUNC('month', CURRENT_DATE) - INTERVAL '5 months'
    GROUP BY 1
    ORDER BY mes
");
$mesesUsuarios = edunexo_report_fill_months($mesesUsuarios, $stmt->fetchAll(PDO::FETCH_ASSOC));
$labelsMeses = edunexo_report_labels($mesesUsuarios);
$valoresUsuarios = edunexo_report_values($mesesUsuarios);
$maxUsuariosMes = edunexo_report_max($valoresUsuarios);
$puntosUsuarios = edunexo_report_line_points($valoresUsuarios, $maxUsuariosMes);

$mesesDesafios = edunexo_report_months();
$stmt = $pdo->query("
    SELECT TO_CHAR(DATE_TRUNC('month', fecha_publicacion), 'YYYY-MM') AS mes, COUNT(*) AS total
    FROM desafio
    WHERE fecha_publicacion >= DATE_TRUNC('month', CURRENT_DATE) - INTERVAL '5 months'
    GROUP BY 1
    ORDER BY mes
");
$mesesDesafios = edunexo_report_fill_months($mesesDesafios, $stmt->fetchAll(PDO::FETCH_ASSOC));

$mesesPropuestas = edunexo_report_months();
$stmt = $pdo->query("
    SELECT TO_CHAR(DATE_TRUNC('month', fecha_envio), 'YYYY-MM') AS mes, COUNT(*) AS total
    FROM propuesta
    WHERE fecha_envio >= DATE_TRUNC('month', CURRENT_DATE) - INTERVAL '5 months'
    GROUP BY 1
    ORDER BY mes
");
$mesesPropuestas = edunexo_report_fill_months($mesesPropuestas, $stmt->fetchAll(PDO::FETCH_ASSOC));
$valoresDesafios = edunexo_report_values($mesesDesafios);
$valoresPropuestas = edunexo_report_values($mesesPropuestas);
$maxActividad = edunexo_report_max($valoresDesafios, $valoresPropuestas);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes administrador | EduNexo MP</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/reportes.css">
    <link rel="stylesheet" href="../assets/css/dark.css?v=dark-fix-2">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <section class="app-content report-page">
        <?php include __DIR__ . '/../includes/app_topbar.php'; ?>

        <div class="report-hero">
            <div>
                <h1>Reportes administrativos</h1>
                <p>Métricas generales de usuarios, desafíos y propuestas registradas.</p>
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
                    <span>Total usuarios</span>
                    <strong><?php echo $totalUsuarios; ?></strong>
                    <p>Cuentas registradas</p>
                </div>
                <i class="bi bi-people"></i>
            </article>
            <article class="report-card">
                <div>
                    <span>Estudiantes</span>
                    <strong><?php echo $totalEstudiantes; ?></strong>
                    <p>Perfiles académicos</p>
                </div>
                <i class="bi bi-mortarboard"></i>
            </article>
            <article class="report-card">
                <div>
                    <span>Organizaciones</span>
                    <strong><?php echo $totalOrganizaciones; ?></strong>
                    <p>Empresas registradas</p>
                </div>
                <i class="bi bi-building"></i>
            </article>
            <article class="report-card">
                <div>
                    <span>Propuestas</span>
                    <strong><?php echo $totalPropuestas; ?></strong>
                    <p>Postulaciones enviadas</p>
                </div>
                <i class="bi bi-send"></i>
            </article>
        </div>

        <div class="report-chart-grid">
            <section class="report-panel report-chart-card">
                <div class="report-panel-head">
                    <div>
                        <h2>Crecimiento de usuarios</h2>
                        <p>Nuevos registros durante los últimos seis meses.</p>
                    </div>
                </div>
                <div class="report-line-chart" aria-label="Gráfica de crecimiento de usuarios">
                    <svg viewBox="0 0 520 260" role="img">
                        <?php for ($i = 34; $i <= 226; $i += 48): ?>
                            <line class="report-chart-gridline" x1="34" y1="<?php echo $i; ?>" x2="486" y2="<?php echo $i; ?>"></line>
                        <?php endfor; ?>
                        <line class="report-chart-axis" x1="34" y1="226" x2="486" y2="226"></line>
                        <line class="report-chart-axis" x1="34" y1="34" x2="34" y2="226"></line>
                        <polyline class="report-chart-line" points="<?php echo htmlspecialchars($puntosUsuarios); ?>"></polyline>
                        <?php foreach (explode(' ', $puntosUsuarios) as $point): ?>
                            <?php [$cx, $cy] = array_map('floatval', explode(',', $point)); ?>
                            <circle class="report-chart-point" cx="<?php echo $cx; ?>" cy="<?php echo $cy; ?>" r="6"></circle>
                        <?php endforeach; ?>
                        <?php foreach ($labelsMeses as $index => $label): ?>
                            <?php $x = 34 + ((452 / max(1, count($labelsMeses) - 1)) * $index); ?>
                            <text class="report-chart-label" x="<?php echo round($x, 2); ?>" y="250" text-anchor="middle"><?php echo htmlspecialchars($label); ?></text>
                        <?php endforeach; ?>
                    </svg>
                </div>
            </section>

            <section class="report-panel report-chart-card">
                <div class="report-panel-head">
                    <div>
                        <h2>Actividad mensual</h2>
                        <p>Desafíos publicados y propuestas enviadas.</p>
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
        </div>

        <section class="report-panel">
            <div class="report-panel-head">
                <div>
                    <h2>Usuarios por rol</h2>
                    <p>Distribución de cuentas activas en el sistema.</p>
                </div>
            </div>
            <div class="report-status-list">
                <?php foreach ($usuariosPorRol as $item): ?>
                    <article class="report-status-item">
                        <span><?php echo htmlspecialchars(ucfirst($item['rol'] ?? 'Sin rol')); ?></span>
                        <strong><?php echo (int) $item['total']; ?></strong>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="report-panel">
            <div class="report-panel-head">
                <div>
                    <h2>Propuestas por estado</h2>
                    <p>Seguimiento del flujo de postulaciones.</p>
                </div>
            </div>
            <div class="report-status-list">
                <?php foreach (($propuestasPorEstado ?: [['estado' => 'Sin propuestas', 'total' => 0]]) as $item): ?>
                    <article class="report-status-item">
                        <span><?php echo htmlspecialchars($item['estado'] ?? 'Sin estado'); ?></span>
                        <strong><?php echo (int) $item['total']; ?></strong>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="report-panel">
            <div class="report-panel-head">
                <div>
                    <h2>Actividad general</h2>
                    <p>Indicadores principales para revisión rápida.</p>
                </div>
            </div>
            <div class="report-table-wrap">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Indicador</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Administradores</td>
                            <td><?php echo $totalAdmins; ?></td>
                        </tr>
                        <tr>
                            <td>Desafíos publicados</td>
                            <td><?php echo $totalDesafios; ?></td>
                        </tr>
                        <tr>
                            <td>Propuestas enviadas</td>
                            <td><?php echo $totalPropuestas; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
