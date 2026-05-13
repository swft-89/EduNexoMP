<?php
require_once '../../includes/session_organizacion.php';
require_once '../../config/conexion.php';

$idUsuario = $_SESSION['usuario_id'];

$stmt = $pdo->prepare("
    SELECT
        d.id_desafio,
        d.titulo,
        d.descripcion,
        d.fecha_publicacion,
        d.fecha_limite,
        d.estado,
        d.modalidad,
        c.nombre_categoria,
        COUNT(p.id_propuesta) AS propuestas_recibidas
    FROM desafio d
    INNER JOIN categoria c
        ON d.id_categoria = c.id_categoria
    LEFT JOIN propuesta p
        ON d.id_desafio = p.id_desafio
    WHERE d.id_organizacion = :id_organizacion
    GROUP BY
        d.id_desafio,
        d.titulo,
        d.descripcion,
        d.fecha_publicacion,
        d.fecha_limite,
        d.estado,
        d.modalidad,
        c.nombre_categoria
    ORDER BY d.fecha_publicacion DESC
");
$stmt->execute([':id_organizacion' => $idUsuario]);
$desafios = $stmt->fetchAll();

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis desafíos - EduNexo MP</title>

    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/dashboard_organizacion.css">
    <link rel="stylesheet" href="../../assets/css/org/mis_desafios.css">
    <link rel="stylesheet" href="../../assets/css/dark.css?v=dark-fix-2">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<div class="app-layout org-layout">
    <aside class="sidebar org-sidebar">
        <div class="sidebar-top">
            <div class="logo-mini"><i class="bi bi-mortarboard"></i></div>
            <span>EduNexo MP</span>
        </div>

        <nav class="sidebar-menu">
            <a href="dashboard_organizacion.php">
                <i class="bi bi-speedometer2"></i> Panel
            </a>
            <a href="mis_desafios.php" class="active">
                <i class="bi bi-folder2-open"></i> Mis desafíos
            </a>
            <a href="propuestas_organizacion.php">
                <i class="bi bi-file-earmark-text"></i> Propuestas
            </a>
            <a href="chat_organizacion.php">
                <i class="bi bi-chat"></i> Chat
            </a>
            <a href="#">
                <i class="bi bi-person"></i> Perfil
            </a>
        </nav>
    </aside>

    <section class="app-content org-content">
        <?php include __DIR__ . '/../../includes/app_topbar.php'; ?>

        <div class="org-page-wrap">
            <div class="org-page-head">
                <div>
                    <h1>Mis desafíos</h1>
                    <p>Administra los desafíos publicados por tu organización</p>
                </div>

                <a href="crear_desafio.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i>
                    Crear nuevo desafío
                </a>
            </div>

            <?php if (!empty($desafios)): ?>
                <div class="mis-desafios-grid">
                    <?php foreach ($desafios as $desafio): ?>
                        <?php
                        $estado = strtolower(trim($desafio['estado'] ?? ''));
                        $estadoClase = str_replace(' ', '-', $estado);
                        ?>
                        <article class="mis-desafio-card">
                            <div class="mis-desafio-head">
                                <div>
                                    <h3><?php echo htmlspecialchars($desafio['titulo']); ?></h3>
                                    <p><?php echo htmlspecialchars($desafio['nombre_categoria']); ?></p>
                                </div>

                                <span class="mis-desafio-status estado-<?php echo htmlspecialchars($estadoClase); ?>">
                                    <?php echo htmlspecialchars($desafio['estado'] ?? 'Sin estado'); ?>
                                </span>
                            </div>

                            <p class="mis-desafio-desc">
                                <?php echo htmlspecialchars(mb_strimwidth($desafio['descripcion'], 0, 130, '...')); ?>
                            </p>

                            <div class="mis-desafio-meta">
                                <div>
                                    <i class="bi bi-calendar-plus"></i>
                                    <span>
                                        Publicado:
                                        <?php echo htmlspecialchars(date('d/m/Y', strtotime($desafio['fecha_publicacion']))); ?>
                                    </span>
                                </div>

                                <div>
                                    <i class="bi bi-calendar-event"></i>
                                    <span>
                                        Cierre:
                                        <?php
                                        echo !empty($desafio['fecha_limite'])
                                            ? htmlspecialchars(date('d/m/Y', strtotime($desafio['fecha_limite'])))
                                            : 'Sin definir';
                                        ?>
                                    </span>
                                </div>

                                <div>
                                    <i class="bi bi-laptop"></i>
                                    <span>
                                        <?php echo htmlspecialchars($desafio['modalidad'] ?? 'No definida'); ?>
                                    </span>
                                </div>

                                <div>
                                    <i class="bi bi-people"></i>
                                    <span>
                                        <?php echo (int) $desafio['propuestas_recibidas']; ?> propuestas recibidas
                                    </span>
                                </div>
                            </div>

                            <div class="mis-desafio-actions">
                                <a href="detalle_desafio_organizacion.php?id=<?php echo (int) $desafio['id_desafio']; ?>" class="btn btn-nav">
                                    Ver detalle
                                </a>

                                <a href="editar_desafio.php?id=<?php echo (int) $desafio['id_desafio']; ?>" class="btn btn-primary">
                                    Editar
                                </a>

                                <a href="habilidades_desafio.php?id=<?php echo (int) $desafio['id_desafio']; ?>" class="btn btn-nav">
                                    Habilidades
                                </a>

                                <a href="talentos_desafio.php?id=<?php echo (int) $desafio['id_desafio']; ?>" class="btn btn-primary">
                                    Talentos
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="org-empty-card">
                    <i class="bi bi-folder2-open"></i>
                    <h3>Aún no tienes desafíos publicados</h3>
                    <p>Crea tu primer desafío para comenzar a recibir propuestas de estudiantes.</p>
                    <a href="crear_desafio.php" class="btn btn-primary">Crear desafío</a>
                </div>
            <?php endif; ?>
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