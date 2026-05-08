<?php
require_once '../includes/session_estudiante.php';
require_once '../config/conexion.php';

$idUsuario = $_SESSION['usuario_id'];

$stmt = $pdo->prepare("
    SELECT
        p.id_propuesta,
        p.fecha_envio,
        p.estado,
        p.feedback,
        p.fecha_respuesta,
        d.id_desafio,
        d.titulo,
        d.fecha_limite,
        d.modalidad,
        o.nombre_empresa,
        c.nombre_categoria
    FROM propuesta p
    INNER JOIN desafio d
        ON p.id_desafio = d.id_desafio
    INNER JOIN organizacion o
        ON d.id_organizacion = o.id_organizacion
    INNER JOIN categoria c
        ON d.id_categoria = c.id_categoria
    WHERE p.id_estudiante = :id_estudiante
    ORDER BY p.fecha_envio DESC
");
$stmt->execute([':id_estudiante' => $idUsuario]);
$propuestas = $stmt->fetchAll();

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis propuestas - EduNexo MP</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/estudiante/mis_propuestas.css">
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
            <a href="dashboard_estudiante.php">
                <i class="bi bi-house-door"></i> Inicio
            </a>
            <a href="mis_propuestas.php" class="active">
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
            <a href="editar_perfil_estudiante.php">
                <i class="bi bi-person"></i> Perfil
            </a>
        </nav>
    </aside>

    <section class="app-content">
        <div class="content-header">
            <div>
                <h1>Mis propuestas</h1>
                <p>Consulta el estado de tus postulaciones enviadas</p>
            </div>

            <div class="user-box">
                <button class="btn btn-theme" id="toggleTheme" type="button">🌙</button>
                <a href="dashboard_estudiante.php" class="btn btn-nav">Volver</a>
            </div>
        </div>

        <?php if (!empty($propuestas)): ?>
            <div class="propuestas-grid">
                <?php foreach ($propuestas as $propuesta): ?>
                    <article class="propuesta-card">
                        <div class="propuesta-header">
                            <div>
                                <h3><?php echo htmlspecialchars($propuesta['titulo']); ?></h3>
                                <p class="propuesta-empresa">
                                    <i class="bi bi-building"></i>
                                    <?php echo htmlspecialchars($propuesta['nombre_empresa']); ?>
                                </p>
                            </div>

                            <?php $estadoClase = strtolower(str_replace(' ', '-', $propuesta['estado'])); ?>

                            <span class="estado-badge estado-<?php echo htmlspecialchars($estadoClase); ?>">
                                <?php echo htmlspecialchars($propuesta['estado']); ?>
                            </span>
                        </div>

                        <div class="propuesta-meta">
                            <div class="meta-line">
                                <span>Categoría</span>
                                <strong><?php echo htmlspecialchars($propuesta['nombre_categoria']); ?></strong>
                            </div>

                            <div class="meta-line">
                                <span>Modalidad</span>
                                <strong><?php echo htmlspecialchars($propuesta['modalidad'] ?? 'No definida'); ?></strong>
                            </div>

                            <div class="meta-line">
                                <span>Fecha de envío</span>
                                <strong><?php echo htmlspecialchars(date('d/m/Y', strtotime($propuesta['fecha_envio']))); ?></strong>
                            </div>

                            <div class="meta-line">
                                <span>Fecha límite</span>
                                <strong>
                                    <?php
                                    echo !empty($propuesta['fecha_limite'])
                                        ? htmlspecialchars(date('d/m/Y', strtotime($propuesta['fecha_limite'])))
                                        : 'Sin definir';
                                    ?>
                                </strong>
                            </div>
                        </div>

                        <div class="feedback-box">
                            <span>Feedback</span>
                            <p>
                                <?php echo !empty($propuesta['feedback'])
                                    ? htmlspecialchars($propuesta['feedback'])
                                    : 'Aún no hay retroalimentación para esta propuesta.'; ?>
                            </p>
                        </div>

                        <div class="propuesta-actions">
                            <a href="detalle_desafio.php?id=<?php echo (int) $propuesta['id_desafio']; ?>" class="btn btn-outline-dark">
                                Ver desafío
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="dashboard-card empty-state">
                <i class="bi bi-file-earmark-text"></i>
                <h3>Aún no has enviado propuestas</h3>
                <p>Explora los desafíos disponibles y postúlate a los que mejor coincidan con tu perfil.</p>
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