<?php
require_once __DIR__ . '../../includes/session_superadmin.php';
require_once __DIR__ . '../../config/conexion.php';

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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes | Superadmin - EduNexo MP</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dark.css">
    <link rel="stylesheet" href="../assets/css/superadmin/dashboard_superadmin.css">
    <link rel="stylesheet" href="../assets/css/superadmin/superadmin_sections.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
<div class="app-layout">
    <aside class="sidebar">
        <div class="sidebar-top">
            <div class="logo-mini">EN</div>
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
                <h1>Reportes del sistema</h1>
                <p>Resumen estadístico del comportamiento general de la plataforma.</p>
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
        </section>
    </section>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>