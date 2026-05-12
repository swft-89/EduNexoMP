<?php
require_once __DIR__ . '../../../includes/session_superadmin.php';
require_once __DIR__ . '../../../config/conexion.php';

$idDesafio = (int) ($_GET['id'] ?? 0);

if ($idDesafio <= 0) {
    $_SESSION['error'] = 'Desafío inválido.';
    header('Location: desafios_superadmin.php');
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

$stmtDesafio = $pdo->prepare("
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
        c.nombre_categoria,
        (
            SELECT COUNT(*)
            FROM propuesta p
            WHERE p.id_desafio = d.id_desafio
        ) AS total_propuestas
    FROM desafio d
    INNER JOIN organizacion o ON o.id_organizacion = d.id_organizacion
    LEFT JOIN categoria c ON c.id_categoria = d.id_categoria
    WHERE d.id_desafio = :id_desafio
    LIMIT 1
");
$stmtDesafio->execute([
    ':id_desafio' => $idDesafio
]);

$desafio = $stmtDesafio->fetch(PDO::FETCH_ASSOC);

if (!$desafio) {
    $_SESSION['error'] = 'No se encontró el desafío solicitado.';
    header('Location: desafios_superadmin.php');
    exit;
}

$stmtHabilidades = $pdo->prepare("
    SELECT
        h.nombre,
        dh.nivel_requerido,
        dh.obligatorio
    FROM desafio_habilidad dh
    INNER JOIN habilidad h ON h.id_habilidad = dh.id_habilidad
    WHERE dh.id_desafio = :id_desafio
    ORDER BY h.nombre ASC
");
$stmtHabilidades->execute([
    ':id_desafio' => $idDesafio
]);
$habilidades = $stmtHabilidades->fetchAll(PDO::FETCH_ASSOC);

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de desafío | Superadmin - EduNexo MP</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/dark.css">
    <link rel="stylesheet" href="../../assets/css/superadmin/dashboard_superadmin.css">
    <link rel="stylesheet" href="../../assets/css/superadmin/superadmin_sections.css">
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
            <a href="../dashboard_superadmin.php" class="active">
                <i class="bi bi-bar-chart-line"></i> Dashboard
            </a>
            <a href="../usuarios/usuarios_superadmin.php">
                <i class="bi bi-people"></i> Usuarios
            </a>
            <a href="../usuarios/solicitudes_admin.php">
                <i class="bi bi-person-check"></i> Solicitudes admin
            </a>
            <a href="../reportes_superadmin.php">
                <i class="bi bi-clipboard-data"></i> Reportes
            </a>
            <a href="../desafios/desafios_superadmin.php">
                <i class="bi bi-file-earmark-text"></i> Desafíos
            </a>
            <a href="../propuestas/propuestas_superadmin.php">
                <i class="bi bi-send"></i> Propuestas
            </a>
            <a href="../categorias_superadmin.php">
                <i class="bi bi-tags"></i> Categorías
            </a>
        </nav>
    </aside>

    <section class="app-content superadmin-content">
        <?php include __DIR__ . '/../../includes/app_topbar.php'; ?>

        <div class="superadmin-header">
            <div>
                <h1>Detalle de desafío</h1>
                <p>Información completa del desafío publicado en la plataforma.</p>
            </div>

            <div class="superadmin-actions">
                <a href="desafios_superadmin.php" class="btn superadmin-btn-light">
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
                    <h2><?php echo htmlspecialchars($desafio['titulo']); ?></h2>
                    <p><?php echo htmlspecialchars($desafio['nombre_empresa']); ?></p>
                </div>

                <div class="detail-user-badges">
                    <?php
                    $estado = strtolower($desafio['estado'] ?? 'activo');
                    $estadoClase = 'status-pill inactive';
                    if ($estado === 'activo') {
                        $estadoClase = 'status-pill active';
                    } elseif ($estado === 'pausado') {
                        $estadoClase = 'status-pill pending';
                    } elseif ($estado === 'cerrado') {
                        $estadoClase = 'status-pill suspended';
                    }
                    ?>
                    <span class="<?php echo $estadoClase; ?>">
                        <?php echo htmlspecialchars($desafio['estado']); ?>
                    </span>
                </div>
            </div>
        </section>

        <div class="detail-grid">
            <section class="superadmin-panel-card">
                <div class="panel-card-head">
                    <div>
                        <h2>Información general</h2>
                        <p>Datos principales del desafío</p>
                    </div>
                </div>

                <div class="detail-list">
                    <div class="detail-item">
                        <span>ID desafío</span>
                        <strong><?php echo (int) $desafio['id_desafio']; ?></strong>
                    </div>
                    <div class="detail-item">
                        <span>Categoría</span>
                        <strong><?php echo htmlspecialchars($desafio['nombre_categoria'] ?: 'Sin categoría'); ?></strong>
                    </div>
                    <div class="detail-item">
                        <span>Modalidad</span>
                        <strong><?php echo htmlspecialchars($desafio['modalidad'] ?: 'No especificada'); ?></strong>
                    </div>
                    <div class="detail-item">
                        <span>Fecha publicación</span>
                        <strong><?php echo !empty($desafio['fecha_publicacion']) ? htmlspecialchars(date('Y-m-d H:i', strtotime($desafio['fecha_publicacion']))) : 'No disponible'; ?></strong>
                    </div>
                    <div class="detail-item">
                        <span>Fecha límite</span>
                        <strong><?php echo !empty($desafio['fecha_limite']) ? htmlspecialchars(date('Y-m-d', strtotime($desafio['fecha_limite']))) : 'Sin fecha'; ?></strong>
                    </div>
                    <div class="detail-item">
                        <span>Propuestas</span>
                        <strong><?php echo (int) $desafio['total_propuestas']; ?></strong>
                    </div>
                </div>
            </section>

            <section class="superadmin-panel-card">
                <div class="panel-card-head">
                    <div>
                        <h2>Acciones rápidas</h2>
                        <p>Control del estado del desafío</p>
                    </div>
                </div>

                <div class="detail-actions">
                    <?php if ($estado !== 'activo'): ?>
                        <form action="../../procesos/cambiar_estado_desafio_admin.php" method="POST">
                            <input type="hidden" name="id_desafio" value="<?php echo (int) $desafio['id_desafio']; ?>">
                            <input type="hidden" name="nuevo_estado" value="activo">
                            <button type="submit" class="btn btn-primary">Activar desafío</button>
                        </form>
                    <?php endif; ?>

                    <?php if ($estado !== 'pausado'): ?>
                        <form action="../../procesos/cambiar_estado_desafio_admin.php" method="POST">
                            <input type="hidden" name="id_desafio" value="<?php echo (int) $desafio['id_desafio']; ?>">
                            <input type="hidden" name="nuevo_estado" value="pausado">
                            <button type="submit" class="btn superadmin-btn-light">Pausar desafío</button>
                        </form>
                    <?php endif; ?>

                    <?php if ($estado !== 'cerrado'): ?>
                        <form action="../../procesos/cambiar_estado_desafio_admin.php" method="POST">
                            <input type="hidden" name="id_desafio" value="<?php echo (int) $desafio['id_desafio']; ?>">
                            <input type="hidden" name="nuevo_estado" value="cerrado">
                            <button type="submit" class="btn btn-reject">Cerrar desafío</button>
                        </form>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <section class="superadmin-panel-card full-card">
            <div class="panel-card-head">
                <div>
                    <h2>Descripción</h2>
                    <p>Resumen del reto publicado</p>
                </div>
            </div>

            <div class="text-block-card">
                <p><?php echo nl2br(htmlspecialchars($desafio['descripcion'] ?: 'Sin descripción')); ?></p>
            </div>
        </section>

        <section class="superadmin-panel-card full-card">
            <div class="panel-card-head">
                <div>
                    <h2>Requisitos específicos</h2>
                    <p>Condiciones o detalles extra del desafío</p>
                </div>
            </div>

            <div class="text-block-card">
                <p><?php echo nl2br(htmlspecialchars($desafio['requisitos_especificos'] ?: 'No se registraron requisitos específicos.')); ?></p>
            </div>
        </section>

        <section class="superadmin-panel-card full-card">
            <div class="panel-card-head">
                <div>
                    <h2>Organización</h2>
                    <p>Datos relacionados con la empresa que publicó el desafío</p>
                </div>
            </div>

            <div class="detail-grid-inner">
                <div class="detail-item"><span>Empresa</span><strong><?php echo htmlspecialchars($desafio['nombre_empresa']); ?></strong></div>
                <div class="detail-item"><span>Representante</span><strong><?php echo htmlspecialchars($desafio['representante'] ?: 'No registrado'); ?></strong></div>
                <div class="detail-item"><span>Teléfono</span><strong><?php echo htmlspecialchars($desafio['telefono_contacto'] ?: 'No registrado'); ?></strong></div>
            </div>
        </section>

        <section class="superadmin-panel-card full-card">
            <div class="panel-card-head">
                <div>
                    <h2>Habilidades requeridas</h2>
                    <p>Habilidades asociadas al desafío</p>
                </div>
            </div>

            <?php if (!empty($habilidades)): ?>
                <div class="chips-wrap">
                    <?php foreach ($habilidades as $habilidad): ?>
                        <div class="skill-chip">
                            <strong><?php echo htmlspecialchars($habilidad['nombre']); ?></strong>
                            <span>
                                <?php
                                $detalle = [];
                                if (!empty($habilidad['nivel_requerido'])) {
                                    $detalle[] = 'Nivel: ' . $habilidad['nivel_requerido'];
                                }
                                if (!empty($habilidad['obligatorio'])) {
                                    $detalle[] = 'Obligatoria';
                                }
                                echo htmlspecialchars(!empty($detalle) ? implode(' · ', $detalle) : 'Sin detalle');
                                ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty-text">No hay habilidades asociadas a este desafío.</p>
            <?php endif; ?>
        </section>
    </section>
</div>
<script src="../../assets/js/main.js"></script>
</body>
</html>