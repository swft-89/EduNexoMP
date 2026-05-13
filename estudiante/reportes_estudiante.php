<?php
require_once '../includes/session_estudiante.php';
require_once '../config/conexion.php';
require_once '../includes/report_chart_helpers.php';

$idUsuario = (int) $_SESSION['usuario_id'];

function reporte_estado_clave(?string $estado): string
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
    SELECT nombre, apellido_paterno, carrera, semestre
    FROM estudiante
    WHERE id_estudiante = :id
    LIMIT 1
");
$stmt->execute([':id' => $idUsuario]);
$estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM propuesta WHERE id_estudiante = :id");
$stmt->execute([':id' => $idUsuario]);
$totalPropuestas = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM favoritos WHERE id_estudiante = :id");
$stmt->execute([':id' => $idUsuario]);
$totalFavoritos = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM estudiante_habilidad WHERE id_estudiante = :id");
$stmt->execute([':id' => $idUsuario]);
$totalHabilidades = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT c.id_conversacion)
    FROM conversacion c
    INNER JOIN propuesta p ON c.id_propuesta = p.id_propuesta
    WHERE p.id_estudiante = :id
");
$stmt->execute([':id' => $idUsuario]);
$totalConversaciones = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT estado, COUNT(*) AS total
    FROM propuesta
    WHERE id_estudiante = :id
    GROUP BY estado
");
$stmt->execute([':id' => $idUsuario]);
$estadoConteos = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $clave = reporte_estado_clave($row['estado'] ?? '');
    $estadoConteos[$clave] = ($estadoConteos[$clave] ?? 0) + (int) $row['total'];
}

$mesesPropuestas = edunexo_report_months();
$stmt = $pdo->prepare("
    SELECT TO_CHAR(DATE_TRUNC('month', fecha_envio), 'YYYY-MM') AS mes, COUNT(*) AS total
    FROM propuesta
    WHERE id_estudiante = :id
      AND fecha_envio >= DATE_TRUNC('month', CURRENT_DATE) - INTERVAL '5 months'
    GROUP BY 1
    ORDER BY mes
");
$stmt->execute([':id' => $idUsuario]);
$mesesPropuestas = edunexo_report_fill_months($mesesPropuestas, $stmt->fetchAll(PDO::FETCH_ASSOC));
$labelsMeses = edunexo_report_labels($mesesPropuestas);
$valoresPropuestas = edunexo_report_values($mesesPropuestas);
$maxPropuestasMes = edunexo_report_max($valoresPropuestas);
$puntosPropuestas = edunexo_report_line_points($valoresPropuestas, $maxPropuestasMes);
$maxEstados = edunexo_report_max(array_values($estadoConteos ?: [0]));

$stmt = $pdo->prepare("
    SELECT
        p.fecha_envio,
        p.estado,
        p.fecha_respuesta,
        COALESCE(p.titulo_propuesta, d.titulo) AS titulo_propuesta,
        d.titulo AS desafio,
        o.nombre_empresa
    FROM propuesta p
    INNER JOIN desafio d ON p.id_desafio = d.id_desafio
    INNER JOIN organizacion o ON d.id_organizacion = o.id_organizacion
    WHERE p.id_estudiante = :id
    ORDER BY p.fecha_envio DESC
    LIMIT 8
");
$stmt->execute([':id' => $idUsuario]);
$propuestasRecientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$nombreEstudiante = trim(($estudiante['nombre'] ?? '') . ' ' . ($estudiante['apellido_paterno'] ?? ''));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - EduNexo MP</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/reportes.css">
    <link rel="stylesheet" href="../assets/css/dark.css?v=dark-fix-2">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/../includes/sidebar_estudiante.php'; ?>

    <section class="app-content report-page">
        <?php include __DIR__ . '/../includes/app_topbar.php'; ?>

        <div class="report-hero">
            <div>
                <h1>Mis reportes</h1>
                <p>Resumen de tus postulaciones, habilidades e interacción dentro de EduNexo MP.</p>
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
                    <span>Propuestas enviadas</span>
                    <strong><?php echo $totalPropuestas; ?></strong>
                    <p>Postulaciones registradas</p>
                </div>
                <i class="bi bi-send"></i>
            </article>
            <article class="report-card">
                <div>
                    <span>Favoritos</span>
                    <strong><?php echo $totalFavoritos; ?></strong>
                    <p>Desafíos guardados</p>
                </div>
                <i class="bi bi-heart"></i>
            </article>
            <article class="report-card">
                <div>
                    <span>Habilidades</span>
                    <strong><?php echo $totalHabilidades; ?></strong>
                    <p>Registradas en tu perfil</p>
                </div>
                <i class="bi bi-stars"></i>
            </article>
            <article class="report-card">
                <div>
                    <span>Conversaciones</span>
                    <strong><?php echo $totalConversaciones; ?></strong>
                    <p>Chats asociados a propuestas</p>
                </div>
                <i class="bi bi-chat"></i>
            </article>
        </div>

        <div class="report-chart-grid">
            <section class="report-panel report-chart-card">
                <div class="report-panel-head">
                    <div>
                        <h2>Postulaciones mensuales</h2>
                        <p>Propuestas enviadas durante los últimos seis meses.</p>
                    </div>
                </div>
                <div class="report-line-chart" aria-label="Gráfica de postulaciones mensuales">
                    <svg viewBox="0 0 520 260" role="img">
                        <?php for ($i = 34; $i <= 226; $i += 48): ?>
                            <line class="report-chart-gridline" x1="34" y1="<?php echo $i; ?>" x2="486" y2="<?php echo $i; ?>"></line>
                        <?php endfor; ?>
                        <line class="report-chart-axis" x1="34" y1="226" x2="486" y2="226"></line>
                        <line class="report-chart-axis" x1="34" y1="34" x2="34" y2="226"></line>
                        <polyline class="report-chart-line" points="<?php echo htmlspecialchars($puntosPropuestas); ?>"></polyline>
                        <?php foreach (explode(' ', $puntosPropuestas) as $point): ?>
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
                        <h2>Distribución por estado</h2>
                        <p>Cómo se reparte el resultado de tus propuestas.</p>
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
                    <p><?php echo htmlspecialchars($nombreEstudiante ?: 'Estudiante'); ?> · <?php echo htmlspecialchars($estudiante['carrera'] ?? 'Carrera no registrada'); ?></p>
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
                    <h2>Propuestas recientes</h2>
                    <p>Últimas postulaciones enviadas y su seguimiento.</p>
                </div>
            </div>

            <?php if (!empty($propuestasRecientes)): ?>
                <div class="report-table-wrap">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Propuesta</th>
                                <th>Desafío</th>
                                <th>Organización</th>
                                <th>Estado</th>
                                <th>Envío</th>
                                <th>Respuesta</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($propuestasRecientes as $propuesta): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($propuesta['titulo_propuesta'] ?? 'Sin título'); ?></td>
                                    <td><?php echo htmlspecialchars($propuesta['desafio'] ?? 'Sin desafío'); ?></td>
                                    <td><?php echo htmlspecialchars($propuesta['nombre_empresa'] ?? 'Sin organización'); ?></td>
                                    <td><?php echo htmlspecialchars($propuesta['estado'] ?? 'Sin estado'); ?></td>
                                    <td><?php echo !empty($propuesta['fecha_envio']) ? htmlspecialchars(date('d/m/Y', strtotime($propuesta['fecha_envio']))) : 'No registrada'; ?></td>
                                    <td><?php echo !empty($propuesta['fecha_respuesta']) ? htmlspecialchars(date('d/m/Y', strtotime($propuesta['fecha_respuesta']))) : 'Pendiente'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="report-empty">Aún no hay propuestas para reportar.</div>
            <?php endif; ?>
        </section>
    </section>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
