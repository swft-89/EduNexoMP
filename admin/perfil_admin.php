<?php
require_once __DIR__ . '/../includes/session_admin.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/profile_photo.php';

$idAdmin = (int) ($_SESSION['usuario_id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT
        a.id_admin,
        a.nombre,
        a.apellido_paterno,
        a.apellido_materno,
        a.puesto,
        a.departamento,
        a.tipo_admin,
        a.estado_solicitud,
        a.fecha_autorizacion,
        a.foto_url,
        u.correo_electronico,
        u.estado,
        u.fecha_registro
    FROM administrador a
    INNER JOIN usuario u ON u.id_usuario = a.id_admin
    WHERE a.id_admin = :id_admin
    LIMIT 1
");
$stmt->execute([':id_admin' => $idAdmin]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    $_SESSION['error'] = 'No se encontro la informacion del administrador.';
    header('Location: dashboard_admin.php');
    exit;
}

$totalUsuarios = (int) $pdo->query("SELECT COUNT(*) FROM usuario")->fetchColumn();
$totalDesafios = (int) $pdo->query("SELECT COUNT(*) FROM desafio")->fetchColumn();
$totalPropuestas = (int) $pdo->query("SELECT COUNT(*) FROM propuesta")->fetchColumn();
$totalAdmins = (int) $pdo->query("SELECT COUNT(*) FROM usuario WHERE rol = 'administrador'")->fetchColumn();

$stmtActividad = $pdo->query("
    SELECT tipo_evento, descripcion, usuario_nombre, usuario_rol, fecha_accion
    FROM auditoria_admin
    ORDER BY fecha_accion DESC
    LIMIT 5
");
$actividadReciente = $stmtActividad->fetchAll(PDO::FETCH_ASSOC);

function admin_profile_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $initials = '';

    foreach ($parts as $part) {
        if ($part !== '') {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        if (mb_strlen($initials) >= 2) {
            break;
        }
    }

    return $initials ?: 'AD';
}

function admin_profile_value($value, string $fallback = 'No registrado'): string
{
    $value = trim((string) ($value ?? ''));
    return $value !== '' ? $value : $fallback;
}

$nombreCompleto = trim(
    ($admin['nombre'] ?? '') . ' ' .
    ($admin['apellido_paterno'] ?? '') . ' ' .
    ($admin['apellido_materno'] ?? '')
);
$iniciales = admin_profile_initials($nombreCompleto);
$tipoAdmin = ($admin['tipo_admin'] ?? '') === 'superadmin' ? 'Superadministrador' : 'Administrador';
$dashboardUrl = ($admin['tipo_admin'] ?? '') === 'superadmin'
    ? '../superadmin/dashboard_superadmin.php'
    : 'dashboard_admin.php';

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi perfil administrativo | EduNexo MP</title>

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin/perfil_admin.css?v=admin-profile-1">
    <link rel="stylesheet" href="../assets/css/dark.css?v=dark-fix-6">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <main class="app-content">
        <?php include __DIR__ . '/../includes/app_topbar.php'; ?>

        <div class="admin-profile-page">
            <section class="admin-profile-hero">
                <div class="admin-profile-hero-top">
                    <div class="admin-profile-hero-user">
                        <div class="admin-profile-avatar">
                            <?php if (!empty($admin['foto_url'])): ?>
                                <img src="<?php echo htmlspecialchars(edunexo_asset_url($admin['foto_url'])); ?>" alt="Foto de perfil">
                            <?php else: ?>
                                <?php echo htmlspecialchars($iniciales); ?>
                            <?php endif; ?>
                        </div>

                        <div>
                            <h1><?php echo htmlspecialchars($nombreCompleto ?: 'Mi perfil'); ?></h1>
                            <p>
                                <?php echo htmlspecialchars($tipoAdmin); ?>
                                &middot; <?php echo htmlspecialchars(admin_profile_value($admin['puesto'] ?? null, 'Puesto no registrado')); ?>
                                <?php if (!empty($admin['departamento'])): ?>
                                    &middot; <?php echo htmlspecialchars($admin['departamento']); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <div class="admin-profile-hero-actions">
                        <a href="editar_perfil_admin.php" class="btn btn-primary">
                            <i class="bi bi-pencil-square"></i>
                            Editar perfil
                        </a>
                    </div>
                </div>

                <div class="admin-profile-stats">
                    <div class="admin-profile-stat">
                        <span>Usuarios</span>
                        <strong><?php echo $totalUsuarios; ?></strong>
                    </div>
                    <div class="admin-profile-stat">
                        <span>Desafios</span>
                        <strong><?php echo $totalDesafios; ?></strong>
                    </div>
                    <div class="admin-profile-stat">
                        <span>Propuestas</span>
                        <strong><?php echo $totalPropuestas; ?></strong>
                    </div>
                    <div class="admin-profile-stat">
                        <span>Admins</span>
                        <strong><?php echo $totalAdmins; ?></strong>
                    </div>
                </div>
            </section>

            <div class="admin-profile-overview-grid">
                <aside class="admin-profile-account-card">
                    <div class="admin-profile-account-avatar">
                        <?php if (!empty($admin['foto_url'])): ?>
                            <img src="<?php echo htmlspecialchars(edunexo_asset_url($admin['foto_url'])); ?>" alt="Foto de perfil">
                        <?php else: ?>
                            <?php echo htmlspecialchars($iniciales); ?>
                        <?php endif; ?>
                    </div>

                    <h2><?php echo htmlspecialchars($nombreCompleto ?: 'Administrador'); ?></h2>
                    <p><?php echo htmlspecialchars(admin_profile_value($admin['correo_electronico'] ?? null)); ?></p>

                    <div class="admin-profile-quick-list">
                        <div>
                            <i class="bi bi-shield-check"></i>
                            <span><?php echo htmlspecialchars($tipoAdmin); ?></span>
                        </div>
                        <div>
                            <i class="bi bi-briefcase"></i>
                            <span><?php echo htmlspecialchars(admin_profile_value($admin['puesto'] ?? null, 'Puesto no registrado')); ?></span>
                        </div>
                        <div>
                            <i class="bi bi-diagram-3"></i>
                            <span><?php echo htmlspecialchars(admin_profile_value($admin['departamento'] ?? null, 'Departamento no registrado')); ?></span>
                        </div>
                    </div>

                    <a href="editar_perfil_admin.php" class="btn btn-primary admin-profile-full-btn">
                        <i class="bi bi-pencil-square"></i>
                        Editar mis datos
                    </a>
                </aside>

                <section class="admin-profile-activity-card">
                    <div class="admin-profile-card-head">
                        <div>
                            <h2>Resumen administrativo</h2>
                            <p>Datos de tu cuenta y accesos principales dentro de EduNexo MP.</p>
                        </div>
                        <a href="<?php echo htmlspecialchars($dashboardUrl); ?>" class="btn btn-nav">
                            <i class="bi bi-grid"></i>
                            Dashboard
                        </a>
                    </div>

                    <div class="admin-profile-info-grid">
                        <div class="admin-profile-info">
                            <span>Correo electronico</span>
                            <strong><?php echo htmlspecialchars(admin_profile_value($admin['correo_electronico'] ?? null)); ?></strong>
                        </div>
                        <div class="admin-profile-info">
                            <span>Estado de cuenta</span>
                            <strong><?php echo htmlspecialchars(admin_profile_value($admin['estado'] ?? null)); ?></strong>
                        </div>
                        <div class="admin-profile-info">
                            <span>Solicitud admin</span>
                            <strong><?php echo htmlspecialchars(admin_profile_value($admin['estado_solicitud'] ?? null)); ?></strong>
                        </div>
                        <div class="admin-profile-info">
                            <span>Registro</span>
                            <strong>
                                <?php echo !empty($admin['fecha_registro']) ? htmlspecialchars(date('d/m/Y H:i', strtotime($admin['fecha_registro']))) : 'No registrado'; ?>
                            </strong>
                        </div>
                    </div>
                </section>
            </div>

            <section class="admin-profile-card" style="margin-top: 24px;">
                <div class="admin-profile-card-head">
                    <div>
                        <h2>Actividad reciente</h2>
                        <p>Ultimos movimientos administrativos registrados en la plataforma.</p>
                    </div>
                </div>

                <?php if (!empty($actividadReciente)): ?>
                    <div class="admin-profile-meta">
                        <?php foreach ($actividadReciente as $actividad): ?>
                            <div>
                                <i class="bi bi-clock-history"></i>
                                <span>
                                    <strong><?php echo htmlspecialchars($actividad['tipo_evento'] ?? 'Actividad'); ?></strong>
                                    <?php echo htmlspecialchars(' - ' . ($actividad['descripcion'] ?? 'Sin descripcion')); ?>
                                    <?php if (!empty($actividad['fecha_accion'])): ?>
                                        <?php echo htmlspecialchars(' (' . date('d/m/Y H:i', strtotime($actividad['fecha_accion'])) . ')'); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="admin-profile-empty">No hay actividad administrativa reciente.</p>
                <?php endif; ?>
            </section>
        </div>
    </main>
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
