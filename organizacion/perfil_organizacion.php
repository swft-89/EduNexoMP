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
        u.correo_electronico,
        d.pais,
        d.estado,
        d.ciudad,
        d.colonia,
        d.codigo_postal,
        d.calle,
        d.num_exterior
    FROM organizacion o
    INNER JOIN usuario u ON u.id_usuario = o.id_organizacion
    LEFT JOIN direccion d ON d.id_direccion = o.id_direccion
    WHERE o.id_organizacion = :id_organizacion
    LIMIT 1
");
$stmt->execute([':id_organizacion' => $idUsuario]);
$organizacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$organizacion) {
    $_SESSION['error'] = 'No se encontro la informacion de la organizacion.';
    header('Location: dashboard_organizacion.php');
    exit;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM desafio WHERE id_organizacion = :id");
$stmt->execute([':id' => $idUsuario]);
$totalDesafios = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM propuesta p
    INNER JOIN desafio d ON d.id_desafio = p.id_desafio
    WHERE d.id_organizacion = :id
");
$stmt->execute([':id' => $idUsuario]);
$totalPropuestas = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT c.id_conversacion)
    FROM conversacion c
    INNER JOIN propuesta p ON p.id_propuesta = c.id_propuesta
    INNER JOIN desafio d ON d.id_desafio = p.id_desafio
    WHERE d.id_organizacion = :id
");
$stmt->execute([':id' => $idUsuario]);
$totalConversaciones = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT
        d.titulo,
        d.estado,
        d.fecha_publicacion,
        d.fecha_limite,
        COUNT(p.id_propuesta) AS propuestas
    FROM desafio d
    LEFT JOIN propuesta p ON p.id_desafio = d.id_desafio
    WHERE d.id_organizacion = :id
    GROUP BY d.id_desafio, d.titulo, d.estado, d.fecha_publicacion, d.fecha_limite
    ORDER BY d.fecha_publicacion DESC
    LIMIT 5
");
$stmt->execute([':id' => $idUsuario]);
$desafiosRecientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

function perfil_org_iniciales(string $nombre): string
{
    $partes = preg_split('/\s+/', trim($nombre));
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

function perfil_org_valor($valor, string $fallback = 'No registrado'): string
{
    $valor = trim((string) ($valor ?? ''));
    return $valor !== '' ? $valor : $fallback;
}

$iniciales = perfil_org_iniciales($organizacion['nombre_empresa'] ?? '');
$ubicacion = trim(
    ($organizacion['ciudad'] ?? '') .
    (!empty($organizacion['estado']) ? ', ' . $organizacion['estado'] : '')
);
$direccion = trim(
    ($organizacion['calle'] ?? '') .
    (!empty($organizacion['num_exterior']) ? ' #' . $organizacion['num_exterior'] : '') .
    (!empty($organizacion['colonia']) ? ', ' . $organizacion['colonia'] : '') .
    (!empty($organizacion['codigo_postal']) ? ', CP ' . $organizacion['codigo_postal'] : '')
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
    <title>Perfil de organizacion | EduNexo MP</title>

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/org/perfil_organizacion.css?v=profile-view-1">
    <link rel="stylesheet" href="../assets/css/dark.css?v=dark-fix-6">
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
                                <img src="<?php echo htmlspecialchars(edunexo_asset_url($organizacion['foto_url'])); ?>" alt="Logo de la organizacion">
                            <?php else: ?>
                                <?php echo htmlspecialchars($iniciales); ?>
                            <?php endif; ?>
                        </div>

                        <div>
                            <h1><?php echo htmlspecialchars($organizacion['nombre_empresa'] ?: 'Perfil de organizacion'); ?></h1>
                            <p>
                                <?php echo htmlspecialchars(perfil_org_valor($organizacion['sector'] ?? null, 'Sector no registrado')); ?>
                                <?php if ($ubicacion !== ''): ?>
                                    &middot; <?php echo htmlspecialchars($ubicacion); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <div class="org-profile-hero-actions">
                        <a href="editar_perfil_organizacion.php" class="btn btn-primary">
                            <i class="bi bi-pencil-square"></i>
                            Editar perfil
                        </a>
                    </div>
                </div>
            </section>

            <div class="org-profile-overview-grid">
                <aside class="org-profile-account-card">
                    <div class="org-profile-account-avatar">
                        <?php if (!empty($organizacion['foto_url'])): ?>
                            <img src="<?php echo htmlspecialchars(edunexo_asset_url($organizacion['foto_url'])); ?>" alt="Logo de la organizacion">
                        <?php else: ?>
                            <?php echo htmlspecialchars($iniciales); ?>
                        <?php endif; ?>
                    </div>

                    <h2><?php echo htmlspecialchars($organizacion['nombre_empresa'] ?: 'Organizacion'); ?></h2>
                    <p><?php echo htmlspecialchars(perfil_org_valor($organizacion['correo_electronico'] ?? null)); ?></p>

                    <div class="org-profile-quick-list">
                        <div>
                            <i class="bi bi-briefcase"></i>
                            <span><?php echo htmlspecialchars(perfil_org_valor($organizacion['sector'] ?? null, 'Sector no registrado')); ?></span>
                        </div>
                        <div>
                            <i class="bi bi-person"></i>
                            <span><?php echo htmlspecialchars(perfil_org_valor($organizacion['representante'] ?? null, 'Representante no registrado')); ?></span>
                        </div>
                        <div>
                            <i class="bi bi-geo-alt"></i>
                            <span><?php echo htmlspecialchars(perfil_org_valor($ubicacion, 'Ubicacion no registrada')); ?></span>
                        </div>
                    </div>

                    <a href="editar_perfil_organizacion.php" class="btn btn-primary org-profile-full-btn">
                        <i class="bi bi-pencil-square"></i>
                        Editar mis datos
                    </a>
                </aside>

                <section class="org-profile-activity-card">
                    <div class="org-profile-card-head org-profile-view-head">
                        <div>
                            <h2>Actividad reciente</h2>
                            <p>Ultimos desafios publicados y movimiento de tu organizacion.</p>
                        </div>
                        <a href="reportes_organizacion.php" class="btn btn-nav">
                            <i class="bi bi-bar-chart-line"></i>
                            Ver reportes
                        </a>
                    </div>

                    <div class="org-profile-mini-stats">
                        <article>
                            <span>Desafios</span>
                            <strong><?php echo $totalDesafios; ?></strong>
                        </article>
                        <article>
                            <span>Propuestas</span>
                            <strong><?php echo $totalPropuestas; ?></strong>
                        </article>
                        <article>
                            <span>Chats</span>
                            <strong><?php echo $totalConversaciones; ?></strong>
                        </article>
                    </div>

                    <?php if (!empty($desafiosRecientes)): ?>
                        <div class="org-profile-activity-table-wrap">
                            <table class="org-profile-activity-table">
                                <thead>
                                    <tr>
                                        <th>Desafio</th>
                                        <th>Estado</th>
                                        <th>Propuestas</th>
                                        <th>Limite</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($desafiosRecientes as $desafio): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($desafio['titulo'] ?? 'Sin titulo'); ?></strong>
                                                <span><?php echo !empty($desafio['fecha_publicacion']) ? htmlspecialchars(date('d/m/Y', strtotime($desafio['fecha_publicacion']))) : 'Sin fecha'; ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($desafio['estado'] ?? 'Sin estado'); ?></td>
                                            <td><?php echo (int) ($desafio['propuestas'] ?? 0); ?></td>
                                            <td><?php echo !empty($desafio['fecha_limite']) ? htmlspecialchars(date('d/m/Y', strtotime($desafio['fecha_limite']))) : 'Sin limite'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="org-profile-empty-activity">
                            <i class="bi bi-briefcase"></i>
                            <p>Aun no has publicado desafios.</p>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <section class="org-profile-card">
                <div class="org-profile-card-head org-profile-view-head">
                    <div>
                        <h2>Informacion institucional</h2>
                        <p>Datos principales visibles para estudiantes y administradores.</p>
                    </div>
                </div>

                <div class="org-profile-info-grid">
                    <article class="org-profile-info-item">
                        <span>Correo electronico</span>
                        <strong><?php echo htmlspecialchars(perfil_org_valor($organizacion['correo_electronico'] ?? null)); ?></strong>
                    </article>
                    <article class="org-profile-info-item">
                        <span>Representante</span>
                        <strong><?php echo htmlspecialchars(perfil_org_valor($organizacion['representante'] ?? null)); ?></strong>
                    </article>
                    <article class="org-profile-info-item">
                        <span>Telefono</span>
                        <strong><?php echo htmlspecialchars(perfil_org_valor($organizacion['telefono_contacto'] ?? null)); ?></strong>
                    </article>
                    <article class="org-profile-info-item">
                        <span>RFC</span>
                        <strong><?php echo htmlspecialchars(perfil_org_valor($organizacion['rfc'] ?? null)); ?></strong>
                    </article>
                </div>
            </section>

            <section class="org-profile-card">
                <div class="org-profile-card-head org-profile-view-head">
                    <div>
                        <h2>Actividad en la plataforma</h2>
                        <p>Resumen rapido de tu participacion dentro de EduNexo MP.</p>
                    </div>
                    <a href="reportes_organizacion.php" class="btn btn-nav">
                        <i class="bi bi-bar-chart-line"></i>
                        Ver reportes
                    </a>
                </div>

                <div class="org-profile-info-grid">
                    <article class="org-profile-info-item">
                        <span>Desafios publicados</span>
                        <strong><?php echo $totalDesafios; ?></strong>
                    </article>
                    <article class="org-profile-info-item">
                        <span>Propuestas recibidas</span>
                        <strong><?php echo $totalPropuestas; ?></strong>
                    </article>
                    <article class="org-profile-info-item wide">
                        <span>Sector</span>
                        <strong><?php echo htmlspecialchars(perfil_org_valor($organizacion['sector'] ?? null)); ?></strong>
                    </article>
                </div>
            </section>

            <section class="org-profile-card">
                <div class="org-profile-card-head org-profile-view-head">
                    <div>
                        <h2>Ubicacion</h2>
                        <p>Domicilio registrado para tu organizacion.</p>
                    </div>
                </div>

                <div class="org-profile-info-grid">
                    <article class="org-profile-info-item">
                        <span>Pais</span>
                        <strong><?php echo htmlspecialchars(perfil_org_valor($organizacion['pais'] ?? null)); ?></strong>
                    </article>
                    <article class="org-profile-info-item">
                        <span>Estado</span>
                        <strong><?php echo htmlspecialchars(perfil_org_valor($organizacion['estado'] ?? null)); ?></strong>
                    </article>
                    <article class="org-profile-info-item">
                        <span>Ciudad</span>
                        <strong><?php echo htmlspecialchars(perfil_org_valor($organizacion['ciudad'] ?? null)); ?></strong>
                    </article>
                    <article class="org-profile-info-item wide">
                        <span>Direccion</span>
                        <strong><?php echo htmlspecialchars(perfil_org_valor($direccion, 'Direccion no registrada')); ?></strong>
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
