<?php
require_once __DIR__ . '/../includes/session_superadmin.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/report_chart_helpers.php';

$stmtAdmin = $pdo->prepare("
    SELECT nombre
    FROM administrador
    WHERE id_admin = :id_admin
    LIMIT 1
");
$stmtAdmin->execute([
    ':id_admin' => $_SESSION['usuario_id']
]);
$admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
$inicialAdmin = strtoupper(substr($admin['nombre'] ?? 'U', 0, 1));

$totalUsuarios = (int) $pdo->query("SELECT COUNT(*) FROM usuario")->fetchColumn();
$totalEstudiantes = (int) $pdo->query("SELECT COUNT(*) FROM usuario WHERE rol = 'estudiante'")->fetchColumn();
$totalOrganizaciones = (int) $pdo->query("SELECT COUNT(*) FROM usuario WHERE rol = 'organizacion'")->fetchColumn();
$totalAdmins = (int) $pdo->query("SELECT COUNT(*) FROM usuario WHERE rol = 'administrador'")->fetchColumn();
$totalDesafios = (int) $pdo->query("SELECT COUNT(*) FROM desafio")->fetchColumn();
$totalPropuestas = (int) $pdo->query("SELECT COUNT(*) FROM propuesta")->fetchColumn();
$totalPendientesAdmin = (int) $pdo->query("SELECT COUNT(*) FROM administrador WHERE estado_solicitud = 'pendiente'")->fetchColumn();

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
$maxRoles = edunexo_report_max([$totalEstudiantes, $totalOrganizaciones, $totalAdmins]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes | Superadmin - EduNexo MP</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dark.css?v=dark-fix-2">
    <link rel="stylesheet" href="../assets/css/superadmin/dashboard_superadmin.css">
    <link rel="stylesheet" href="../assets/css/superadmin/superadmin_sections.css">
    <link rel="stylesheet" href="../assets/css/reportes.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <section class="app-content superadmin-content">
        <?php include __DIR__ . '/../includes/app_topbar.php'; ?>

        <div class="superadmin-header">
            <div>
                <h1>Reportes del sistema</h1>
                <p>Resumen estadístico del comportamiento general de la plataforma.</p>
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

        <div class="superadmin-summary-grid">
            <article class="superadmin-summary-card">
                <div>
                    <span>Total usuarios</span>
                    <strong><?php echo $totalUsuarios; ?></strong>
                    <p>Usuarios acumulados en EduNexo MP</p>
                </div>
                <div class="superadmin-summary-icon icon-blue-soft">
                    <i class="bi bi-people"></i>
                </div>
            </article>

            <article class="superadmin-summary-card">
                <div>
                    <span>Desafíos</span>
                    <strong><?php echo $totalDesafios; ?></strong>
                    <p>Desafíos publicados en el sistema</p>
                </div>
                <div class="superadmin-summary-icon icon-sky-soft">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
            </article>

            <article class="superadmin-summary-card">
                <div>
                    <span>Propuestas</span>
                    <strong><?php echo $totalPropuestas; ?></strong>
                    <p>Postulaciones enviadas por estudiantes</p>
                </div>
                <div class="superadmin-summary-icon icon-green-soft">
                    <i class="bi bi-send"></i>
                </div>
            </article>

            <article class="superadmin-summary-card">
                <div>
                    <span>Solicitudes admin</span>
                    <strong><?php echo $totalPendientesAdmin; ?></strong>
                    <p>Solicitudes pendientes de autorización</p>
                </div>
                <div class="superadmin-summary-icon icon-orange-soft">
                    <i class="bi bi-shield-check"></i>
                </div>
            </article>
        </div>

        <div class="report-chart-grid">
            <section class="report-panel report-chart-card">
                <div class="report-panel-head">
                    <div>
                        <h2>Crecimiento de usuarios</h2>
                        <p>Evolución mensual de cuentas registradas.</p>
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

        <section class="superadmin-panel-card full-card">
            <div class="panel-card-head">
                <div>
                    <h2>Resumen por tipo de cuenta</h2>
                    <p>Distribución general de usuarios registrados</p>
                </div>
            </div>

            <div class="report-grid">
                <article class="report-mini-card">
                    <h3>Estudiantes</h3>
                    <strong><?php echo $totalEstudiantes; ?></strong>
                </article>

                <article class="report-mini-card">
                    <h3>Organizaciones</h3>
                    <strong><?php echo $totalOrganizaciones; ?></strong>
                </article>

                <article class="report-mini-card">
                    <h3>Administradores</h3>
                    <strong><?php echo $totalAdmins; ?></strong>
                </article>
            </div>
            <div class="report-progress-list">
                <?php foreach ([
                    'Estudiantes' => $totalEstudiantes,
                    'Organizaciones' => $totalOrganizaciones,
                    'Administradores' => $totalAdmins,
                ] as $label => $total): ?>
                    <div class="report-progress-row">
                        <span><?php echo htmlspecialchars($label); ?></span>
                        <div class="report-progress-track">
                            <div class="report-progress-fill" style="width: <?php echo edunexo_report_bar_percent((int) $total, $maxRoles); ?>%"></div>
                        </div>
                        <strong><?php echo (int) $total; ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </section>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>