<?php
require_once '../includes/session_estudiante.php';
require_once '../config/conexion.php';

$idUsuario = $_SESSION['usuario_id'];
$idDesafio = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$idUsuario) {
    header('Location: ../index.php');
    exit;
}

if ($idDesafio <= 0) {
    header('Location: dashboard_estudiante.php');
    exit;
}

/* Datos del desafio*/
$stmt = $pdo->prepare("
    SELECT
        d.id_desafio,
        d.titulo,
        d.descripcion,
        d.fecha_publicacion,
        d.fecha_limite,
        d.estado,
        d.requisitos_especificos,
        d.modalidad,
        o.nombre_empresa,
        o.representante,
        o.telefono_contacto,
        c.nombre_categoria
    FROM desafio d
    INNER JOIN organizacion o
        ON d.id_organizacion = o.id_organizacion
    INNER JOIN categoria c
        ON d.id_categoria = c.id_categoria
    WHERE d.id_desafio = :id_desafio
    LIMIT 1
");
$stmt->execute([':id_desafio' => $idDesafio]);
$desafio = $stmt->fetch();

if (!$desafio) {
    header('Location: dashboard_estudiante.php');
    exit;
}

/* Habilidades del desafio*/
$stmt = $pdo->prepare("
    SELECT
        h.nombre,
        dh.nivel_requerido,
        dh.obligatorio
    FROM desafio_habilidad dh
    INNER JOIN habilidad h
        ON dh.id_habilidad = h.id_habilidad
    WHERE dh.id_desafio = :id_desafio
    ORDER BY dh.obligatorio DESC, h.nombre ASC
");
$stmt->execute([':id_desafio' => $idDesafio]);
$habilidades = $stmt->fetchAll();

/* Verificar si ya esta postulado*/
$stmt = $pdo->prepare("
    SELECT id_propuesta
    FROM propuesta
    WHERE id_estudiante = :id_estudiante
      AND id_desafio = :id_desafio
    LIMIT 1
");
$stmt->execute([
    ':id_estudiante' => $idUsuario,
    ':id_desafio' => $idDesafio
]);
$yaPostulado = $stmt->fetch();

/* Match*/
$stmt = $pdo->prepare("
    SELECT
        COUNT(DISTINCT dh.id_habilidad) AS total_habilidades,
        COUNT(DISTINCT CASE
            WHEN eh.id_habilidad IS NOT NULL THEN dh.id_habilidad
        END) AS coincidentes
    FROM desafio_habilidad dh
    LEFT JOIN estudiante_habilidad eh
        ON eh.id_habilidad = dh.id_habilidad
       AND eh.id_estudiante = :id_estudiante
    WHERE dh.id_desafio = :id_desafio
");
$stmt->execute([
    ':id_estudiante' => $idUsuario,
    ':id_desafio' => $idDesafio
]);
$matchData = $stmt->fetch();

$totalHabilidades = (int) ($matchData['total_habilidades'] ?? 0);
$coincidentes = (int) ($matchData['coincidentes'] ?? 0);

$matchPorcentaje = $totalHabilidades > 0
    ? round(($coincidentes * 100) / $totalHabilidades)
    : 0;

/* Alertas de sesion*/
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del desafío - EduNexo MP</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/estudiante/detalle_desafio.css">
    <link rel="stylesheet" href="../assets/css/dark.css">
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
            <a href="dashboard_estudiante.php" class="active">
                <i class="bi bi-house-door"></i> Inicio
            </a>
            <a href="mis_propuestas.php"><i class="bi bi-file-earmark-text"></i> Mis propuestas</a>
            <a href="mis_favoritos.php"><i class="bi bi-heart"></i> Favoritos</a>
            <a href="chat.php"><i class="bi bi-chat"></i> Chat</a>
            <a href="editar_perfil_estudiante.php"><i class="bi bi-person"></i> Perfil</a>
        </nav>
    </aside>

    <section class="app-content">
        <?php include __DIR__ . '/../includes/app_topbar.php'; ?>
        <div class="content-header">
            <div>
                <h1><?php echo htmlspecialchars($desafio['titulo']); ?></h1>
                <p><?php echo htmlspecialchars($desafio['nombre_empresa']); ?> · <?php echo htmlspecialchars($desafio['nombre_categoria']); ?></p>
            </div>

            <div class="user-box">
                <a href="dashboard_estudiante.php" class="btn btn-nav">Volver</a>
            </div>
        </div>

        <div class="detalle-grid">
            <div class="detalle-main">
                <div class="dashboard-card">
                    <div class="detalle-top-meta">
                        <span class="detalle-chip">
                            <i class="bi bi-calendar-event"></i>
                            Fecha límite:
                            <?php echo !empty($desafio['fecha_limite']) ? htmlspecialchars(date('d/m/Y', strtotime($desafio['fecha_limite']))) : 'Sin definir'; ?>
                        </span>

                        <span class="detalle-chip">
                            <i class="bi bi-laptop"></i>
                            <?php echo htmlspecialchars($desafio['modalidad'] ?? 'No especificada'); ?>
                        </span>

                        <span class="detalle-chip">
                            <i class="bi bi-graph-up-arrow"></i>
                            Match <?php echo $matchPorcentaje; ?>%
                        </span>
                    </div>

                    <h2 class="detalle-section-title">Descripción</h2>
                    <p class="detalle-text">
                        <?php echo nl2br(htmlspecialchars($desafio['descripcion'])); ?>
                    </p>

                    <h2 class="detalle-section-title">Requisitos específicos</h2>
                    <p class="detalle-text">
                        <?php echo !empty($desafio['requisitos_especificos'])
                            ? nl2br(htmlspecialchars($desafio['requisitos_especificos']))
                            : 'No especificados.'; ?>
                    </p>

                    <h2 class="detalle-section-title">Habilidades requeridas</h2>
                    <div class="detalle-tags">
                        <?php if (!empty($habilidades)): ?>
                            <?php foreach ($habilidades as $habilidad): ?>
                                <span class="detalle-tag">
                                    <?php echo htmlspecialchars($habilidad['nombre']); ?>
                                    <?php if (!empty($habilidad['nivel_requerido'])): ?>
                                        · <?php echo htmlspecialchars($habilidad['nivel_requerido']); ?>
                                    <?php endif; ?>
                                    <?php if (!empty($habilidad['obligatorio'])): ?>
                                        · Obligatoria
                                    <?php endif; ?>
                                </span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="detalle-tag">Sin habilidades registradas</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <aside class="detalle-side">
                <div class="dashboard-card">
                    <h3 class="detalle-side-title">Información de la organización</h3>

                    <div class="detalle-side-item">
                        <span>Empresa</span>
                        <strong><?php echo htmlspecialchars($desafio['nombre_empresa']); ?></strong>
                    </div>

                    <div class="detalle-side-item">
                        <span>Representante</span>
                        <strong><?php echo htmlspecialchars($desafio['representante'] ?? 'No registrado'); ?></strong>
                    </div>

                    <div class="detalle-side-item">
                        <span>Teléfono</span>
                        <strong><?php echo htmlspecialchars($desafio['telefono_contacto'] ?? 'No registrado'); ?></strong>
                    </div>

                    <div class="detalle-side-item">
                        <span>Estado del desafío</span>
                        <strong><?php echo htmlspecialchars($desafio['estado'] ?? 'No definido'); ?></strong>
                    </div>

                    <div class="detalle-postular">
                        <?php if ($yaPostulado): ?>
                            <button class="btn btn-outline detalle-btn-disabled" type="button" disabled>
                                Ya postulaste
                            </button>
                        <?php else: ?>
                            <a href="crear_propuesta.php?id=<?php echo (int) $desafio['id_desafio']; ?>" class="btn btn-primary detalle-btn-full">
                                Crear propuesta
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </aside>
        </div>
    </section>
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
