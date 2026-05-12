<?php
require_once __DIR__ . '/../../includes/session_superadmin.php';
require_once __DIR__ . '/../../config/conexion.php';

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

$stmtSolicitudes = $pdo->query("
    SELECT
        a.id_admin,
        a.nombre,
        a.apellido_paterno,
        a.apellido_materno,
        a.puesto,
        a.departamento,
        a.estado_solicitud,
        u.correo_electronico,
        u.fecha_registro
    FROM administrador a
    INNER JOIN usuario u ON u.id_usuario = a.id_admin
    WHERE a.estado_solicitud IN ('pendiente', 'aprobado', 'rechazado')
    ORDER BY u.fecha_registro DESC
");
$solicitudes = $stmtSolicitudes->fetchAll(PDO::FETCH_ASSOC);

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitudes admin | Superadmin - EduNexo MP</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/dark.css">
    <link rel="stylesheet" href="../../assets/css/superadmin/dashboard_superadmin.css">
    <link rel="stylesheet" href="../../assets/css/superadmin/superadmin_sections.css">
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
            <a href="../dashboard_superadmin.php">
                <i class="bi bi-bar-chart-line"></i> Dashboard
            </a>
            <a href="../usuarios/usuarios_superadmin.php">
                <i class="bi bi-people"></i> Usuarios
            </a>
            <a href="../usuarios/solicitudes_admin.php" class="active">
                <i class="bi bi-person-check"></i> Solicitudes admin
            </a>
            <a href="../reportes_superadmin.php">
                <i class="bi bi-clipboard-data"></i> Reportes
            </a>
            <a href="../desafios/desafios_superadmin.php">
                <i class="bi bi-file-earmark-text"></i> Desafíos
            </a>
            <a href="../propuestas/propuestas_superadmin.php">
                <i class="bi bi-send"></i> Propuestas
            </a>
            <a href="../categorias_superadmin.php">
                <i class="bi bi-tags"></i> Categorías
            </a>
        </nav>
    </aside>

    <section class="app-content superadmin-content">
        <?php include __DIR__ . '/../../includes/app_topbar.php'; ?>

        <div class="superadmin-header">
            <div>
                <h1>Solicitudes de administradores</h1>
                <p>Revisa, aprueba o rechaza cuentas que solicitan acceso administrativo.</p>
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

        <section class="superadmin-panel-card full-card">
            <div class="panel-card-head">
                <div>
                    <h2>Listado de solicitudes</h2>
                    <p>Historial y solicitudes activas de cuentas administrativas</p>
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

                                    <?php
                                    $estadoSolicitud = $item['estado_solicitud'] ?? 'pendiente';
                                    $claseEstado = 'status-pill inactive';
                                    if ($estadoSolicitud === 'pendiente') {
                                        $claseEstado = 'status-pill pending';
                                    } elseif ($estadoSolicitud === 'aprobado') {
                                        $claseEstado = 'status-pill active';
                                    } elseif ($estadoSolicitud === 'rechazado') {
                                        $claseEstado = 'status-pill suspended';
                                    }
                                    ?>
                                    <span class="<?php echo $claseEstado; ?>">
                                        <?php echo htmlspecialchars($estadoSolicitud); ?>
                                    </span>
                                </div>

                                <p><?php echo htmlspecialchars($item['correo_electronico']); ?></p>
                                <small>
                                    <?php echo htmlspecialchars($item['puesto'] ?: 'Sin puesto especificado'); ?> ·
                                    <?php echo htmlspecialchars(date('Y-m-d', strtotime($item['fecha_registro']))); ?>
                                </small>
                            </div>

                            <div class="verification-actions">
                                <?php if (($item['estado_solicitud'] ?? '') === 'pendiente'): ?>
                                    <form action="../../procesos/aprobar_admin.php" method="POST">
                                        <input type="hidden" name="id_admin" value="<?php echo (int) $item['id_admin']; ?>">
                                        <input type="hidden" name="redirect" value="../superadmin/usuarios/solicitudes_admin.php">
                                        <button type="submit" class="btn btn-primary btn-verify">Verificar</button>
                                    </form>

                                    <form action="../../procesos/rechazar_admin.php" method="POST">
                                        <input type="hidden" name="id_admin" value="<?php echo (int) $item['id_admin']; ?>">
                                        <input type="hidden" name="redirect" value="../superadmin/usuarios/solicitudes_admin.php">
                                        <button type="submit" class="btn btn-reject">Rechazar</button>
                                    </form>
                                <?php else: ?>
                                    <a href="detalle_usuario_superadmin.php?id=<?php echo (int) $item['id_admin']; ?>" class="btn superadmin-btn-light btn-sm">
                                        Ver detalles
                                    </a>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="empty-text">No hay solicitudes de administradores registradas.</p>
                <?php endif; ?>
            </div>
        </section>
    </section>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../../assets/js/main.js"></script>
</body>
</html>
