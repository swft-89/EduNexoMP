<?php
require_once '../includes/session_estudiante.php';
require_once '../config/conexion.php';
require_once '../includes/profile_photo.php';

$idUsuario = $_SESSION['usuario_id'] ?? null;

if (!$idUsuario) {
    header('Location: ../index.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        e.id_estudiante,
        e.nombre,
        e.apellido_paterno,
        e.apellido_materno,
        e.carrera,
        e.no_control,
        e.semestre,
        e.curp,
        e.telefono,
        e.foto_url,
        e.id_direccion,
        u.correo_electronico,
        d.pais,
        d.estado,
        d.ciudad,
        d.colonia,
        d.codigo_postal,
        d.calle,
        d.num_exterior
    FROM estudiante e
    INNER JOIN usuario u
        ON u.id_usuario = e.id_estudiante
    LEFT JOIN direccion d
        ON d.id_direccion = e.id_direccion
    WHERE e.id_estudiante = :id_estudiante
    LIMIT 1
");
$stmt->execute([':id_estudiante' => $idUsuario]);
$estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$estudiante) {
    $_SESSION['error'] = 'No se encontró la información del estudiante.';
    header('Location: dashboard_estudiante.php');
    exit;
}

function obtenerInicialesPerfil($nombreCompleto)
{
    $partes = preg_split('/\s+/', trim($nombreCompleto));
    $iniciales = '';

    foreach ($partes as $parte) {
        if ($parte !== '') {
            $iniciales .= mb_strtoupper(mb_substr($parte, 0, 1));
        }
        if (mb_strlen($iniciales) >= 2) {
            break;
        }
    }

    return $iniciales ?: 'E';
}

$nombreCompleto = trim(
    ($estudiante['nombre'] ?? '') . ' ' .
    ($estudiante['apellido_paterno'] ?? '') . ' ' .
    ($estudiante['apellido_materno'] ?? '')
);

$iniciales = obtenerInicialesPerfil($nombreCompleto);

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
$old = $_SESSION['old'] ?? [];
unset($_SESSION['success'], $_SESSION['error'], $_SESSION['old']);

function oldValue($key, $default, $old)
{
    return htmlspecialchars($old[$key] ?? $default ?? '');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar perfil | EduNexo MP</title>

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/estudiante/perfil_estudiante.css">
    <link rel="stylesheet" href="../assets/css/dark.css">
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
            <a href="dashboard_estudiante.php">
                <i class="bi bi-house-door"></i> Inicio
            </a>
            <a href="mis_propuestas.php">
                <i class="bi bi-file-earmark-text"></i> Mis propuestas
            </a>
            <a href="mis_favoritos.php">
                <i class="bi bi-heart"></i> Favoritos
            </a>
            <a href="chat.php">
                <i class="bi bi-chat"></i> Chat
            </a>
            <a href="habilidades_estudiante.php">
                <i class="bi bi-stars"></i> Mis habilidades
            </a>
            <a href="editar_perfil_estudiante.php" class="active">
                <i class="bi bi-person"></i> Perfil
            </a>
        </nav>
    </aside>

    <main class="app-content">
        <?php include __DIR__ . '/../includes/app_topbar.php'; ?>
        <div class="profile-page">

            <section class="profile-hero">
                <div class="profile-hero-top">
                    <div class="profile-hero-user">
                        <div class="profile-avatar">
                            <?php if (!empty($estudiante['foto_url'])): ?>
                                <img src="<?php echo htmlspecialchars(edunexo_asset_url($estudiante['foto_url'])); ?>" alt="Foto de perfil">
                            <?php else: ?>
                                <?php echo htmlspecialchars($iniciales); ?>
                            <?php endif; ?>
                        </div>

                        <div>
                            <h1>Editar perfil</h1>
                            <p>
                                <?php echo htmlspecialchars($nombreCompleto); ?>
                                · <?php echo htmlspecialchars($estudiante['carrera']); ?>
                                · <?php echo (int)$estudiante['semestre']; ?>° semestre
                            </p>
                        </div>
                    </div>

                    <div class="profile-hero-actions">
                        <a href="dashboard_estudiante.php" class="btn btn-nav">
                            <i class="bi bi-arrow-left"></i>
                            Volver
                        </a>
                    </div>
                </div>
            </section>

            <section class="profile-card">
                <div class="profile-card-head">
                    <div>
                        <h2>Información del estudiante</h2>
                        <p>Actualiza tus datos personales, académicos y de contacto.</p>
                    </div>
                </div>

                <form action="../procesos/estudiante/guardar_perfil_estudiante.php" method="POST" enctype="multipart/form-data" class="profile-form" novalidate>
                    <input type="hidden" name="foto_url_actual" value="<?php echo htmlspecialchars($estudiante['foto_url'] ?? ''); ?>">

                    <div class="profile-section">
                        <h3>Datos personales</h3>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="nombre">Nombre</label>
                                <input type="text" id="nombre" name="nombre" maxlength="100" required
                                    value="<?php echo oldValue('nombre', $estudiante['nombre'], $old); ?>">
                            </div>

                            <div class="form-group">
                                <label for="apellido_paterno">Apellido paterno</label>
                                <input type="text" id="apellido_paterno" name="apellido_paterno" maxlength="100" required
                                    value="<?php echo oldValue('apellido_paterno', $estudiante['apellido_paterno'], $old); ?>">
                            </div>

                            <div class="form-group">
                                <label for="apellido_materno">Apellido materno</label>
                                <input type="text" id="apellido_materno" name="apellido_materno" maxlength="100"
                                    value="<?php echo oldValue('apellido_materno', $estudiante['apellido_materno'], $old); ?>">
                            </div>

                            <div class="form-group">
                                <label for="curp">CURP</label>
                                <input type="text" id="curp" name="curp" maxlength="18"
                                    value="<?php echo oldValue('curp', $estudiante['curp'], $old); ?>">
                            </div>

                            <div class="form-group">
                                <label for="telefono">Teléfono</label>
                                <input type="text" id="telefono" name="telefono" maxlength="20"
                                    value="<?php echo oldValue('telefono', $estudiante['telefono'], $old); ?>">
                            </div>

                            <div class="form-group">
                                <label for="correo_electronico">Correo electrónico</label>
                                <input type="email" id="correo_electronico" name="correo_electronico" maxlength="120" required
                                    value="<?php echo oldValue('correo_electronico', $estudiante['correo_electronico'], $old); ?>">
                            </div>

                            <div class="form-group full">
                                <label for="foto_perfil">Foto de perfil</label>
                                <input type="file" id="foto_perfil" name="foto_perfil" accept="image/jpeg,image/png,image/webp">
                                <small>JPG, PNG o WEBP. Máximo 2 MB.</small>
                            </div>

                        </div>
                    </div>

                    <div class="profile-section">
                        <h3>Datos académicos</h3>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="carrera">Carrera</label>
                                <input type="text" id="carrera" name="carrera" maxlength="150" required
                                    value="<?php echo oldValue('carrera', $estudiante['carrera'], $old); ?>">
                            </div>

                            <div class="form-group">
                                <label for="no_control">Número de control</label>
                                <input type="text" id="no_control" name="no_control" maxlength="20" required
                                    value="<?php echo oldValue('no_control', $estudiante['no_control'], $old); ?>">
                            </div>

                            <div class="form-group">
                                <label for="semestre">Semestre</label>
                                <input type="number" id="semestre" name="semestre" min="1" max="20" required
                                    value="<?php echo oldValue('semestre', $estudiante['semestre'], $old); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="profile-section">
                        <h3>Domicilio</h3>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="pais">País</label>
                                <input type="text" id="pais" name="pais" maxlength="100"
                                    value="<?php echo oldValue('pais', $estudiante['pais'], $old); ?>">
                            </div>

                            <div class="form-group">
                                <label for="estado">Estado</label>
                                <input type="text" id="estado" name="estado" maxlength="100"
                                    value="<?php echo oldValue('estado', $estudiante['estado'], $old); ?>">
                            </div>

                            <div class="form-group">
                                <label for="ciudad">Ciudad</label>
                                <input type="text" id="ciudad" name="ciudad" maxlength="100"
                                    value="<?php echo oldValue('ciudad', $estudiante['ciudad'], $old); ?>">
                            </div>

                            <div class="form-group">
                                <label for="colonia">Colonia</label>
                                <input type="text" id="colonia" name="colonia" maxlength="100"
                                    value="<?php echo oldValue('colonia', $estudiante['colonia'], $old); ?>">
                            </div>

                            <div class="form-group">
                                <label for="codigo_postal">Código postal</label>
                                <input type="text" id="codigo_postal" name="codigo_postal" maxlength="10"
                                    value="<?php echo oldValue('codigo_postal', $estudiante['codigo_postal'], $old); ?>">
                            </div>

                            <div class="form-group">
                                <label for="calle">Calle</label>
                                <input type="text" id="calle" name="calle" maxlength="150"
                                    value="<?php echo oldValue('calle', $estudiante['calle'], $old); ?>">
                            </div>

                            <div class="form-group">
                                <label for="num_exterior">Número exterior</label>
                                <input type="text" id="num_exterior" name="num_exterior" maxlength="20"
                                    value="<?php echo oldValue('num_exterior', $estudiante['num_exterior'], $old); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="profile-actions">
                        <a href="dashboard_estudiante.php" class="btn btn-nav">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle"></i>
                            Guardar cambios
                        </button>
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
