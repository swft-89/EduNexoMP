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
    WHERE d.id_organizacion = :id_organizacion
    ORDER BY p.fecha_envio DESC
");
$stmt->execute([':id_organizacion' => $idUsuario]);
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
    <title>Propuestas recibidas - EduNexo MP</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard_organizacion.css">
    <link rel="stylesheet" href="../assets/css/org/propuestas_organizacion.css">
    <link rel="stylesheet" href="../assets/css/dark.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<div class="app-layout org-layout">
    <aside class="sidebar org-sidebar">
        <div class="sidebar-top">
            <div class="logo-mini">EN</div>
            <span>EduNexo MP</span>
        </div>

        <nav class="sidebar-menu">
            <a href="dashboard_organizacion.php">
                <i class="bi bi-house-door"></i> Inicio
            </a>
            <a href="crear_desafio.php">
                <i class="bi bi-plus-circle"></i> Crear desafío
            </a>
            <a href="mis_desafios.php">
                <i class="bi bi-briefcase"></i> Mis desafíos
            </a>
            <a href="propuestas_recibidas.php" class="active">
                <i class="bi bi-file-earmark-text"></i> Propuestas
            </a>
            <a href="chat_organizacion.php">
                <i class="bi bi-chat"></i> Chat
            </a>
            <a href="editar_perfil_organizacion.php">
                <i class="bi bi-building"></i> Perfil
            </a>
        </nav>
    </aside>

    <section class="app-content org-content">
        <div class="org-topbar">
            <div></div>
            <div class="org-topbar-right">
                <button class="org-icon-btn" type="button">
                    <i class="bi bi-bell"></i>
                </button>
                <div class="org-avatar">
                    <?php echo htmlspecialchars(strtoupper(substr($_SESSION['rol'], 0, 1))); ?>
                </div>
            </div>
        </div>

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
                                <a href="./desafios/detalle_desafio.php?id=<?php echo (int) $propuesta['id_desafio']; ?>" class="btn btn-nav">
                                    Ver desafío
                                </a>

                                <?php if (!empty($propuesta['id_conversacion'])): ?>
                                    <a href="chat_organizacion.php?id=<?php echo (int) $propuesta['id_conversacion']; ?>" class="btn btn-primary">
                                        Abrir chat
                                    </a>
                                <?php else: ?>
                                    <button type="button" class="btn btn-primary" disabled>
                                        Sin chat
                                    </button>
                                <?php endif; ?>
                            </div>

                            <form action="../procesos/cambiar_estado_propuesta.php" method="POST" class="org-estado-form">
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>