<?php
require_once __DIR__ . '/includes/session_superadmin.php';
require_once __DIR__ . '/config/conexion.php';

$idPropuesta = (int) ($_GET['id'] ?? 0);

if ($idPropuesta <= 0) {
    $_SESSION['error'] = 'Propuesta inválida.';
    header('Location: propuestas_superadmin.php');
    exit;
}

$stmtAdmin = $pdo->prepare("
    SELECT nombre
    FROM administrador
    WHERE id_admin = :id_admin
    LIMIT 1
");
$stmtAdmin->execute([
    ':id_admin' => $_SESSION['usuario_id']
]);
$admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
$inicialAdmin = strtoupper(substr($admin['nombre'] ?? 'U', 0, 1));

$stmtPropuesta = $pdo->prepare("
    SELECT
        p.id_propuesta,
        p.fecha_envio,
        p.estado,
        p.feedback,
        p.fecha_respuesta,

        CONCAT_WS(' ', e.nombre, e.apellido_paterno, e.apellido_materno) AS nombre_estudiante,
        e.carrera,
        e.semestre,
        e.no_control,
        e.telefono,

        d.titulo AS titulo_desafio,
        d.descripcion AS descripcion_desafio,
        d.modalidad,
        d.estado AS estado_desafio,

        o.nombre_empresa,
        o.representante,
        o.telefono_contacto

    FROM propuesta p
    INNER JOIN estudiante e ON e.id_estudiante = p.id_estudiante
    INNER JOIN desafio d ON d.id_desafio = p.id_desafio
    INNER JOIN organizacion o ON o.id_organizacion = d.id_organizacion
    WHERE p.id_propuesta = :id_propuesta
    LIMIT 1
");
$stmtPropuesta->execute([
    ':id_propuesta' => $idPropuesta
]);

$propuesta = $stmtPropuesta->fetch(PDO::FETCH_ASSOC);

if (!$propuesta) {
    $_SESSION['error'] = 'No se encontró la propuesta solicitada.';
    header('Location: propuestas_superadmin.php');
    exit;
}

$stmtDocs = $pdo->prepare("
    SELECT
        id_documento,
        nombre_archivo,
        tipo_archivo,
        url_archivo,
        fecha_subida,
        tamano_bytes
    FROM documento_propuesta
    WHERE id_propuesta = :id_propuesta
    ORDER BY fecha_subida DESC
");
$stmtDocs->execute([
    ':id_propuesta' => $idPropuesta
]);
$documentos = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de propuesta | Superadmin - EduNexo MP</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dark.css">
    <link rel="stylesheet" href="assets/css/dashboard_superadmin.css">
    <link rel="stylesheet" href="assets/css/superadmin_sections.css">
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
            <a href="dashboard_superadmin.php">
                <i class="bi bi-bar-chart-line"></i> Dashboard
            </a>
            <a href="usuarios_superadmin.php">
                <i class="bi bi-people"></i> Usuarios
            </a>
            <a href="desafios_superadmin.php">
                <i class="bi bi-file-earmark-text"></i> Desafíos
            </a>
            <a href="solicitudes_admin.php">
                <i class="bi bi-person-check"></i> Solicitudes admin
            </a>
            <a href="reportes_superadmin.php">
                <i class="bi bi-clipboard-data"></i> Reportes
            </a>
            <a href="propuestas_superadmin.php">
                <i class="bi bi-send"></i> Propuestas
            </a>
            <a href="categorias_superadmin.php">
                <i class="bi bi-tags"></i> Categorías
            </a>
        </nav>
    </aside>

    <section class="app-content superadmin-content">
        <div class="superadmin-topbar">
            <div></div>
            <div class="superadmin-topbar-right">
                <button class="superadmin-icon-btn" type="button">
                    <i class="bi bi-bell"></i>
                </button>
                <div class="superadmin-avatar">
                    <?php echo htmlspecialchars($inicialAdmin); ?>
                </div>
            </div>
        </div>

        <div class="superadmin-header">
            <div>
                <h1>Detalle de propuesta</h1>
                <p>Consulta la información completa de la postulación enviada por el estudiante.</p>
            </div>

            <div class="superadmin-actions">
                <a href="propuestas_superadmin.php" class="btn superadmin-btn-light">
                    <i class="bi bi-arrow-left"></i>
                    Volver
                </a>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="superadmin-alert success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="superadmin-alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <section class="superadmin-panel-card full-card">
            <div class="detail-user-header">
                <div>
                    <h2><?php echo htmlspecialchars($propuesta['nombre_estudiante'] ?: 'Estudiante'); ?></h2>
                    <p><?php echo htmlspecialchars($propuesta['titulo_desafio']); ?></p>
                </div>

                <div class="detail-user-badges">
                    <?php
                    $estado = strtolower($propuesta['estado'] ?? 'pendiente');
                    $estadoClase = 'status-pill inactive';
                    if ($estado === 'aceptada') {
                        $estadoClase = 'status-pill active';
                    } elseif ($estado === 'rechazada') {
                        $estadoClase = 'status-pill suspended';
                    } elseif ($estado === 'pendiente' || $estado === 'en revision') {
                        $estadoClase = 'status-pill pending';
                    }
                    ?>
                    <span class="<?php echo $estadoClase; ?>">
                        <?php echo htmlspecialchars($propuesta['estado']); ?>
                    </span>
                </div>
            </div>
        </section>

        <div class="detail-grid">
            <section class="superadmin-panel-card">
                <div class="panel-card-head">
                    <div>
                        <h2>Información de la propuesta</h2>
                        <p>Datos generales del envío</p>
                    </div>
                </div>

                <div class="detail-list">
                    <div class="detail-item">
                        <span>ID propuesta</span>
                        <strong><?php echo (int) $propuesta['id_propuesta']; ?></strong>
                    </div>
                    <div class="detail-item">
                        <span>Fecha de envío</span>
                        <strong><?php echo !empty($propuesta['fecha_envio']) ? htmlspecialchars(date('Y-m-d H:i', strtotime($propuesta['fecha_envio']))) : 'No disponible'; ?></strong>
                    </div>
                    <div class="detail-item">
                        <span>Estado</span>
                        <strong><?php echo htmlspecialchars($propuesta['estado']); ?></strong>
                    </div>
                    <div class="detail-item">
                        <span>Fecha de respuesta</span>
                        <strong><?php echo !empty($propuesta['fecha_respuesta']) ? htmlspecialchars(date('Y-m-d H:i', strtotime($propuesta['fecha_respuesta']))) : 'No disponible'; ?></strong>
                    </div>
                </div>
            </section>

            <section class="superadmin-panel-card">
                <div class="panel-card-head">
                    <div>
                        <h2>Feedback</h2>
                        <p>Retroalimentación registrada en el sistema</p>
                    </div>
                </div>

                <div class="text-block-card">
                    <p><?php echo nl2br(htmlspecialchars($propuesta['feedback'] ?: 'No hay feedback registrado para esta propuesta.')); ?></p>
                </div>
            </section>
        </div>

        <section class="superadmin-panel-card full-card">
            <div class="panel-card-head">
                <div>
                    <h2>Datos del estudiante</h2>
                    <p>Información académica y de contacto</p>
                </div>
            </div>

            <div class="detail-grid-inner">
                <div class="detail-item"><span>Nombre</span><strong><?php echo htmlspecialchars($propuesta['nombre_estudiante'] ?: 'No registrado'); ?></strong></div>
                <div class="detail-item"><span>Carrera</span><strong><?php echo htmlspecialchars($propuesta['carrera'] ?: 'No registrada'); ?></strong></div>
                <div class="detail-item"><span>Semestre</span><strong><?php echo htmlspecialchars((string) ($propuesta['semestre'] ?? 'No registrado')); ?></strong></div>
                <div class="detail-item"><span>No. control</span><strong><?php echo htmlspecialchars($propuesta['no_control'] ?: 'No registrado'); ?></strong></div>
                <div class="detail-item"><span>Teléfono</span><strong><?php echo htmlspecialchars($propuesta['telefono'] ?: 'No registrado'); ?></strong></div>
            </div>
        </section>

        <section class="superadmin-panel-card full-card">
            <div class="panel-card-head">
                <div>
                    <h2>Desafío relacionado</h2>
                    <p>Información del desafío al que se postuló</p>
                </div>
            </div>

            <div class="detail-grid-inner">
                <div class="detail-item"><span>Título</span><strong><?php echo htmlspecialchars($propuesta['titulo_desafio']); ?></strong></div>
                <div class="detail-item"><span>Organización</span><strong><?php echo htmlspecialchars($propuesta['nombre_empresa']); ?></strong></div>
                <div class="detail-item"><span>Modalidad</span><strong><?php echo htmlspecialchars($propuesta['modalidad'] ?: 'No especificada'); ?></strong></div>
                <div class="detail-item"><span>Estado del desafío</span><strong><?php echo htmlspecialchars($propuesta['estado_desafio'] ?: 'No disponible'); ?></strong></div>
                <div class="detail-item"><span>Representante</span><strong><?php echo htmlspecialchars($propuesta['representante'] ?: 'No registrado'); ?></strong></div>
                <div class="detail-item"><span>Teléfono organización</span><strong><?php echo htmlspecialchars($propuesta['telefono_contacto'] ?: 'No registrado'); ?></strong></div>
            </div>
        </section>

        <section class="superadmin-panel-card full-card">
            <div class="panel-card-head">
                <div>
                    <h2>Descripción del desafío</h2>
                    <p>Contexto general de la vacante o reto</p>
                </div>
            </div>

            <div class="text-block-card">
                <p><?php echo nl2br(htmlspecialchars($propuesta['descripcion_desafio'] ?: 'Sin descripción disponible.')); ?></p>
            </div>
        </section>

        <section class="superadmin-panel-card full-card">
            <div class="panel-card-head">
                <div>
                    <h2>Documentos adjuntos</h2>
                    <p>Archivos enviados junto con la propuesta</p>
                </div>
            </div>

            <?php if (!empty($documentos)): ?>
                <div class="detail-grid-inner">
                    <?php foreach ($documentos as $doc): ?>
                        <div class="detail-item">
                            <span><?php echo htmlspecialchars($doc['nombre_archivo']); ?></span>
                            <strong><?php echo htmlspecialchars($doc['tipo_archivo'] ?: 'Archivo'); ?></strong>
                            <small class="row-subtext">
                                <?php echo !empty($doc['fecha_subida']) ? htmlspecialchars(date('Y-m-d H:i', strtotime($doc['fecha_subida']))) : 'Sin fecha'; ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty-text">No hay documentos asociados a esta propuesta.</p>
            <?php endif; ?>
        </section>
    </section>
</div>
</body>
</html>