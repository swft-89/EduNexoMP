<?php
require_once '../includes/session_estudiante.php';
require_once '../config/conexion.php';
require_once '../includes/profile_photo.php';
require_once '../includes/student_schema.php';

$idUsuario = $_SESSION['usuario_id'] ?? null;

if (!$idUsuario) {
    header('Location: ../index.php');
    exit;
}

edunexo_ensure_student_interests_column($pdo);

$stmt = $pdo->prepare("
    SELECT
        e.id_estudiante,
        e.nombre,
        e.apellido_paterno,
        e.apellido_materno,
        e.carrera,
        e.no_control,
        e.semestre,
        e.intereses,
        e.curp,
        e.telefono,
        e.foto_url,
        u.correo_electronico,
        d.pais,
        d.estado,
        d.ciudad,
        d.colonia,
        d.codigo_postal,
        d.calle,
        d.num_exterior
    FROM estudiante e
    INNER JOIN usuario u ON u.id_usuario = e.id_estudiante
    LEFT JOIN direccion d ON d.id_direccion = e.id_direccion
    WHERE e.id_estudiante = :id_estudiante
    LIMIT 1
");
$stmt->execute([':id_estudiante' => $idUsuario]);
$estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$estudiante) {
    $_SESSION['error'] = 'No se encontro la informacion del estudiante.';
    header('Location: dashboard_estudiante.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT h.nombre, h.categoria_habilidad, eh.nivel
    FROM estudiante_habilidad eh
    INNER JOIN habilidad h ON h.id_habilidad = eh.id_habilidad
    WHERE eh.id_estudiante = :id_estudiante
    ORDER BY COALESCE(h.categoria_habilidad, 'General') ASC, h.nombre ASC
    LIMIT 8
");
$stmt->execute([':id_estudiante' => $idUsuario]);
$habilidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    SELECT
        COALESCE(p.titulo_propuesta, d.titulo) AS titulo_propuesta,
        p.estado,
        p.fecha_envio,
        d.titulo AS desafio,
        o.nombre_empresa
    FROM propuesta p
    INNER JOIN desafio d ON d.id_desafio = p.id_desafio
    INNER JOIN organizacion o ON o.id_organizacion = d.id_organizacion
    WHERE p.id_estudiante = :id
    ORDER BY p.fecha_envio DESC
    LIMIT 5
");
$stmt->execute([':id' => $idUsuario]);
$propuestasRecientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

function perfil_estudiante_iniciales(string $nombreCompleto): string
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

function perfil_estudiante_valor($valor, string $fallback = 'No registrado'): string
{
    $valor = trim((string) ($valor ?? ''));
    return $valor !== '' ? $valor : $fallback;
}

$nombreCompleto = trim(
    ($estudiante['nombre'] ?? '') . ' ' .
    ($estudiante['apellido_paterno'] ?? '') . ' ' .
    ($estudiante['apellido_materno'] ?? '')
);
$iniciales = perfil_estudiante_iniciales($nombreCompleto);
$ubicacion = trim(
    ($estudiante['ciudad'] ?? '') .
    (!empty($estudiante['estado']) ? ', ' . $estudiante['estado'] : '')
);
$direccion = trim(
    ($estudiante['calle'] ?? '') .
    (!empty($estudiante['num_exterior']) ? ' #' . $estudiante['num_exterior'] : '') .
    (!empty($estudiante['colonia']) ? ', ' . $estudiante['colonia'] : '') .
    (!empty($estudiante['codigo_postal']) ? ', CP ' . $estudiante['codigo_postal'] : '')
);

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi perfil | EduNexo MP</title>

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/estudiante/perfil_estudiante.css?v=profile-view-1">
    <link rel="stylesheet" href="../assets/css/dark.css?v=dark-fix-6">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/../includes/sidebar_estudiante.php'; ?>

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
                            <h1><?php echo htmlspecialchars($nombreCompleto ?: 'Mi perfil'); ?></h1>
                            <p>
                                <?php echo htmlspecialchars(perfil_estudiante_valor($estudiante['carrera'] ?? null, 'Carrera no registrada')); ?>
                                &middot; <?php echo htmlspecialchars(perfil_estudiante_valor($estudiante['semestre'] ?? null, 'Semestre no registrado')); ?>&deg; semestre
                                <?php if ($ubicacion !== ''): ?>
                                    &middot; <?php echo htmlspecialchars($ubicacion); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <div class="profile-hero-actions">
                        <a href="editar_perfil_estudiante.php" class="btn btn-primary">
                            <i class="bi bi-pencil-square"></i>
                            Editar perfil
                        </a>
                    </div>
                </div>
            </section>

            <div class="profile-overview-grid">
                <aside class="profile-account-card">
                    <div class="profile-account-avatar">
                        <?php if (!empty($estudiante['foto_url'])): ?>
                            <img src="<?php echo htmlspecialchars(edunexo_asset_url($estudiante['foto_url'])); ?>" alt="Foto de perfil">
                        <?php else: ?>
                            <?php echo htmlspecialchars($iniciales); ?>
                        <?php endif; ?>
                    </div>

                    <h2><?php echo htmlspecialchars($nombreCompleto ?: 'Estudiante'); ?></h2>
                    <p><?php echo htmlspecialchars(perfil_estudiante_valor($estudiante['correo_electronico'] ?? null)); ?></p>

                    <div class="profile-quick-list">
                        <div>
                            <i class="bi bi-mortarboard"></i>
                            <span><?php echo htmlspecialchars(perfil_estudiante_valor($estudiante['carrera'] ?? null, 'Carrera no registrada')); ?></span>
                        </div>
                        <div>
                            <i class="bi bi-hash"></i>
                            <span><?php echo htmlspecialchars(perfil_estudiante_valor($estudiante['no_control'] ?? null, 'No. de control no registrado')); ?></span>
                        </div>
                        <div>
                            <i class="bi bi-geo-alt"></i>
                            <span><?php echo htmlspecialchars(perfil_estudiante_valor($ubicacion, 'Ubicacion no registrada')); ?></span>
                        </div>
                    </div>

                    <a href="editar_perfil_estudiante.php" class="btn btn-primary profile-full-btn">
                        <i class="bi bi-pencil-square"></i>
                        Editar mis datos
                    </a>
                </aside>

                <section class="profile-activity-card">
                    <div class="profile-card-head profile-view-head">
                        <div>
                            <h2>Actividad reciente</h2>
                            <p>Ultimas propuestas enviadas y resumen de tu perfil.</p>
                        </div>
                        <a href="reportes_estudiante.php" class="btn btn-nav">
                            <i class="bi bi-bar-chart-line"></i>
                            Ver reportes
                        </a>
                    </div>

                    <div class="profile-mini-stats">
                        <article>
                            <span>Propuestas</span>
                            <strong><?php echo $totalPropuestas; ?></strong>
                        </article>
                        <article>
                            <span>Favoritos</span>
                            <strong><?php echo $totalFavoritos; ?></strong>
                        </article>
                        <article>
                            <span>Habilidades</span>
                            <strong><?php echo $totalHabilidades; ?></strong>
                        </article>
                    </div>

                    <?php if (!empty($propuestasRecientes)): ?>
                        <div class="profile-activity-table-wrap">
                            <table class="profile-activity-table">
                                <thead>
                                    <tr>
                                        <th>Propuesta</th>
                                        <th>Organizacion</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($propuestasRecientes as $propuesta): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($propuesta['titulo_propuesta'] ?? 'Sin titulo'); ?></strong>
                                                <span><?php echo htmlspecialchars($propuesta['desafio'] ?? 'Sin desafio'); ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($propuesta['nombre_empresa'] ?? 'Sin organizacion'); ?></td>
                                            <td><?php echo htmlspecialchars($propuesta['estado'] ?? 'Sin estado'); ?></td>
                                            <td><?php echo !empty($propuesta['fecha_envio']) ? htmlspecialchars(date('d/m/Y', strtotime($propuesta['fecha_envio']))) : 'Sin fecha'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="profile-empty-activity">
                            <i class="bi bi-send"></i>
                            <p>Aun no has enviado propuestas.</p>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <section class="profile-card">
                <div class="profile-card-head profile-view-head">
                    <div>
                        <h2>Informacion personal</h2>
                        <p>Datos visibles de tu cuenta y contacto principal.</p>
                    </div>
                </div>

                <div class="profile-info-grid">
                    <article class="profile-info-item">
                        <span>Correo electronico</span>
                        <strong><?php echo htmlspecialchars(perfil_estudiante_valor($estudiante['correo_electronico'] ?? null)); ?></strong>
                    </article>
                    <article class="profile-info-item">
                        <span>Telefono</span>
                        <strong><?php echo htmlspecialchars(perfil_estudiante_valor($estudiante['telefono'] ?? null)); ?></strong>
                    </article>
                    <article class="profile-info-item">
                        <span>CURP</span>
                        <strong><?php echo htmlspecialchars(perfil_estudiante_valor($estudiante['curp'] ?? null)); ?></strong>
                    </article>
                    <article class="profile-info-item">
                        <span>No. de control</span>
                        <strong><?php echo htmlspecialchars(perfil_estudiante_valor($estudiante['no_control'] ?? null)); ?></strong>
                    </article>
                </div>
            </section>

            <section class="profile-card">
                <div class="profile-card-head profile-view-head">
                    <div>
                        <h2>Perfil academico</h2>
                        <p>Informacion que ayuda a recomendarte desafios compatibles.</p>
                    </div>
                    <a href="habilidades_estudiante.php" class="btn btn-nav">
                        <i class="bi bi-stars"></i>
                        Ver habilidades
                    </a>
                </div>

                <div class="profile-info-grid">
                    <article class="profile-info-item wide">
                        <span>Intereses</span>
                        <strong><?php echo htmlspecialchars(perfil_estudiante_valor($estudiante['intereses'] ?? null, 'Sin intereses registrados')); ?></strong>
                    </article>
                    <article class="profile-info-item wide">
                        <span>Habilidades destacadas</span>
                        <?php if (!empty($habilidades)): ?>
                            <div class="profile-chip-list">
                                <?php foreach ($habilidades as $habilidad): ?>
                                    <span>
                                        <?php echo htmlspecialchars($habilidad['nombre']); ?>
                                        <?php if (!empty($habilidad['nivel'])): ?>
                                            &middot; <?php echo htmlspecialchars($habilidad['nivel']); ?>
                                        <?php endif; ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <strong>Sin habilidades registradas</strong>
                        <?php endif; ?>
                    </article>
                </div>
            </section>

            <section class="profile-card">
                <div class="profile-card-head profile-view-head">
                    <div>
                        <h2>Ubicacion</h2>
                        <p>Domicilio registrado en tu perfil.</p>
                    </div>
                </div>

                <div class="profile-info-grid">
                    <article class="profile-info-item">
                        <span>Pais</span>
                        <strong><?php echo htmlspecialchars(perfil_estudiante_valor($estudiante['pais'] ?? null)); ?></strong>
                    </article>
                    <article class="profile-info-item">
                        <span>Estado</span>
                        <strong><?php echo htmlspecialchars(perfil_estudiante_valor($estudiante['estado'] ?? null)); ?></strong>
                    </article>
                    <article class="profile-info-item">
                        <span>Ciudad</span>
                        <strong><?php echo htmlspecialchars(perfil_estudiante_valor($estudiante['ciudad'] ?? null)); ?></strong>
                    </article>
                    <article class="profile-info-item wide">
                        <span>Direccion</span>
                        <strong><?php echo htmlspecialchars(perfil_estudiante_valor($direccion, 'Direccion no registrada')); ?></strong>
                    </article>
                </div>
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
