<?php
require_once '../includes/session_estudiante.php';
require_once '../config/conexion.php';

$idUsuario = $_SESSION['usuario_id'];

$stmt = $pdo->prepare("
    SELECT
        f.fecha_marcado,
        d.id_desafio,
        d.titulo,
        d.descripcion,
        d.fecha_limite,
        d.modalidad,
        d.estado,
        o.nombre_empresa,
        c.nombre_categoria
    FROM favoritos f
    INNER JOIN desafio d
        ON f.id_desafio = d.id_desafio
    INNER JOIN organizacion o
        ON d.id_organizacion = o.id_organizacion
    INNER JOIN categoria c
        ON d.id_categoria = c.id_categoria
    WHERE f.id_estudiante = :id_estudiante
    ORDER BY f.fecha_marcado DESC
");
$stmt->execute([':id_estudiante' => $idUsuario]);
$favoritos = $stmt->fetchAll();

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis favoritos - EduNexo MP</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/estudiante/mis_propuestas.css">
    <link rel="stylesheet" href="../assets/css/estudiante/favoritos.css">
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
            <a href="mis_favoritos.php" class="active">
                <i class="bi bi-heart"></i> Favoritos
            </a>
            <a href="chat.php">
                <i class="bi bi-chat"></i> Chat
            </a>
            <a href="editar_perfil_estudiante.php">
                <i class="bi bi-person"></i> Perfil
            </a>
        </nav>
    </aside>

    <section class="app-content">
        <?php include __DIR__ . '/../includes/app_topbar.php'; ?>
        <div class="content-header">
            <div>
                <h1>Mis favoritos</h1>
                <p>Consulta los desafíos que has guardado</p>
            </div>

            <div class="user-box">
                <a href="dashboard_estudiante.php" class="btn btn-nav">Volver</a>
            </div>
        </div>

        <?php if (!empty($favoritos)): ?>
            <div class="favoritos-grid">
                <?php foreach ($favoritos as $favorito): ?>
                    <article class="favorito-card">
                        <div class="propuesta-header">
                            <div>
                                <h3><?php echo htmlspecialchars($favorito['titulo']); ?></h3>
                                <p class="propuesta-empresa">
                                    <i class="bi bi-building"></i>
                                    <?php echo htmlspecialchars($favorito['nombre_empresa']); ?>
                                </p>
                            </div>

                            <span class="estado-badge">
                                Favorito
                            </span>
                        </div>

                        <div class="propuesta-meta">
                            <div class="meta-line">
                                <span>Categoría</span>
                                <strong><?php echo htmlspecialchars($favorito['nombre_categoria']); ?></strong>
                            </div>

                            <div class="meta-line">
                                <span>Modalidad</span>
                                <strong><?php echo htmlspecialchars($favorito['modalidad'] ?? 'No definida'); ?></strong>
                            </div>

                            <div class="meta-line">
                                <span>Fecha guardado</span>
                                <strong><?php echo htmlspecialchars(date('d/m/Y', strtotime($favorito['fecha_marcado']))); ?></strong>
                            </div>

                            <div class="meta-line">
                                <span>Fecha límite</span>
                                <strong>
                                    <?php
                                    echo !empty($favorito['fecha_limite'])
                                        ? htmlspecialchars(date('d/m/Y', strtotime($favorito['fecha_limite'])))
                                        : 'Sin definir';
                                    ?>
                                </strong>
                            </div>
                        </div>

                        <div class="feedback-box">
                            <span>Descripción</span>
                            <p>
                                <?php echo !empty($favorito['descripcion'])
                                    ? htmlspecialchars($favorito['descripcion'])
                                    : 'Este desafío no tiene descripción disponible.'; ?>
                            </p>
                        </div>

                        <div class="propuesta-actions" style="display:flex; gap:10px; flex-wrap:wrap;">
                            <a href="detalle_desafio.php?id=<?php echo (int) $favorito['id_desafio']; ?>" class="btn btn-outline-dark">
                                Ver desafío
                            </a>

                            <form action="../procesos/estudiante/toggle_favorito.php" method="POST" style="margin:0;">
                                <input type="hidden" name="id_desafio" value="<?php echo (int) $favorito['id_desafio']; ?>">
                                <input type="hidden" name="redirect" value="../estudiante/mis_favoritos.php">
                                <button type="submit" class="btn btn-primary">
                                    Quitar de favoritos
                                </button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="dashboard-card empty-state">
                <i class="bi bi-heart"></i>
                <h3>Aún no has guardado favoritos</h3>
                <p>Explora los desafíos disponibles y guarda los que más te interesen.</p>
                <a href="dashboard_estudiante.php" class="btn btn-primary">Ver desafíos</a>
            </div>
        <?php endif; ?>
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
