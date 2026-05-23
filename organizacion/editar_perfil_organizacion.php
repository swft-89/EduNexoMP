<?php
require_once '../includes/session_organizacion.php';
require_once '../config/conexion.php';
require_once '../includes/profile_photo.php';

$idUsuario = $_SESSION['usuario_id'] ?? null;

if (!$idUsuario) {
    header('Location: ../index.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        o.id_organizacion,
        o.nombre_empresa,
        o.rfc,
        o.sector,
        o.representante,
        o.telefono_contacto,
        o.foto_url,
        o.id_direccion,
        u.correo_electronico,
        d.pais,
        d.estado,
        d.ciudad,
        d.colonia,
        d.codigo_postal,
        d.calle,
        d.num_exterior
    FROM organizacion o
    INNER JOIN usuario u
        ON u.id_usuario = o.id_organizacion
    LEFT JOIN direccion d
        ON d.id_direccion = o.id_direccion
    WHERE o.id_organizacion = :id_organizacion
    LIMIT 1
");
$stmt->execute([':id_organizacion' => $idUsuario]);
$organizacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$organizacion) {
    $_SESSION['error'] = 'No se encontró la información de la organización.';
    header('Location: dashboard_organizacion.php');
    exit;
}

function obtenerInicialesOrganizacion($nombre)
{
    $nombre = trim((string)$nombre);

    if ($nombre === '') {
        return 'OR';
    }

    $partes = preg_split('/\s+/', $nombre);
    $iniciales = '';

    foreach ($partes as $parte) {
        if ($parte !== '') {
            $iniciales .= mb_strtoupper(mb_substr($parte, 0, 1));
        }
        if (mb_strlen($iniciales) >= 2) {
            break;
        }
    }

    return $iniciales ?: 'OR';
}

function oldOrgValue($key, $default, $old)
{
    return htmlspecialchars($old[$key] ?? ($default ?? ''));
}

$iniciales = obtenerInicialesOrganizacion($organizacion['nombre_empresa'] ?? '');
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
$old = $_SESSION['old'] ?? [];

$camposPerfil = [
    'Nombre de la empresa' => $organizacion['nombre_empresa'] ?? '',
    'Correo electrónico' => $organizacion['correo_electronico'] ?? '',
    'RFC' => $organizacion['rfc'] ?? '',
    'Sector' => $organizacion['sector'] ?? '',
    'Representante' => $organizacion['representante'] ?? '',
    'Teléfono de contacto' => $organizacion['telefono_contacto'] ?? '',
    'Logo o imagen' => $organizacion['foto_url'] ?? ''
];

$totalCamposPerfil = count($camposPerfil);
$camposCompletosPerfil = 0;
$camposPendientesPerfil = [];

foreach ($camposPerfil as $label => $valor) {
    if (trim((string)$valor) !== '') {
        $camposCompletosPerfil++;
    } else {
        $camposPendientesPerfil[] = $label;
    }
}

$porcentajePerfil = $totalCamposPerfil > 0
    ? (int) round(($camposCompletosPerfil / $totalCamposPerfil) * 100)
    : 0;

$pendientesPreview = array_slice($camposPendientesPerfil, 0, 4);
$pendientesRestantes = max(0, count($camposPendientesPerfil) - count($pendientesPreview));

unset($_SESSION['success'], $_SESSION['error'], $_SESSION['old']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar perfil de organización | EduNexo MP</title>

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/org/perfil_organizacion.css?v=org-profile-dark-1">
    <link rel="stylesheet" href="../assets/css/dark.css?v=dark-fix-4">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
<div class="app-layout">

    <?php include __DIR__ . '/../includes/sidebar_organizacion.php'; ?>

    <main class="app-content">
        <?php include __DIR__ . '/../includes/app_topbar.php'; ?>
        <div class="org-profile-page">

            <section class="org-profile-hero">
                <div class="org-profile-hero-top">
                    <div class="org-profile-hero-user">
                        <div class="org-profile-avatar">
                            <?php if (!empty($organizacion['foto_url'])): ?>
                                <img src="<?php echo htmlspecialchars(edunexo_asset_url($organizacion['foto_url'])); ?>" alt="Logo de la organización">
                            <?php else: ?>
                                <?php echo htmlspecialchars($iniciales); ?>
                            <?php endif; ?>
                        </div>

                        <div>
                            <h1>Editar perfil</h1>
                            <p>
                                <?php echo htmlspecialchars($organizacion['nombre_empresa']); ?>
                                <?php if (!empty($organizacion['sector'])): ?>
                                    · <?php echo htmlspecialchars($organizacion['sector']); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <div class="org-profile-hero-actions">
                        <a href="perfil_organizacion.php" class="btn btn-nav">
                            <i class="bi bi-arrow-left"></i>
                            Volver
                        </a>
                    </div>
                </div>

                <div class="org-profile-hero-body">
                   <div class="org-profile-progress">
                        <div class="org-profile-progress-left">
                            <div class="org-profile-progress-top">
                                <span>Perfil completado</span>
                                <strong><?php echo $porcentajePerfil; ?>%</strong>
                            </div>

                            <div class="org-profile-progress-bar">
                                <div class="org-profile-progress-fill" style="width: <?php echo $porcentajePerfil; ?>%;"></div>
                            </div>
                        </div>

                        <div class="org-profile-progress-right">
                            <p>
                                Completa la información de tu organización para generar más confianza dentro de la plataforma
                                y mejorar la presentación de tus desafíos.
                            </p>

                            <?php if (!empty($camposPendientesPerfil)): ?>
                                <div class="org-profile-missing">
                                    <strong>Te falta completar:</strong>
                                    <span>
                                        <?php echo htmlspecialchars(implode(', ', $pendientesPreview)); ?>
                                        <?php if ($pendientesRestantes > 0): ?>
                                            <?php echo htmlspecialchars(' y ' . $pendientesRestantes . ' más'); ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="org-profile-preview">
                        <div class="org-preview-card">
                            <div class="org-preview-label">Resumen rápido</div>

                            <div class="org-preview-header">
                                <div class="org-preview-avatar">
                                    <?php if (!empty($organizacion['foto_url'])): ?>
                                        <img src="<?php echo htmlspecialchars(edunexo_asset_url($organizacion['foto_url'])); ?>" alt="">
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($iniciales); ?>
                                    <?php endif; ?>
                                </div>

                                <div>
                                    <h4><?php echo htmlspecialchars($organizacion['nombre_empresa']); ?></h4>
                                    <span><?php echo htmlspecialchars($organizacion['sector'] ?: 'Sector no definido'); ?></span>
                                </div>
                            </div>

                            <div class="org-preview-body">
                                <div class="org-preview-item">
                                    <i class="bi bi-person"></i>
                                    <span><?php echo htmlspecialchars($organizacion['representante'] ?: 'Representante no definido'); ?></span>
                                </div>

                                <div class="org-preview-item">
                                    <i class="bi bi-telephone"></i>
                                    <span><?php echo htmlspecialchars($organizacion['telefono_contacto'] ?: 'Teléfono no definido'); ?></span>
                                </div>

                                <div class="org-preview-item">
                                    <i class="bi bi-geo-alt"></i>
                                    <span>
                                        <?php
                                        $ubicacionPreview = trim(
                                            ($organizacion['ciudad'] ?? '') .
                                            (!empty($organizacion['estado']) ? ', ' . $organizacion['estado'] : '')
                                        );
                                        echo htmlspecialchars($ubicacionPreview !== '' ? $ubicacionPreview : 'Ubicación no definida');
                                        ?>
                                    </span>
                                </div>

                                <div class="org-preview-item">
                                    <i class="bi bi-envelope"></i>
                                    <span><?php echo htmlspecialchars($organizacion['correo_electronico']); ?></span>
                                </div>
                            </div>

                            <div class="org-preview-footer">
                                <button type="button" class="btn-soft" disabled>
                                    Vista informativa
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="org-profile-card">
                <div class="org-profile-card-head">
                    <div>
                        <h2>Información de la organización</h2>
                        <p>Actualiza los datos institucionales y de contacto que se mostrarán dentro de la plataforma.</p>
                    </div>
                </div>

                <form action="../procesos/guardar_perfil_organizacion.php" method="POST" enctype="multipart/form-data" class="org-profile-form" novalidate>
                    <?php echo edunexo_csrf_input(); ?>
                    <input type="hidden" name="foto_url_actual" value="<?php echo htmlspecialchars($organizacion['foto_url'] ?? ''); ?>">

                    <div class="org-profile-section">
                        <h3>Datos generales</h3>

                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="nombre_empresa">Nombre de la empresa <span class="required-mark" aria-hidden="true">*</span></label>
                                <input
                                    type="text"
                                    id="nombre_empresa"
                                    name="nombre_empresa"
                                    maxlength="150"
                                    required
                                    value="<?php echo oldOrgValue('nombre_empresa', $organizacion['nombre_empresa'], $old); ?>">
                            </div>

                            <div class="form-group">
                                <label for="rfc">RFC</label>
                                <input
                                    type="text"
                                    id="rfc"
                                    name="rfc"
                                    maxlength="20"
                                    value="<?php echo oldOrgValue('rfc', $organizacion['rfc'], $old); ?>">
                            </div>

                            <div class="form-group">
                                <label for="sector">Sector</label>
                                <input
                                    type="text"
                                    id="sector"
                                    name="sector"
                                    maxlength="100"
                                    value="<?php echo oldOrgValue('sector', $organizacion['sector'], $old); ?>">
                            </div>

                            <div class="form-group">
                                <label for="representante">Representante</label>
                                <input
                                    type="text"
                                    id="representante"
                                    name="representante"
                                    maxlength="150"
                                    value="<?php echo oldOrgValue('representante', $organizacion['representante'], $old); ?>">
                            </div>

                            <div class="form-group">
                                <label for="telefono_contacto">Teléfono de contacto</label>
                                <input
                                    type="text"
                                    id="telefono_contacto"
                                    name="telefono_contacto"
                                    maxlength="20"
                                    value="<?php echo oldOrgValue('telefono_contacto', $organizacion['telefono_contacto'], $old); ?>">
                            </div>

                            <div class="form-group">
                                <label for="correo_electronico">Correo electrónico <span class="required-mark" aria-hidden="true">*</span></label>
                                <input
                                    type="email"
                                    id="correo_electronico"
                                    name="correo_electronico"
                                    maxlength="120"
                                    required
                                    value="<?php echo oldOrgValue('correo_electronico', $organizacion['correo_electronico'], $old); ?>">
                            </div>

                            <div class="form-group full">
                                <label for="foto_perfil">Logo o imagen</label>
                                <input
                                    type="file"
                                    id="foto_perfil"
                                    name="foto_perfil"
                                    accept="image/jpeg,image/png,image/webp">
                                <small>JPG, PNG o WEBP. Máximo 2 MB.</small>
                            </div>

                        </div>
                    </div>

                    <div class="org-profile-section">
                        <h3>Ubicación <span class="optional-note">(opcional)</span></h3>

                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="pais">País</label>
                                <input
                                    type="text"
                                    id="pais"
                                    name="pais"
                                    maxlength="100"
                                    value="<?php echo oldOrgValue('pais', $organizacion['pais'], $old); ?>">
                            </div>

                            <div class="form-group">
                                <label for="estado">Estado</label>
                                <input
                                    type="text"
                                    id="estado"
                                    name="estado"
                                    maxlength="100"
                                    value="<?php echo oldOrgValue('estado', $organizacion['estado'], $old); ?>">
                            </div>

                            <div class="form-group">
                                <label for="ciudad">Ciudad</label>
                                <input
                                    type="text"
                                    id="ciudad"
                                    name="ciudad"
                                    maxlength="100"
                                    value="<?php echo oldOrgValue('ciudad', $organizacion['ciudad'], $old); ?>">
                            </div>

                            <div class="form-group">
                                <label for="colonia">Colonia</label>
                                <input
                                    type="text"
                                    id="colonia"
                                    name="colonia"
                                    maxlength="100"
                                    value="<?php echo oldOrgValue('colonia', $organizacion['colonia'], $old); ?>">
                            </div>

                            <div class="form-group">
                                <label for="codigo_postal">Código postal</label>
                                <input
                                    type="text"
                                    id="codigo_postal"
                                    name="codigo_postal"
                                    maxlength="10"
                                    value="<?php echo oldOrgValue('codigo_postal', $organizacion['codigo_postal'], $old); ?>">
                            </div>

                            <div class="form-group">
                                <label for="calle">Calle</label>
                                <input
                                    type="text"
                                    id="calle"
                                    name="calle"
                                    maxlength="150"
                                    value="<?php echo oldOrgValue('calle', $organizacion['calle'], $old); ?>">
                            </div>

                            <div class="form-group">
                                <label for="num_exterior">Número exterior</label>
                                <input
                                    type="text"
                                    id="num_exterior"
                                    name="num_exterior"
                                    maxlength="20"
                                    value="<?php echo oldOrgValue('num_exterior', $organizacion['num_exterior'], $old); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="org-profile-actions">
                        <a href="perfil_organizacion.php" class="btn btn-nav">Cancelar</a>
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
