<?php
require_once __DIR__ . '/../includes/session_admin.php';
require_once __DIR__ . '/../config/conexion.php';

$idAdmin = (int) ($_SESSION['usuario_id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT
        a.nombre,
        a.apellido_paterno,
        a.apellido_materno,
        a.puesto,
        a.departamento,
        a.tipo_admin,
        u.correo_electronico,
        u.fecha_registro
    FROM administrador a
    INNER JOIN usuario u ON u.id_usuario = a.id_admin
    WHERE a.id_admin = :id_admin
    LIMIT 1
");
$stmt->execute([':id_admin' => $idAdmin]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    $_SESSION['error'] = 'No se encontro el perfil del administrador.';
    header('Location: ../index.php');
    exit;
}

$nombreCompleto = trim(
    ($admin['nombre'] ?? '') . ' ' .
    ($admin['apellido_paterno'] ?? '') . ' ' .
    ($admin['apellido_materno'] ?? '')
);
$primerNombre = explode(' ', trim($admin['nombre'] ?? 'Administrador'))[0] ?: 'Administrador';

$totalUsuarios = (int) $pdo->query("SELECT COUNT(*) FROM usuario")->fetchColumn();
$totalEstudiantes = (int) $pdo->query("SELECT COUNT(*) FROM usuario WHERE rol = 'estudiante'")->fetchColumn();
$totalOrganizaciones = (int) $pdo->query("SELECT COUNT(*) FROM usuario WHERE rol = 'organizacion'")->fetchColumn();
$totalDesafios = (int) $pdo->query("SELECT COUNT(*) FROM desafio")->fetchColumn();
$desafiosActivos = (int) $pdo->query("SELECT COUNT(*) FROM desafio WHERE LOWER(COALESCE(estado, '')) = 'activo'")->fetchColumn();
$totalPropuestas = (int) $pdo->query("SELECT COUNT(*) FROM propuesta")->fetchColumn();
$propuestasPendientes = (int) $pdo->query("SELECT COUNT(*) FROM propuesta WHERE LOWER(COALESCE(estado, '')) IN ('pendiente', 'en revision', 'en revisión')")->fetchColumn();
$usuariosActivos = (int) $pdo->query("SELECT COUNT(*) FROM usuario WHERE LOWER(COALESCE(estado, '')) = 'activo'")->fetchColumn();

$stmt = $pdo->query("
    SELECT rol, COUNT(*) AS total
    FROM usuario
    GROUP BY rol
    ORDER BY total DESC
");
$usuariosPorRol = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT estado, COUNT(*) AS total
    FROM propuesta
    GROUP BY estado
    ORDER BY total DESC
    LIMIT 5
");
$propuestasPorEstado = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT
        d.id_desafio,
        d.titulo,
        d.estado,
        d.fecha_publicacion,
        d.fecha_limite,
        o.nombre_empresa,
        COUNT(p.id_propuesta) AS total_propuestas
    FROM desafio d
    INNER JOIN organizacion o ON o.id_organizacion = d.id_organizacion
    LEFT JOIN propuesta p ON p.id_desafio = d.id_desafio
    GROUP BY d.id_desafio, d.titulo, d.estado, d.fecha_publicacion, d.fecha_limite, o.nombre_empresa
    ORDER BY d.fecha_publicacion DESC
    LIMIT 5
");
$desafiosRecientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT
        u.id_usuario,
        u.rol,
        u.estado,
        u.fecha_registro,
        COALESCE(
            CONCAT_WS(' ', e.nombre, e.apellido_paterno, e.apellido_materno),
            o.nombre_empresa,
            CONCAT_WS(' ', a.nombre, a.apellido_paterno, a.apellido_materno),
            u.correo_electronico
        ) AS nombre
    FROM usuario u
    LEFT JOIN estudiante e ON e.id_estudiante = u.id_usuario
    LEFT JOIN organizacion o ON o.id_organizacion = u.id_usuario
    LEFT JOIN administrador a ON a.id_admin = u.id_usuario
    ORDER BY u.fecha_registro DESC
    LIMIT 6
");
$usuariosRecientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

function admin_dashboard_pct(int $value, int $total): int
{
    if ($total <= 0) {
        return 0;
    }

    return max(0, min(100, (int) round(($value / $total) * 100)));
}

function admin_dashboard_status_class(?string $estado): string
{
    $estado = strtolower(trim((string) $estado));

    if (in_array($estado, ['activo', 'aceptada', 'aprobada'], true)) {
        return 'is-ok';
    }

    if (in_array($estado, ['pendiente', 'en revision', 'en revisión', 'borrador'], true)) {
        return 'is-waiting';
    }

    if (in_array($estado, ['cerrado', 'rechazada', 'rechazado', 'inactivo'], true)) {
        return 'is-danger';
    }

    return 'is-neutral';
}

$porcentajeUsuariosActivos = admin_dashboard_pct($usuariosActivos, $totalUsuarios);
$porcentajeDesafiosActivos = admin_dashboard_pct($desafiosActivos, $totalDesafios);
$porcentajePropuestasPendientes = admin_dashboard_pct($propuestasPendientes, $totalPropuestas);

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard administrador | EduNexo MP</title>

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin/dashboard_admin.css?v=admin-dashboard-2">
    <link rel="stylesheet" href="../assets/css/dark.css?v=dark-fix-6">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <main class="app-content admin-content">
        <?php include __DIR__ . '/../includes/app_topbar.php'; ?>

        <section class="admin-dashboard-hero">
            <div>
                <span class="admin-dashboard-badge">Panel administrativo</span>
                <h1>Hola, <?php echo htmlspecialchars($primerNombre); ?></h1>
                <p>
                    Revisa el estado general de usuarios, desafios y propuestas dentro de EduNexo MP.
                </p>
            </div>

            <div class="admin-dashboard-identity">
                <span><?php echo htmlspecialchars($admin['puesto'] ?: 'Administrador'); ?></span>
                <strong><?php echo htmlspecialchars($nombreCompleto ?: 'Administrador'); ?></strong>
                <small><?php echo htmlspecialchars($admin['departamento'] ?: 'Departamento no registrado'); ?></small>
            </div>
        </section>

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

        <section class="admin-summary-grid">
            <article class="admin-summary-card">
                <div>
                    <span>Usuarios</span>
                    <strong><?php echo $totalUsuarios; ?></strong>
                    <p><?php echo $usuariosActivos; ?> activos</p>
                </div>
                <i class="bi bi-people"></i>
            </article>

            <article class="admin-summary-card">
                <div>
                    <span>Estudiantes</span>
                    <strong><?php echo $totalEstudiantes; ?></strong>
                    <p>Perfiles academicos</p>
                </div>
                <i class="bi bi-mortarboard"></i>
            </article>

            <article class="admin-summary-card">
                <div>
                    <span>Organizaciones</span>
                    <strong><?php echo $totalOrganizaciones; ?></strong>
                    <p>Empresas registradas</p>
                </div>
                <i class="bi bi-building"></i>
            </article>

            <article class="admin-summary-card">
                <div>
                    <span>Propuestas</span>
                    <strong><?php echo $totalPropuestas; ?></strong>
                    <p><?php echo $propuestasPendientes; ?> pendientes</p>
                </div>
                <i class="bi bi-send"></i>
            </article>
        </section>

        <section class="admin-dashboard-grid">
            <article class="admin-panel">
                <div class="admin-panel-head">
                    <div>
                        <h2>Salud operativa</h2>
                        <p>Indicadores rapidos del estado actual.</p>
                    </div>
                </div>

                <div class="admin-progress-list">
                    <div class="admin-progress-item">
                        <div>
                            <span>Usuarios activos</span>
                            <strong><?php echo $porcentajeUsuariosActivos; ?>%</strong>
                        </div>
                        <div class="admin-progress-track">
                            <span style="width: <?php echo $porcentajeUsuariosActivos; ?>%;"></span>
                        </div>
                    </div>

                    <div class="admin-progress-item">
                        <div>
                            <span>Desafios activos</span>
                            <strong><?php echo $porcentajeDesafiosActivos; ?>%</strong>
                        </div>
                        <div class="admin-progress-track">
                            <span style="width: <?php echo $porcentajeDesafiosActivos; ?>%;"></span>
                        </div>
                    </div>

                    <div class="admin-progress-item">
                        <div>
                            <span>Propuestas pendientes</span>
                            <strong><?php echo $porcentajePropuestasPendientes; ?>%</strong>
                        </div>
                        <div class="admin-progress-track warning">
                            <span style="width: <?php echo $porcentajePropuestasPendientes; ?>%;"></span>
                        </div>
                    </div>
                </div>
            </article>

            <article class="admin-panel">
                <div class="admin-panel-head">
                    <div>
                        <h2>Accesos rapidos</h2>
                        <p>Herramientas disponibles para tu rol.</p>
                    </div>
                </div>

                <div class="admin-quick-actions">
                    <a href="usuarios_admin.php">
                        <i class="bi bi-people"></i>
                        <span>Usuarios</span>
                    </a>
                    <a href="desafios_admin.php">
                        <i class="bi bi-file-earmark-text"></i>
                        <span>Desafios</span>
                    </a>
                    <a href="propuestas_admin.php">
                        <i class="bi bi-send"></i>
                        <span>Propuestas</span>
                    </a>
                    <a href="reportes_admin.php">
                        <i class="bi bi-clipboard-data"></i>
                        <span>Reportes</span>
                    </a>
                    <a href="perfil_admin.php">
                        <i class="bi bi-person"></i>
                        <span>Mi perfil</span>
                    </a>
                    <a href="../ayuda.php">
                        <i class="bi bi-question-circle"></i>
                        <span>Centro de ayuda</span>
                    </a>
                </div>
            </article>
        </section>

        <section class="admin-dashboard-grid wide-left">
            <article class="admin-panel">
                <div class="admin-panel-head">
                    <div>
                        <h2>Desafios recientes</h2>
                        <p>Ultimas publicaciones realizadas por organizaciones.</p>
                    </div>
                </div>

                <div class="admin-list">
                    <?php if (!empty($desafiosRecientes)): ?>
                        <?php foreach ($desafiosRecientes as $desafio): ?>
                            <article class="admin-list-item">
                                <div>
                                    <h3><?php echo htmlspecialchars($desafio['titulo']); ?></h3>
                                    <p>
                                        <?php echo htmlspecialchars($desafio['nombre_empresa']); ?>
                                        &middot; <?php echo (int) $desafio['total_propuestas']; ?> propuestas
                                    </p>
                                </div>
                                <span class="admin-status <?php echo admin_dashboard_status_class($desafio['estado'] ?? ''); ?>">
                                    <?php echo htmlspecialchars($desafio['estado'] ?: 'Sin estado'); ?>
                                </span>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="admin-empty">Aun no hay desafios registrados.</p>
                    <?php endif; ?>
                </div>
            </article>

            <article class="admin-panel">
                <div class="admin-panel-head">
                    <div>
                        <h2>Usuarios recientes</h2>
                        <p>Nuevas cuentas creadas en la plataforma.</p>
                    </div>
                </div>

                <div class="admin-list compact">
                    <?php if (!empty($usuariosRecientes)): ?>
                        <?php foreach ($usuariosRecientes as $usuario): ?>
                            <article class="admin-list-item">
                                <div>
                                    <h3><?php echo htmlspecialchars($usuario['nombre'] ?: 'Usuario'); ?></h3>
                                    <p>
                                        <?php echo htmlspecialchars(ucfirst($usuario['rol'] ?? 'Sin rol')); ?>
                                        <?php if (!empty($usuario['fecha_registro'])): ?>
                                            &middot; <?php echo htmlspecialchars(date('d/m/Y', strtotime($usuario['fecha_registro']))); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <span class="admin-status <?php echo admin_dashboard_status_class($usuario['estado'] ?? ''); ?>">
                                    <?php echo htmlspecialchars($usuario['estado'] ?: 'Sin estado'); ?>
                                </span>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="admin-empty">Aun no hay usuarios registrados.</p>
                    <?php endif; ?>
                </div>
            </article>
        </section>

        <section class="admin-dashboard-grid">
            <article class="admin-panel">
                <div class="admin-panel-head">
                    <div>
                        <h2>Usuarios por rol</h2>
                        <p>Distribucion general de cuentas.</p>
                    </div>
                </div>

                <div class="admin-pill-list">
                    <?php foreach ($usuariosPorRol as $item): ?>
                        <div>
                            <span><?php echo htmlspecialchars(ucfirst($item['rol'] ?? 'Sin rol')); ?></span>
                            <strong><?php echo (int) $item['total']; ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>

            <article class="admin-panel">
                <div class="admin-panel-head">
                    <div>
                        <h2>Propuestas por estado</h2>
                        <p>Resumen del flujo de postulaciones.</p>
                    </div>
                </div>

                <div class="admin-pill-list">
                    <?php foreach (($propuestasPorEstado ?: [['estado' => 'Sin propuestas', 'total' => 0]]) as $item): ?>
                        <div>
                            <span><?php echo htmlspecialchars($item['estado'] ?: 'Sin estado'); ?></span>
                            <strong><?php echo (int) $item['total']; ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        </section>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
