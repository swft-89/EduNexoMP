<?php
require_once '../../includes/session_organizacion.php';
require_once '../../config/conexion.php';

$idUsuario = $_SESSION['usuario_id'];
$idDesafio = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($idDesafio <= 0) {
    header('Location: ../dashboard_organizacion.php');
    exit;
}

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
        d.id_categoria,
        c.nombre_categoria,
        COUNT(p.id_propuesta) AS propuestas_recibidas
    FROM desafio d
    INNER JOIN categoria c
        ON d.id_categoria = c.id_categoria
    LEFT JOIN propuesta p
        ON d.id_desafio = p.id_desafio
    WHERE d.id_desafio = :id_desafio
      AND d.id_organizacion = :id_organizacion
    GROUP BY
        d.id_desafio,
        d.titulo,
        d.descripcion,
        d.fecha_publicacion,
        d.fecha_limite,
        d.estado,
        d.requisitos_especificos,
        d.modalidad,
        d.id_categoria,
        c.nombre_categoria
    LIMIT 1
");
$stmt->execute([
    ':id_desafio' => $idDesafio,
    ':id_organizacion' => $idUsuario
]);
$desafio = $stmt->fetch();

if (!$desafio) {
    $_SESSION['error'] = 'Desafío no encontrado.';
    header('Location: ../dashboard_organizacion.php');
    exit;
}

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

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle desafío - Organización</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/org/detalle_desafio.css">
    <link rel="stylesheet" href="../../assets/css/dark.css">
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
            <a href="../dashboard_organizacion.php">
                <i class="bi bi-house-door"></i> Inicio
            </a>
            <a href="crear_desafio.php" class="active">
                <i class="bi bi-plus-circle"></i> Crear desafío
            </a>
            <a href="../mis_desafios.php" class="active">
                <i class="bi bi-briefcase"></i> Mis desafíos
            </a>
            <a href="../propuestas_recibidas.php">
                <i class="bi bi-file-earmark-text"></i> Propuestas
            </a>
            <a href="../chat_organizacion.php">
                <i class="bi bi-chat"></i> Chat
            </a>
            <a href="../editar_perfil_organizacion.php">
                <i class="bi bi-building"></i> Perfil
            </a>
        </nav>
    </aside>

<section class="app-content org-content">
    <div class="org-detail-wrap">

        <div class="org-detail-header">
            <div>
                <h1><?php echo htmlspecialchars($desafio['titulo']); ?></h1>
                <p>
                    <?php echo htmlspecialchars($desafio['nombre_categoria']); ?>
                    ·
                    <?php echo htmlspecialchars($desafio['modalidad'] ?? 'No especificada'); ?>
                </p>
            </div>

            <div class="org-detail-actions">
                <a href="mis_desafios.php" class="btn btn-nav">Volver</a>
                <a href="editar_desafio.php?id=<?php echo (int) $desafio['id_desafio']; ?>" class="btn btn-primary">Editar</a>
                <!a href="../habilidades_desafio.php?id=><//?php echo (int) $desafio['id_desafio']; ?>" <!class="btn btn-primary"><!Gesionar habilidades><!/a>
                <a href="talentos_desafio.php?id=<?php echo (int) $desafio['id_desafio']; ?>" class="btn btn-primary">Ver talentos compatibles</a>
            </div>
        </div>

        <div class="org-detail-grid">
            <div class="org-detail-card">
                <div class="org-detail-meta">
                    <span class="org-detail-chip">
                        <i class="bi bi-calendar-plus"></i>
                        Publicado:
                        <?php echo htmlspecialchars(date('d/m/Y', strtotime($desafio['fecha_publicacion']))); ?>
                    </span>

                    <span class="org-detail-chip">
                        <i class="bi bi-hourglass-split"></i>
                        Fecha límite:
                        <?php echo !empty($desafio['fecha_limite']) ? htmlspecialchars(date('d/m/Y', strtotime($desafio['fecha_limite']))) : 'Sin definir'; ?>
                    </span>

                    <span class="org-detail-chip">
                        <i class="bi bi-journal-text"></i>
                        <?php echo (int) $desafio['propuestas_recibidas']; ?> propuestas
                    </span>
                </div>

                <div class="org-detail-section">
                    <h2>Descripción</h2>
                    <p><?php echo nl2br(htmlspecialchars($desafio['descripcion'])); ?></p>
                </div>

                <div class="org-detail-section">
                    <h2>Requisitos específicos</h2>
                    <p>
                        <?php echo !empty($desafio['requisitos_especificos'])
                            ? nl2br(htmlspecialchars($desafio['requisitos_especificos']))
                            : 'No especificados.'; ?>
                    </p>
                </div>

                <div class="org-detail-section">
                    <h2>Habilidades requeridas</h2>
                    <div class="org-detail-tags">
                        <?php if (!empty($habilidades)): ?>
                            <?php foreach ($habilidades as $habilidad): ?>
                                <span class="org-detail-tag">
                                    <?php echo htmlspecialchars($habilidad['nombre']); ?>
                                </span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="org-detail-tag">Sin habilidades registradas</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <aside class="org-detail-card org-side-card">
                <h3>Estado del desafío</h3>

                <div class="org-side-item">
                    <span>Estado actual</span>
                    <strong><?php echo htmlspecialchars($desafio['estado'] ?? 'No definido'); ?></strong>
                </div>

                <div class="org-side-item">
                    <span>Categoría</span>
                    <strong><?php echo htmlspecialchars($desafio['nombre_categoria']); ?></strong>
                </div>

                <div class="org-side-item">
                    <span>Modalidad</span>
                    <strong><?php echo htmlspecialchars($desafio['modalidad'] ?? 'No definida'); ?></strong>
                </div>

                <form action="../../procesos/cambiar_estado_desafio.php" method="POST" class="org-state-form">
                    <input type="hidden" name="id_desafio" value="<?php echo (int) $desafio['id_desafio']; ?>">

                    <label for="estado">Cambiar estado</label>
                    <select id="estado" name="estado">
                        <option value="activo" <?php echo (($desafio['estado'] ?? '') === 'activo') ? 'selected' : ''; ?>>Activo</option>
                        <option value="cerrado" <?php echo (($desafio['estado'] ?? '') === 'cerrado') ? 'selected' : ''; ?>>Cerrado</option>
                    </select>

                    <button type="submit" class="btn btn-primary">
                        Guardar estado
                    </button>
                </form>
            </aside>
        </div>

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
<script src="../../assets/js/main.js"></script>
</body>
</html>