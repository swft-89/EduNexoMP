<?php
require_once '../includes/session_organizacion.php';
require_once '../config/conexion.php';

$idUsuario = $_SESSION['usuario_id'];

$stmt = $pdo->prepare("
    SELECT
        p.id_propuesta,
        p.fecha_envio,
        p.estado,
        p.feedback,
        p.fecha_respuesta,
        p.id_estudiante,
        p.titulo_propuesta,
        p.descripcion_breve,
        p.enlace_portafolio,
        d.id_desafio,
        d.titulo,
        d.modalidad,
        o.nombre_empresa,
        e.nombre,
        e.apellido_paterno,
        e.apellido_materno,
        e.carrera,
        e.semestre,
        c.id_conversacion
    FROM propuesta p
    INNER JOIN desafio d
        ON p.id_desafio = d.id_desafio
    INNER JOIN organizacion o
        ON d.id_organizacion = o.id_organizacion
    INNER JOIN estudiante e
        ON p.id_estudiante = e.id_estudiante
    LEFT JOIN conversacion c
        ON p.id_propuesta = c.id_propuesta
        AND c.activa = TRUE
        AND LOWER(COALESCE(p.estado, '')) = 'aceptada'
    WHERE d.id_organizacion = :id_organizacion
    ORDER BY p.fecha_envio DESC
");
$stmt->execute([':id_organizacion' => $idUsuario]);
$propuestas = $stmt->fetchAll();

$documentosPorPropuesta = [];
$idsPropuestas = array_column($propuestas, 'id_propuesta');

if (!empty($idsPropuestas)) {
    $placeholders = implode(',', array_fill(0, count($idsPropuestas), '?'));

    $stmtDocs = $pdo->prepare("
        SELECT
            id_propuesta,
            nombre_archivo,
            tipo_archivo,
            url_archivo,
            fecha_subida,
            tamano_bytes
        FROM documento_propuesta
        WHERE id_propuesta IN ($placeholders)
        ORDER BY fecha_subida DESC
    ");
    $stmtDocs->execute($idsPropuestas);

    foreach ($stmtDocs->fetchAll(PDO::FETCH_ASSOC) as $documento) {
        $documentosPorPropuesta[(int) $documento['id_propuesta']][] = $documento;
    }
}

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Propuestas recibidas - EduNexo MP</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard_organizacion.css">
    <link rel="stylesheet" href="../assets/css/org/propuestas_organizacion.css?v=modal-preview-2">
    <link rel="stylesheet" href="../assets/css/dark.css?v=dark-fix-2">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<div class="app-layout org-layout">
    <?php include __DIR__ . '/../includes/sidebar_organizacion.php'; ?>

    <section class="app-content org-content org-page-content">
        <?php include __DIR__ . '/../includes/app_topbar.php'; ?>

        <div class="org-page-wrap">
            <div class="org-page-head">
                <div>
                    <h1>Propuestas recibidas</h1>
                    <p>Revisa las postulaciones enviadas por estudiantes a tus desafíos</p>
                </div>
                <a href="dashboard_organizacion.php" class="btn btn-nav">Volver</a>
            </div>

            <?php if (!empty($propuestas)): ?>
                <div class="org-propuestas-grid">
                    <?php foreach ($propuestas as $propuesta): ?>
                        <?php
                        $nombreEstudiante = trim(
                            $propuesta['nombre'] . ' ' .
                            $propuesta['apellido_paterno'] . ' ' .
                            ($propuesta['apellido_materno'] ?? '')
                        );

                        $estadoClase = strtolower(str_replace(' ', '-', $propuesta['estado'] ?? ''));
                        $modalId = 'modal_propuesta_' . (int) $propuesta['id_propuesta'];
                        $documentos = $documentosPorPropuesta[(int) $propuesta['id_propuesta']] ?? [];
                        ?>
                        <article class="org-propuesta-card">
                            <div class="org-propuesta-head">
                                <div>
                                    <h3><?php echo htmlspecialchars($propuesta['titulo']); ?></h3>
                                    <p class="org-propuesta-student">
                                        <i class="bi bi-person-circle"></i>
                                        <?php echo htmlspecialchars($nombreEstudiante); ?>
                                    </p>
                                </div>

                                <span class="org-propuesta-badge estado-<?php echo htmlspecialchars($estadoClase); ?>">
                                    <?php echo htmlspecialchars($propuesta['estado'] ?? 'Sin estado'); ?>
                                </span>
                            </div>

                            <div class="org-propuesta-meta">
                                <div>
                                    <span>Carrera</span>
                                    <strong><?php echo htmlspecialchars($propuesta['carrera']); ?></strong>
                                </div>

                                <div>
                                    <span>Semestre</span>
                                    <strong><?php echo htmlspecialchars($propuesta['semestre']); ?>°</strong>
                                </div>

                                <div>
                                    <span>Modalidad</span>
                                    <strong><?php echo htmlspecialchars($propuesta['modalidad'] ?? 'No definida'); ?></strong>
                                </div>

                                <div>
                                    <span>Fecha de envío</span>
                                    <strong><?php echo htmlspecialchars(date('d/m/Y', strtotime($propuesta['fecha_envio']))); ?></strong>
                                </div>

                                <div>
                                    <span>Fecha de respuesta</span>
                                    <strong>
                                        <?php
                                        echo !empty($propuesta['fecha_respuesta'])
                                            ? htmlspecialchars(date('d/m/Y', strtotime($propuesta['fecha_respuesta'])))
                                            : 'Pendiente';
                                        ?>
                                    </strong>
                                </div>

                                <div class="org-feedback-box">
                                    <span>Feedback actual</span>
                                    <p>
                                        <?php echo !empty($propuesta['feedback'])
                                            ? nl2br(htmlspecialchars($propuesta['feedback']))
                                            : 'Aún no se ha registrado retroalimentación.'; ?>
                                    </p>
                                </div>
                            </div>

                            <div class="org-propuesta-actions">
                                <button
                                    type="button"
                                    class="btn btn-primary"
                                    data-modal-target="<?php echo htmlspecialchars($modalId); ?>"
                                >
                                    <i class="bi bi-file-earmark-richtext"></i>
                                    Ver propuesta
                                </button>
                                <a href="./desafios/detalle_desafio_organizacion.php?id=<?php echo (int) $propuesta['id_desafio']; ?>" class="btn btn-nav">
                                    Ver desafío
                                </a>

                                <?php if (!empty($propuesta['id_conversacion'])): ?>
                                    <a href="chat_organizacion.php?id=<?php echo (int) $propuesta['id_conversacion']; ?>" class="btn btn-nav">
                                        Abrir chat
                                    </a>
                                <?php else: ?>
                                    <button type="button" class="btn btn-nav" disabled>
                                        Sin chat
                                    </button>
                                <?php endif; ?>
                            </div>

                            <form action="../procesos/cambiar_estado_propuesta.php" method="POST" class="org-estado-form">
                                <?php echo edunexo_csrf_input(); ?>
                                <input type="hidden" name="id_propuesta" value="<?php echo (int) $propuesta['id_propuesta']; ?>">

                                <label for="estado_<?php echo (int) $propuesta['id_propuesta']; ?>">Cambiar estado</label>

                                <div class="org-estado-row">
                                    <select
                                        id="estado_<?php echo (int) $propuesta['id_propuesta']; ?>"
                                        name="estado"
                                        class="org-estado-select"
                                    >
                                        <option value="en revisión" <?php echo (($propuesta['estado'] ?? '') === 'en revisión') ? 'selected' : ''; ?>>
                                            En revisión
                                        </option>
                                        <option value="aceptada" <?php echo (($propuesta['estado'] ?? '') === 'aceptada') ? 'selected' : ''; ?>>
                                            Aceptada
                                        </option>
                                        <option value="rechazada" <?php echo (($propuesta['estado'] ?? '') === 'rechazada') ? 'selected' : ''; ?>>
                                            Rechazada
                                        </option>
                                    </select>

                                    <button type="submit" class="btn btn-primary">
                                        Guardar
                                    </button>
                                </div>

                                <label for="feedback_<?php echo (int) $propuesta['id_propuesta']; ?>" class="org-feedback-label">
                                    Feedback para el estudiante
                                </label>

                                <textarea
                                    id="feedback_<?php echo (int) $propuesta['id_propuesta']; ?>"
                                    name="feedback"
                                    class="org-feedback-textarea"
                                    rows="4"
                                    placeholder="Escribe retroalimentación para esta propuesta..."
                                ><?php echo htmlspecialchars($propuesta['feedback'] ?? ''); ?></textarea>
                            </form>
                        </article>

                        <div class="org-propuesta-modal" id="<?php echo htmlspecialchars($modalId); ?>" aria-hidden="true">
                            <div class="org-propuesta-modal-backdrop" data-modal-close></div>
                            <section class="org-propuesta-modal-panel" role="dialog" aria-modal="true" aria-labelledby="<?php echo htmlspecialchars($modalId); ?>_title">
                                <div class="org-propuesta-modal-head">
                                    <div>
                                        <span class="org-modal-kicker">Propuesta enviada</span>
                                        <h2 id="<?php echo htmlspecialchars($modalId); ?>_title">
                                            <?php echo htmlspecialchars($propuesta['titulo_propuesta'] ?: $propuesta['titulo']); ?>
                                        </h2>
                                        <p>
                                            <?php echo htmlspecialchars($nombreEstudiante); ?> ·
                                            <?php echo htmlspecialchars($propuesta['carrera']); ?>
                                        </p>
                                    </div>

                                    <button type="button" class="org-modal-close" data-modal-close aria-label="Cerrar modal">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>

                                <div class="org-propuesta-modal-body">
                                    <div class="org-modal-summary">
                                        <div>
                                            <span>Desafio</span>
                                            <strong><?php echo htmlspecialchars($propuesta['titulo']); ?></strong>
                                        </div>
                                        <div>
                                            <span>Estado</span>
                                            <strong><?php echo htmlspecialchars($propuesta['estado'] ?? 'Sin estado'); ?></strong>
                                        </div>
                                        <div>
                                            <span>Fecha de envio</span>
                                            <strong><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($propuesta['fecha_envio']))); ?></strong>
                                        </div>
                                        <div>
                                            <span>Portafolio</span>
                                            <?php if (!empty($propuesta['enlace_portafolio'])): ?>
                                                <a href="<?php echo htmlspecialchars($propuesta['enlace_portafolio']); ?>" target="_blank" rel="noopener">
                                                    Abrir enlace
                                                </a>
                                            <?php else: ?>
                                                <strong>No registrado</strong>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="org-modal-section">
                                        <h3>Descripcion del estudiante</h3>
                                        <p>
                                            <?php echo !empty($propuesta['descripcion_breve'])
                                                ? nl2br(htmlspecialchars($propuesta['descripcion_breve']))
                                                : 'El estudiante no agrego una descripcion adicional.'; ?>
                                        </p>
                                    </div>

                                    <div class="org-modal-section">
                                        <h3>Archivos adjuntos</h3>

                                        <?php if (!empty($documentos)): ?>
                                            <div class="org-doc-list">
                                                <?php foreach ($documentos as $documento): ?>
                                                    <?php
                                                    $urlArchivo = '../' . ltrim($documento['url_archivo'], '/');
                                                    $extension = strtolower(pathinfo($documento['nombre_archivo'], PATHINFO_EXTENSION));
                                                    $tamanoMb = !empty($documento['tamano_bytes'])
                                                        ? number_format(((int) $documento['tamano_bytes']) / 1048576, 2) . ' MB'
                                                        : 'Tamano no disponible';
                                                    ?>

                                                    <div class="org-doc-item">
                                                        <div class="org-doc-info">
                                                            <i class="bi bi-file-earmark-text"></i>
                                                            <div>
                                                                <strong><?php echo htmlspecialchars($documento['nombre_archivo']); ?></strong>
                                                                <span><?php echo htmlspecialchars(strtoupper($extension)); ?> · <?php echo htmlspecialchars($tamanoMb); ?></span>
                                                            </div>
                                                        </div>

                                                        <a href="<?php echo htmlspecialchars($urlArchivo); ?>" target="_blank" rel="noopener" class="btn btn-nav">
                                                            Abrir
                                                        </a>
                                                    </div>

                                                    <?php if ($extension === 'pdf'): ?>
                                                        <iframe
                                                            class="org-pdf-preview"
                                                            src="<?php echo htmlspecialchars($urlArchivo); ?>"
                                                            title="Vista previa de <?php echo htmlspecialchars($documento['nombre_archivo']); ?>"
                                                        ></iframe>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <p class="org-modal-empty">Esta propuesta no tiene archivos adjuntos.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </section>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="org-empty-card">
                    <i class="bi bi-inbox"></i>
                    <h3>Aún no has recibido propuestas</h3>
                    <p>Cuando estudiantes se postulen a tus desafíos, aparecerán aquí.</p>
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

<script>
document.addEventListener('click', function (event) {
    const trigger = event.target.closest('[data-modal-target]');
    const closeButton = event.target.closest('[data-modal-close]');

    if (trigger) {
        const modal = document.getElementById(trigger.dataset.modalTarget);

        if (modal) {
            document.querySelectorAll('.app-notification-menu, .app-user-menu').forEach(function (menu) {
                menu.classList.remove('is-open');
            });
            document.querySelectorAll('#appNotificationToggle, #appUserToggle').forEach(function (button) {
                button.setAttribute('aria-expanded', 'false');
            });
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('org-modal-open');
        }
    }

    if (closeButton) {
        const modal = closeButton.closest('.org-propuesta-modal');

        if (modal) {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('org-modal-open');
        }
    }
});

document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') {
        return;
    }

    document.querySelectorAll('.org-propuesta-modal.is-open').forEach(function (modal) {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    });
    document.body.classList.remove('org-modal-open');
});
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
