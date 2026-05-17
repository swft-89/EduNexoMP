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
        a.foto_url,
        u.correo_electronico
    FROM administrador a
    INNER JOIN usuario u ON u.id_usuario = a.id_admin
    WHERE a.id_admin = :id_admin
    LIMIT 1
");
$stmt->execute([':id_admin' => $idAdmin]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    $_SESSION['error'] = 'No se encontro la informacion del administrador.';
    header('Location: perfil_admin.php');
    exit;
}

function admin_edit_initials(string $name): string
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

function admin_old_value(string $key, $default, array $old): string
{
    return htmlspecialchars($old[$key] ?? ($default ?? ''));
}

$nombreCompleto = trim(
    ($admin['nombre'] ?? '') . ' ' .
    ($admin['apellido_paterno'] ?? '') . ' ' .
    ($admin['apellido_materno'] ?? '')
);
$iniciales = admin_edit_initials($nombreCompleto);
$tipoAdmin = ($admin['tipo_admin'] ?? '') === 'superadmin' ? 'Superadministrador' : 'Administrador';

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
$old = $_SESSION['old'] ?? [];
unset($_SESSION['success'], $_SESSION['error'], $_SESSION['old']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar perfil administrativo | EduNexo MP</title>

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
                            <h1>Editar perfil</h1>
                            <p>
                                <?php echo htmlspecialchars($nombreCompleto ?: 'Administrador'); ?>
                                &middot; <?php echo htmlspecialchars($tipoAdmin); ?>
                            </p>
                        </div>
                    </div>

                    <div class="admin-profile-hero-actions">
                        <a href="perfil_admin.php" class="btn btn-nav">
                            <i class="bi bi-arrow-left"></i>
                            Volver
                        </a>
                    </div>
                </div>
            </section>

            <section class="admin-profile-card">
                <div class="admin-profile-card-head">
                    <div>
                        <h2>Datos del administrador</h2>
                        <p>Actualiza tu informacion visible y la imagen que aparece en el topbar.</p>
                    </div>
                </div>

                <form action="../procesos/admin/guardar_perfil_admin.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="foto_url_actual" value="<?php echo htmlspecialchars($admin['foto_url'] ?? ''); ?>">

                    <div class="admin-profile-photo-field" style="margin-bottom: 22px;">
                        <div class="admin-profile-avatar">
                            <?php if (!empty($admin['foto_url'])): ?>
                                <img src="<?php echo htmlspecialchars(edunexo_asset_url($admin['foto_url'])); ?>" alt="Foto de perfil">
                            <?php else: ?>
                                <?php echo htmlspecialchars($iniciales); ?>
                            <?php endif; ?>
                        </div>
                        <div class="admin-profile-form-group" style="flex: 1;">
                            <label for="foto_perfil">Foto de perfil</label>
                            <input type="file" id="foto_perfil" name="foto_perfil" accept="image/png,image/jpeg,image/webp">
                            <p class="admin-profile-help">Formatos permitidos: JPG, PNG o WEBP. Maximo 2 MB.</p>
                        </div>
                    </div>

                    <div class="admin-profile-form-grid">
                        <div class="admin-profile-form-group">
                            <label for="nombre">Nombre *</label>
                            <input type="text" id="nombre" name="nombre" value="<?php echo admin_old_value('nombre', $admin['nombre'] ?? '', $old); ?>" required>
                        </div>

                        <div class="admin-profile-form-group">
                            <label for="apellido_paterno">Apellido paterno *</label>
                            <input type="text" id="apellido_paterno" name="apellido_paterno" value="<?php echo admin_old_value('apellido_paterno', $admin['apellido_paterno'] ?? '', $old); ?>" required>
                        </div>

                        <div class="admin-profile-form-group">
                            <label for="apellido_materno">Apellido materno</label>
                            <input type="text" id="apellido_materno" name="apellido_materno" value="<?php echo admin_old_value('apellido_materno', $admin['apellido_materno'] ?? '', $old); ?>">
                        </div>

                        <div class="admin-profile-form-group">
                            <label for="correo_electronico">Correo electronico *</label>
                            <input type="email" id="correo_electronico" name="correo_electronico" value="<?php echo admin_old_value('correo_electronico', $admin['correo_electronico'] ?? '', $old); ?>" required>
                        </div>

                        <div class="admin-profile-form-group">
                            <label for="puesto">Puesto</label>
                            <input type="text" id="puesto" name="puesto" value="<?php echo admin_old_value('puesto', $admin['puesto'] ?? '', $old); ?>">
                        </div>

                        <div class="admin-profile-form-group">
                            <label for="departamento">Departamento</label>
                            <input type="text" id="departamento" name="departamento" value="<?php echo admin_old_value('departamento', $admin['departamento'] ?? '', $old); ?>">
                        </div>
                    </div>

                    <div class="admin-profile-actions" style="margin-top: 24px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2"></i>
                            Guardar cambios
                        </button>
                        <a href="perfil_admin.php" class="btn btn-nav">
                            Cancelar
                        </a>
                    </div>
                </form>
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
