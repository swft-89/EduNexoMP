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
        p.titulo_propuesta,
        p.descripcion_breve,
        p.enlace_portafolio,
        d.id_desafio,
        d.titulo,
        d.fecha_limite,
        d.modalidad,
        o.nombre_empresa,
        c.nombre_categoria,
        dp.nombre_archivo,
        dp.url_archivo,
        dp.tipo_archivo,
        dp.tamano_bytes
    FROM propuesta p
    INNER JOIN desafio d
        ON p.id_desafio = d.id_desafio
    INNER JOIN organizacion o
        ON d.id_organizacion = o.id_organizacion
    INNER JOIN categoria c
        ON d.id_categoria = c.id_categoria
    LEFT JOIN documento_propuesta dp
        ON p.id_propuesta = dp.id_propuesta
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
    <title>Mis propuestas - EduNexo MP</title>

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/estudiante/mis_propuestas.css">
    <link rel="stylesheet" href="../assets/css/dark.css">
    <link rel="stylesheet" href="../assets/css/estudiante/modal_propuesta.css">

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
            <a href="mis_propuestas.php" class="active">
                <i class="bi bi-file-earmark-text"></i> Mis propuestas
            </a>
            <a href="mis_favoritos.php">
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
                <h1>Mis propuestas</h1>
                <p>Consulta el estado y detalle de tus postulaciones enviadas</p>
            </div>

            <div class="user-box">
                <a href="dashboard_estudiante.php" class="btn btn-nav">Volver</a>
            </div>
        </div>

        <?php if (!empty($propuestas)): ?>
            <div class="propuestas-grid">
                <?php foreach ($propuestas as $propuesta): ?>
                    <?php
                    $estadoClase = strtolower(str_replace(' ', '-', $propuesta['estado'] ?? ''));
                    ?>
                    <article class="propuesta-card">
                        <div class="propuesta-header">
                            <div>
                                <h3><?php echo htmlspecialchars($propuesta['titulo']); ?></h3>
                                <p class="propuesta-empresa">
                                    <i class="bi bi-building"></i>
                                    <?php echo htmlspecialchars($propuesta['nombre_empresa']); ?>
                                </p>
                            </div>

                            <span class="estado-badge estado-<?php echo htmlspecialchars($estadoClase); ?>">
                                <?php echo htmlspecialchars($propuesta['estado'] ?? 'Sin estado'); ?>
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
                                <span>Fecha de respuesta</span>
                                <strong>
                                    <?php
                                    echo !empty($propuesta['fecha_respuesta'])
                                        ? htmlspecialchars(date('d/m/Y', strtotime($propuesta['fecha_respuesta'])))
                                        : 'Pendiente';
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
                            <button
                                type="button"
                                class="btn btn-primary btn-ver-propuesta"
                                data-modal="modalPropuesta_<?php echo (int) $propuesta['id_propuesta']; ?>"
                            >
                                Ver propuesta
                            </button>

                            <a href="detalle_desafio.php?id=<?php echo (int) $propuesta['id_desafio']; ?>" class="btn btn-outline-dark">
                                Ver desafío
                            </a>
                        </div>
                    </article>

                    <div class="propuesta-modal-overlay" id="modalPropuesta_<?php echo (int) $propuesta['id_propuesta']; ?>">
                        <div class="propuesta-modal">
                            <div class="propuesta-modal-head">
                                <div>
                                    <h2>Detalle de propuesta</h2>
                                    <p><?php echo htmlspecialchars($propuesta['titulo']); ?></p>
                                </div>

                                <button type="button" class="propuesta-modal-close">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>

                            <div class="propuesta-modal-body">
                                <div class="modal-section">
                                    <span>Título de propuesta</span>
                                    <strong>
                                        <?php echo htmlspecialchars($propuesta['titulo_propuesta'] ?? 'Sin título registrado'); ?>
                                    </strong>
                                </div>

                                <div class="modal-section">
                                    <span>Descripción breve</span>
                                    <p>
                                        <?php echo !empty($propuesta['descripcion_breve'])
                                            ? nl2br(htmlspecialchars($propuesta['descripcion_breve']))
                                            : 'No se agregó descripción breve.'; ?>
                                    </p>
                                </div>

                                <div class="modal-section">
                                    <span>Portafolio / GitHub / Drive</span>

                                    <?php if (!empty($propuesta['enlace_portafolio'])): ?>
                                        <a href="<?php echo htmlspecialchars($propuesta['enlace_portafolio']); ?>" target="_blank" class="modal-link">
                                            <?php echo htmlspecialchars($propuesta['enlace_portafolio']); ?>
                                        </a>
                                    <?php else: ?>
                                        <p>No se agregó enlace.</p>
                                    <?php endif; ?>
                                </div>

                                <div class="modal-section">
                                    <span>Archivo enviado</span>

                                    <?php if (!empty($propuesta['url_archivo'])): ?>
                                        <a href="../<?php echo htmlspecialchars($propuesta['url_archivo']); ?>" target="_blank" class="modal-file">
                                            <i class="bi bi-file-earmark-arrow-down"></i>
                                            <?php echo htmlspecialchars($propuesta['nombre_archivo']); ?>
                                        </a>
                                    <?php else: ?>
                                        <p>No hay archivo registrado.</p>
                                    <?php endif; ?>
                                </div>

                                <div class="modal-section">
                                    <span>Estado</span>
                                    <strong><?php echo htmlspecialchars($propuesta['estado'] ?? 'Sin estado'); ?></strong>
                                </div>

                                <div class="modal-section">
                                    <span>Feedback de la organización</span>
                                    <p>
                                        <?php echo !empty($propuesta['feedback'])
                                            ? nl2br(htmlspecialchars($propuesta['feedback']))
                                            : 'Aún no hay retroalimentación para esta propuesta.'; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="dashboard-card empty-state">
                <i class="bi bi-file-earmark-text"></i>
                <h3>Aún no has enviado propuestas</h3>
                <p>Explora los desafíos disponibles y envía una propuesta a los que mejor coincidan con tu perfil.</p>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-ver-propuesta').forEach(btn => {
        btn.addEventListener('click', function () {
            const modalId = this.dataset.modal;
            const modal = document.getElementById(modalId);

            if (modal) {
                modal.classList.add('active');
            }
        });
    });

    document.querySelectorAll('.propuesta-modal-close').forEach(btn => {
        btn.addEventListener('click', function () {
            this.closest('.propuesta-modal-overlay').classList.remove('active');
        });
    });

    document.querySelectorAll('.propuesta-modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function (e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
    });
});
</script>

</body>
</html>
