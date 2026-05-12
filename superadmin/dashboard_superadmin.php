<?php
require_once __DIR__ . '../../includes/session_superadmin.php';
require_once __DIR__ . '../../config/conexion.php';

/* Datos */
$stmt = $pdo->prepare("
    SELECT a.nombre, a.apellido_paterno, a.apellido_materno
    FROM administrador a
    WHERE a.id_admin = :id_admin
    LIMIT 1
");
$stmt->execute([
    ':id_admin' => $_SESSION['usuario_id']
]);

$admin = $stmt->fetch(PDO::FETCH_ASSOC);

$nombreCompleto = trim(
    ($admin['nombre'] ?? '') . ' ' .
    ($admin['apellido_paterno'] ?? '') . ' ' .
    ($admin['apellido_materno'] ?? '')
);

$inicialAdmin = strtoupper(substr($admin['nombre'] ?? 'U', 0, 1));

/* Metricas */
$totalUsuarios = (int) $pdo->query("SELECT COUNT(*) FROM usuario")->fetchColumn();

$totalDesafiosActivos = (int) $pdo->query("
    SELECT COUNT(*)
    FROM desafio
    WHERE LOWER(COALESCE(estado, '')) = 'activo'
")->fetchColumn();

$totalPropuestas = (int) $pdo->query("SELECT COUNT(*) FROM propuesta")->fetchColumn();

$totalPendientesAdmin = (int) $pdo->query("
    SELECT COUNT(*)
    FROM administrador
    WHERE estado_solicitud = 'pendiente'
")->fetchColumn();

$totalEstudiantes = (int) $pdo->query("
    SELECT COUNT(*)
    FROM usuario
    WHERE rol = 'estudiante'
")->fetchColumn();

$totalOrganizaciones = (int) $pdo->query("
    SELECT COUNT(*)
    FROM usuario
    WHERE rol = 'organizacion'
")->fetchColumn();

$totalAdmins = (int) $pdo->query("
    SELECT COUNT(*)
    FROM usuario
    WHERE rol = 'administrador'
")->fetchColumn();

/* Desafios por vencer */
$stmtDesafios = $pdo->query("
    SELECT
        d.id_desafio,
        d.titulo,
        d.fecha_limite,
        o.nombre_empresa,
        (
            SELECT COUNT(*)
            FROM propuesta p
            WHERE p.id_desafio = d.id_desafio
        ) AS total_propuestas
    FROM desafio d
    INNER JOIN organizacion o ON o.id_organizacion = d.id_organizacion
    WHERE d.fecha_limite IS NOT NULL
    ORDER BY d.fecha_limite ASC
    LIMIT 3
");
$desafiosPorVencer = $stmtDesafios->fetchAll(PDO::FETCH_ASSOC);

/* Solicitudes de admin pendientes */
$stmtSolicitudes = $pdo->query("
    SELECT
        a.id_admin,
        a.nombre,
        a.apellido_paterno,
        a.apellido_materno,
        a.puesto,
        a.departamento,
        u.correo_electronico,
        u.fecha_registro
    FROM administrador a
    INNER JOIN usuario u ON u.id_usuario = a.id_admin
    WHERE a.estado_solicitud = 'pendiente'
    ORDER BY u.fecha_registro ASC
    LIMIT 6
");
$solicitudes = $stmtSolicitudes->fetchAll(PDO::FETCH_ASSOC);

/* Actividad reciente */
$stmtActividad = $pdo->query("
    SELECT
        tipo_evento,
        descripcion,
        usuario_nombre,
        usuario_rol,
        fecha_accion
    FROM auditoria_admin
    ORDER BY fecha_accion DESC
    LIMIT 8
");
$actividadReciente = $stmtActividad->fetchAll(PDO::FETCH_ASSOC);

/* Alertas */
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Superadministrador - EduNexo MP</title>

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dark.css">
    <link rel="stylesheet" href="../assets/css/superadmin/dashboard_superadmin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<div class="app-layout">
    <aside class="sidebar">
        <div class="sidebar-top">
            <div class="logo-mini"><i class="bi bi-mortarboard"></i></div>
            <span>EduNexo MP</span>
        </div>

        <nav class="sidebar-menu">
            <a href="dashboard_superadmin.php" class="active">
                <i class="bi bi-bar-chart-line"></i> Dashboard
            </a>
            <a href="usuarios/usuarios_superadmin.php">
                <i class="bi bi-people"></i> Usuarios
            </a>
            <a href="usuarios/solicitudes_admin.php">
                <i class="bi bi-person-check"></i> Solicitudes admin
            </a>
            <a href="reportes_superadmin.php">
                <i class="bi bi-clipboard-data"></i> Reportes
            </a>
            <a href="desafios/desafios_superadmin.php">
                <i class="bi bi-file-earmark-text"></i> Desafíos
            </a>
            <a href="propuestas/propuestas_superadmin.php">
                <i class="bi bi-send"></i> Propuestas
            </a>
            <a href="categorias_superadmin.php">
                <i class="bi bi-tags"></i> Categorías
            </a>
        </nav>
    </aside>

    <section class="app-content superadmin-content">
        <?php include __DIR__ . '/../includes/app_topbar.php'; ?>

        <div class="superadmin-header">
            <div>
                <h1>Panel de Administración</h1>
                <p>Vista general del sistema EduNexo MP. Gestiona usuarios, desafíos y monitorea la actividad.</p>
            </div>

            <div class="superadmin-actions">
                <a href="#" class="btn superadmin-btn-light">
                    <i class="bi bi-download"></i>
                    Exportar reporte
                </a>

                <a href="#" class="btn btn-primary superadmin-btn-main">
                    <i class="bi bi-eye"></i>
                    Ver todos los reportes
                </a>
            </div>
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

        <div class="superadmin-summary-grid">
            <article class="superadmin-summary-card">
                <div>
                    <span>Total usuarios</span>
                    <strong><?php echo $totalUsuarios; ?></strong>
                    <p><?php echo $totalEstudiantes; ?> estudiantes · <?php echo $totalOrganizaciones; ?> organizaciones</p>
                </div>
                <div class="superadmin-summary-icon icon-blue-soft">
                    <i class="bi bi-people"></i>
                </div>
            </article>

            <article class="superadmin-summary-card">
                <div>
                    <span>Desafíos activos</span>
                    <strong><?php echo $totalDesafiosActivos; ?></strong>
                    <p>Publicados actualmente</p>
                </div>
                <div class="superadmin-summary-icon icon-sky-soft">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
            </article>

            <article class="superadmin-summary-card">
                <div>
                    <span>Propuestas enviadas</span>
                    <strong><?php echo $totalPropuestas; ?></strong>
                    <p>Interacción total en la plataforma</p>
                </div>
                <div class="superadmin-summary-icon icon-green-soft">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
            </article>

            <article class="superadmin-summary-card">
                <div>
                    <span>Admins pendientes</span>
                    <strong><?php echo $totalPendientesAdmin; ?></strong>
                    <p><?php echo $totalAdmins; ?> administradores registrados</p>
                </div>
                <div class="superadmin-summary-icon icon-orange-soft">
                    <i class="bi bi-shield-check"></i>
                </div>
            </article>
        </div>

        <div class="superadmin-panels-grid">
            <section class="superadmin-panel-card">
                <div class="panel-card-head">
                    <div>
                        <h2>Distribución de usuarios</h2>
                        <p>Por tipo de cuenta en la plataforma</p>
                    </div>
                </div>

                <?php
                $porcentajeEst = $totalUsuarios > 0 ? round(($totalEstudiantes / $totalUsuarios) * 100, 1) : 0;
                $porcentajeOrg = $totalUsuarios > 0 ? round(($totalOrganizaciones / $totalUsuarios) * 100, 1) : 0;
                $porcentajeAdm = $totalUsuarios > 0 ? round(($totalAdmins / $totalUsuarios) * 100, 1) : 0;
                ?>

                <div class="distribution-list">
                    <div class="distribution-item">
                        <div class="distribution-head">
                            <span class="distribution-label">
                                <i class="bi bi-mortarboard"></i> Estudiantes
                            </span>
                            <span class="distribution-value"><?php echo $totalEstudiantes; ?> &nbsp; <?php echo $porcentajeEst; ?>%</span>
                        </div>
                        <div class="distribution-bar">
                            <span class="bar-fill blue" style="width: <?php echo $porcentajeEst; ?>%;"></span>
                        </div>
                    </div>

                    <div class="distribution-item">
                        <div class="distribution-head">
                            <span class="distribution-label">
                                <i class="bi bi-buildings"></i> Organizaciones
                            </span>
                            <span class="distribution-value"><?php echo $totalOrganizaciones; ?> &nbsp; <?php echo $porcentajeOrg; ?>%</span>
                        </div>
                        <div class="distribution-bar">
                            <span class="bar-fill green" style="width: <?php echo $porcentajeOrg; ?>%;"></span>
                        </div>
                    </div>

                    <div class="distribution-item">
                        <div class="distribution-head">
                            <span class="distribution-label">
                                <i class="bi bi-shield-lock"></i> Administradores
                            </span>
                            <span class="distribution-value"><?php echo $totalAdmins; ?> &nbsp; <?php echo $porcentajeAdm; ?>%</span>
                        </div>
                        <div class="distribution-bar">
                            <span class="bar-fill orange" style="width: <?php echo $porcentajeAdm; ?>%;"></span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="superadmin-panel-card">
                <div class="panel-card-head">
                    <div>
                        <h2>Próximos desafíos por vencer</h2>
                        <p>Desafíos con fecha límite cercana</p>
                    </div>
                </div>

                <div class="upcoming-list">
                    <?php if (!empty($desafiosPorVencer)): ?>
                        <?php foreach ($desafiosPorVencer as $item): ?>
                            <?php
                            $fechaLimite = !empty($item['fecha_limite']) ? strtotime($item['fecha_limite']) : null;
                            $diasRestantes = $fechaLimite ? floor(($fechaLimite - time()) / 86400) : null;
                            ?>
                            <article class="upcoming-card">
                                <div>
                                    <h3><?php echo htmlspecialchars($item['titulo']); ?></h3>
                                    <p><?php echo htmlspecialchars($item['nombre_empresa']); ?></p>
                                    <small><?php echo (int) $item['total_propuestas']; ?> propuestas</small>
                                </div>

                                <div class="upcoming-meta">
                                    <?php if ($diasRestantes !== null && $diasRestantes >= 0): ?>
                                        <strong><?php echo $diasRestantes; ?> días</strong>
                                    <?php else: ?>
                                        <strong class="danger-text">Vencido</strong>
                                    <?php endif; ?>
                                    <span><?php echo htmlspecialchars(date('Y-m-d', strtotime($item['fecha_limite']))); ?></span>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="empty-text">No hay desafíos con fecha límite próxima.</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <section class="superadmin-panel-card full-card">
            <div class="panel-card-head">
                <div>
                    <h2>Pendientes de verificación</h2>
                    <p>Solicitudes de administradores que requieren aprobación antes de acceder</p>
                </div>
            </div>

            <div class="verification-list">
                <?php if (!empty($solicitudes)): ?>
                    <?php foreach ($solicitudes as $item): ?>
                        <article class="verification-item">
                            <div class="verification-main">
                                <h3>
                                    <?php
                                    echo htmlspecialchars(
                                        trim($item['nombre'] . ' ' . $item['apellido_paterno'] . ' ' . ($item['apellido_materno'] ?? ''))
                                    );
                                    ?>
                                </h3>

                                <div class="verification-badges">
                                    <span class="role-badge">Administrador</span>
                                    <?php if (!empty($item['departamento'])): ?>
                                        <span class="role-badge soft"><?php echo htmlspecialchars($item['departamento']); ?></span>
                                    <?php endif; ?>
                                </div>

                                <p><?php echo htmlspecialchars($item['correo_electronico']); ?></p>
                                <small>Registrado el <?php echo htmlspecialchars(date('Y-m-d', strtotime($item['fecha_registro']))); ?></small>
                            </div>

                            <div class="verification-actions">
                                <a href="usuarios/detalle_usuario_superadmin.php?id=<?php echo (int) $item['id_admin']; ?>" class="btn superadmin-btn-light">
                                    Ver detalles
                                </a>

                                <form action="../procesos/aprobar_admin.php" method="POST">
                                    <input type="hidden" name="id_admin" value="<?php echo (int) $item['id_admin']; ?>">
                                    <input type="hidden" name="redirect" value="../superadmin/dashboard_superadmin.php">
                                    <button type="submit" class="btn btn-primary btn-verify">Verificar</button>
                                </form>

                                <form action="../procesos/rechazar_admin.php" method="POST">
                                    <input type="hidden" name="id_admin" value="<?php echo (int) $item['id_admin']; ?>">
                                    <input type="hidden" name="redirect" value="../superadmin/dashboard_superadmin.php">
                                    <button type="submit" class="btn btn-reject">Rechazar</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="empty-text">No hay solicitudes pendientes por revisar.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="superadmin-panel-card full-card">
            <div class="panel-card-head">
                <div>
                    <h2>Actividad reciente</h2>
                    <p>Últimas acciones en la plataforma</p>
                </div>
            </div>

            <div class="activity-table-wrap">
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th>Usuario</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($actividadReciente)): ?>
                            <?php foreach ($actividadReciente as $act): ?>
                                <?php
                                $tipoEvento = $act['tipo_evento'] ?? 'Actividad';
                                $claseTag = 'tag-blue';

                                if ($tipoEvento === 'Nuevo desafío') {
                                    $claseTag = 'tag-green';
                                } elseif ($tipoEvento === 'Propuesta enviada') {
                                    $claseTag = 'tag-sky';
                                } elseif ($tipoEvento === 'Verificación') {
                                    $claseTag = 'tag-green';
                                } elseif ($tipoEvento === 'Rechazo') {
                                    $claseTag = 'tag-red';
                                } elseif ($tipoEvento === 'Solicitud admin') {
                                    $claseTag = 'tag-orange';
                                }

                                $fechaFormateada = !empty($act['fecha_accion'])
                                    ? date('Y-m-d H:i', strtotime($act['fecha_accion']))
                                    : 'Sin fecha';
                                ?>
                                <tr>
                                    <td>
                                        <span class="activity-tag <?php echo htmlspecialchars($claseTag); ?>">
                                            <?php echo htmlspecialchars($tipoEvento); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($act['descripcion'] ?? ''); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($act['usuario_nombre'] ?? 'Sistema'); ?>
                                        <?php if (!empty($act['usuario_rol'])): ?>
                                            <span class="mini-role"><?php echo htmlspecialchars($act['usuario_rol']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($fechaFormateada); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">No hay actividad reciente registrada.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="superadmin-bottom-actions">
            <a href="usuarios_superadmin.php" class="bottom-action-btn">
                <i class="bi bi-people"></i>
                Gestionar usuarios
            </a>
            <a href="solicitudes_admin.php" class="bottom-action-btn">
                <i class="bi bi-person-check"></i>
                Revisar solicitudes
            </a>
            <a href="reportes_superadmin.php" class="bottom-action-btn">
                <i class="bi bi-graph-up-arrow"></i>
                Ver reportes
            </a>
            <a href="dashboard_superadmin.php" class="bottom-action-btn">
                <i class="bi bi-house-door"></i>
                Volver al panel
            </a>
        </div>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
