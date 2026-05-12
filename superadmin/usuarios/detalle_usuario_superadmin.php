<?php
require_once __DIR__ . '/../../includes/session_superadmin.php';
require_once __DIR__ . '/../../config/conexion.php';

$idUsuario = (int) ($_GET['id'] ?? 0);

if ($idUsuario <= 0) {
    $_SESSION['error'] = 'Usuario inválido.';
    header('Location: usuarios_superadmin.php');
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
$adminSesion = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
$inicialAdmin = strtoupper(substr($adminSesion['nombre'] ?? 'U', 0, 1));

$stmtUsuario = $pdo->prepare("
    SELECT
        u.id_usuario,
        u.correo_electronico,
        u.rol,
        u.estado,
        u.fecha_registro,

        e.nombre AS est_nombre,
        e.apellido_paterno AS est_apellido_paterno,
        e.apellido_materno AS est_apellido_materno,
        e.carrera,
        e.no_control,
        e.semestre,
        e.curp,
        e.telefono,

        o.nombre_empresa,
        o.rfc,
        o.sector,
        o.representante,
        o.telefono_contacto,

        a.nombre AS adm_nombre,
        a.apellido_paterno AS adm_apellido_paterno,
        a.apellido_materno AS adm_apellido_materno,
        a.puesto,
        a.departamento,
        a.tipo_admin,
        a.estado_solicitud,
        a.fecha_autorizacion,

        d.pais,
        d.estado AS dir_estado,
        d.ciudad,
        d.colonia,
        d.codigo_postal,
        d.calle,
        d.num_exterior

    FROM usuario u
    LEFT JOIN estudiante e ON e.id_estudiante = u.id_usuario
    LEFT JOIN organizacion o ON o.id_organizacion = u.id_usuario
    LEFT JOIN administrador a ON a.id_admin = u.id_usuario
    LEFT JOIN direccion d
        ON d.id_direccion = COALESCE(e.id_direccion, o.id_direccion)
    WHERE u.id_usuario = :id_usuario
    LIMIT 1
");
$stmtUsuario->execute([
    ':id_usuario' => $idUsuario
]);

$usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    $_SESSION['error'] = 'No se encontró el usuario solicitado.';
    header('Location: usuarios_superadmin.php');
    exit;
}

$nombreMostrar = $usuario['correo_electronico'];

if (($usuario['rol'] ?? '') === 'estudiante') {
    $nombreMostrar = trim(
        ($usuario['est_nombre'] ?? '') . ' ' .
        ($usuario['est_apellido_paterno'] ?? '') . ' ' .
        ($usuario['est_apellido_materno'] ?? '')
    );
} elseif (($usuario['rol'] ?? '') === 'organizacion') {
    $nombreMostrar = $usuario['nombre_empresa'] ?: $usuario['correo_electronico'];
} elseif (($usuario['rol'] ?? '') === 'administrador') {
    $nombreMostrar = trim(
        ($usuario['adm_nombre'] ?? '') . ' ' .
        ($usuario['adm_apellido_paterno'] ?? '') . ' ' .
        ($usuario['adm_apellido_materno'] ?? '')
    );
}

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

$esYo = ((int) $usuario['id_usuario'] === (int) $_SESSION['usuario_id']);
$esSuperadminObjetivo = (($usuario['tipo_admin'] ?? '') === 'superadmin');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de usuario | Superadmin - EduNexo MP</title>
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
            <div class="logo-mini">EN</div>
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
                <h1>Detalle de usuario</h1>
                <p>Consulta completa del perfil registrado en la plataforma.</p>
            </div>

            <div class="superadmin-actions">
                <a href="usuarios_superadmin.php" class="btn superadmin-btn-light">
                    <i class="bi bi-arrow-left"></i>
                    Volver
                </a>
            </div>
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

        <section class="superadmin-panel-card full-card">
            <div class="detail-user-header">
                <div>
                    <h2><?php echo htmlspecialchars($nombreMostrar ?: 'Sin nombre'); ?></h2>
                    <p><?php echo htmlspecialchars($usuario['correo_electronico']); ?></p>
                </div>

                <div class="detail-user-badges">
                    <span class="mini-role"><?php echo htmlspecialchars($usuario['rol']); ?></span>

                    <?php
                    $estadoClase = 'status-pill';
                    if (($usuario['estado'] ?? '') === 'activo') {
                        $estadoClase .= ' active';
                    } elseif (($usuario['estado'] ?? '') === 'suspendido') {
                        $estadoClase .= ' suspended';
                    } else {
                        $estadoClase .= ' inactive';
                    }
                    ?>
                    <span class="<?php echo $estadoClase; ?>">
                        <?php echo htmlspecialchars($usuario['estado']); ?>
                    </span>

                    <?php if (($usuario['tipo_admin'] ?? '') === 'superadmin'): ?>
                        <span class="mini-role">Superadmin</span>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <div class="detail-grid">
            <section class="superadmin-panel-card">
                <div class="panel-card-head">
                    <div>
                        <h2>Información general</h2>
                        <p>Datos base del acceso al sistema</p>
                    </div>
                </div>

                <div class="detail-list">
                    <div class="detail-item">
                        <span>ID usuario</span>
                        <strong><?php echo (int) $usuario['id_usuario']; ?></strong>
                    </div>
                    <div class="detail-item">
                        <span>Correo</span>
                        <strong><?php echo htmlspecialchars($usuario['correo_electronico']); ?></strong>
                    </div>
                    <div class="detail-item">
                        <span>Rol</span>
                        <strong><?php echo htmlspecialchars($usuario['rol']); ?></strong>
                    </div>
                    <div class="detail-item">
                        <span>Estado</span>
                        <strong><?php echo htmlspecialchars($usuario['estado']); ?></strong>
                    </div>
                    <div class="detail-item">
                        <span>Fecha de registro</span>
                        <strong><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($usuario['fecha_registro']))); ?></strong>
                    </div>
                </div>
            </section>

            <section class="superadmin-panel-card">
                <div class="panel-card-head">
                    <div>
                        <h2>Acciones rápidas</h2>
                        <p>Control del estado del usuario</p>
                    </div>
                </div>

                <div class="detail-actions">
                    <?php if (!$esYo && !$esSuperadminObjetivo): ?>
                        <?php if (($usuario['estado'] ?? '') === 'suspendido'): ?>
                            <form action="../../procesos/cambiar_estado_usuario.php" method="POST">
                                <input type="hidden" name="id_usuario" value="<?php echo (int) $usuario['id_usuario']; ?>">
                                <input type="hidden" name="nuevo_estado" value="activo">
                                <input type="hidden" name="redirect" value="../superadmin/usuarios/detalle_usuario_superadmin.php?id=<?php echo (int) $usuario['id_usuario']; ?>">
                                <button type="submit" class="btn btn-primary">Reactivar usuario</button>
                            </form>
                        <?php else: ?>
                            <form action="../../procesos/cambiar_estado_usuario.php" method="POST">
                                <input type="hidden" name="id_usuario" value="<?php echo (int) $usuario['id_usuario']; ?>">
                                <input type="hidden" name="nuevo_estado" value="suspendido">
                                <input type="hidden" name="redirect" value="../superadmin/usuarios/detalle_usuario_superadmin.php?id=<?php echo (int) $usuario['id_usuario']; ?>">
                                <button type="submit" class="btn btn-reject">Suspender usuario</button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="action-disabled">Este perfil está protegido.</div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <?php if (($usuario['rol'] ?? '') === 'estudiante'): ?>
            <section class="superadmin-panel-card full-card">
                <div class="panel-card-head">
                    <div>
                        <h2>Datos de estudiante</h2>
                        <p>Información académica y personal registrada</p>
                    </div>
                </div>

                <div class="detail-grid-inner">
                    <div class="detail-item"><span>Nombre</span><strong><?php echo htmlspecialchars(trim(($usuario['est_nombre'] ?? '') . ' ' . ($usuario['est_apellido_paterno'] ?? '') . ' ' . ($usuario['est_apellido_materno'] ?? ''))); ?></strong></div>
                    <div class="detail-item"><span>Carrera</span><strong><?php echo htmlspecialchars($usuario['carrera'] ?? 'No registrada'); ?></strong></div>
                    <div class="detail-item"><span>No. control</span><strong><?php echo htmlspecialchars($usuario['no_control'] ?? 'No registrado'); ?></strong></div>
                    <div class="detail-item"><span>Semestre</span><strong><?php echo htmlspecialchars((string) ($usuario['semestre'] ?? 'No registrado')); ?></strong></div>
                    <div class="detail-item"><span>CURP</span><strong><?php echo htmlspecialchars($usuario['curp'] ?? 'No registrada'); ?></strong></div>
                    <div class="detail-item"><span>Teléfono</span><strong><?php echo htmlspecialchars($usuario['telefono'] ?? 'No registrado'); ?></strong></div>
                </div>
            </section>
        <?php endif; ?>

        <?php if (($usuario['rol'] ?? '') === 'organizacion'): ?>
            <section class="superadmin-panel-card full-card">
                <div class="panel-card-head">
                    <div>
                        <h2>Datos de organización</h2>
                        <p>Información empresarial registrada en la plataforma</p>
                    </div>
                </div>

                <div class="detail-grid-inner">
                    <div class="detail-item"><span>Empresa</span><strong><?php echo htmlspecialchars($usuario['nombre_empresa'] ?? 'No registrada'); ?></strong></div>
                    <div class="detail-item"><span>RFC</span><strong><?php echo htmlspecialchars($usuario['rfc'] ?? 'No registrado'); ?></strong></div>
                    <div class="detail-item"><span>Sector</span><strong><?php echo htmlspecialchars($usuario['sector'] ?? 'No registrado'); ?></strong></div>
                    <div class="detail-item"><span>Representante</span><strong><?php echo htmlspecialchars($usuario['representante'] ?? 'No registrado'); ?></strong></div>
                    <div class="detail-item"><span>Teléfono</span><strong><?php echo htmlspecialchars($usuario['telefono_contacto'] ?? 'No registrado'); ?></strong></div>
                </div>
            </section>
        <?php endif; ?>

        <?php if (($usuario['rol'] ?? '') === 'administrador'): ?>
            <section class="superadmin-panel-card full-card">
                <div class="panel-card-head">
                    <div>
                        <h2>Datos de administrador</h2>
                        <p>Información del perfil administrativo y autorización</p>
                    </div>
                </div>

                <div class="detail-grid-inner">
                    <div class="detail-item"><span>Nombre</span><strong><?php echo htmlspecialchars(trim(($usuario['adm_nombre'] ?? '') . ' ' . ($usuario['adm_apellido_paterno'] ?? '') . ' ' . ($usuario['adm_apellido_materno'] ?? ''))); ?></strong></div>
                    <div class="detail-item"><span>Puesto</span><strong><?php echo htmlspecialchars($usuario['puesto'] ?? 'No registrado'); ?></strong></div>
                    <div class="detail-item"><span>Departamento</span><strong><?php echo htmlspecialchars($usuario['departamento'] ?? 'No registrado'); ?></strong></div>
                    <div class="detail-item"><span>Tipo admin</span><strong><?php echo htmlspecialchars($usuario['tipo_admin'] ?? 'admin'); ?></strong></div>
                    <div class="detail-item"><span>Estado solicitud</span><strong><?php echo htmlspecialchars($usuario['estado_solicitud'] ?? 'No registrado'); ?></strong></div>
                    <div class="detail-item"><span>Fecha autorización</span><strong><?php echo !empty($usuario['fecha_autorizacion']) ? htmlspecialchars(date('Y-m-d H:i', strtotime($usuario['fecha_autorizacion']))) : 'No disponible'; ?></strong></div>
                </div>
            </section>
        <?php endif; ?>

        <section class="superadmin-panel-card full-card">
            <div class="panel-card-head">
                <div>
                    <h2>Dirección</h2>
                    <p>Ubicación registrada en el sistema</p>
                </div>
            </div>

            <div class="detail-grid-inner">
                <div class="detail-item"><span>País</span><strong><?php echo htmlspecialchars($usuario['pais'] ?? 'No registrado'); ?></strong></div>
                <div class="detail-item"><span>Estado</span><strong><?php echo htmlspecialchars($usuario['dir_estado'] ?? 'No registrado'); ?></strong></div>
                <div class="detail-item"><span>Ciudad</span><strong><?php echo htmlspecialchars($usuario['ciudad'] ?? 'No registrado'); ?></strong></div>
                <div class="detail-item"><span>Colonia</span><strong><?php echo htmlspecialchars($usuario['colonia'] ?? 'No registrado'); ?></strong></div>
                <div class="detail-item"><span>Código postal</span><strong><?php echo htmlspecialchars($usuario['codigo_postal'] ?? 'No registrado'); ?></strong></div>
                <div class="detail-item"><span>Calle</span><strong><?php echo htmlspecialchars($usuario['calle'] ?? 'No registrada'); ?></strong></div>
                <div class="detail-item"><span>Número exterior</span><strong><?php echo htmlspecialchars($usuario['num_exterior'] ?? 'No registrado'); ?></strong></div>
            </div>
        </section>
    </section>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../../assets/js/main.js"></script>
</body>
</html>
