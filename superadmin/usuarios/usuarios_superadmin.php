<?php
require_once __DIR__ . '/../../includes/session_superadmin.php';
require_once __DIR__ . '/../../config/conexion.php';

$stmtAdmin = $pdo->prepare("
    SELECT nombre, apellido_paterno, apellido_materno
    FROM administrador
    WHERE id_admin = :id_admin
    LIMIT 1
");
$stmtAdmin->execute([
    ':id_admin' => $_SESSION['usuario_id']
]);
$admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

$inicialAdmin = strtoupper(substr($admin['nombre'] ?? 'U', 0, 1));

$busqueda = trim($_GET['q'] ?? '');
$filtroRol = trim($_GET['rol'] ?? '');
$filtroEstado = trim($_GET['estado'] ?? '');

$rolesPermitidos = ['estudiante', 'organizacion', 'administrador'];
$estadosPermitidos = ['activo', 'inactivo', 'suspendido'];

$params = [];
$where = [];

if ($busqueda !== '') {
    $where[] = "(
        u.correo_electronico ILIKE :busqueda
        OR COALESCE(e.nombre, '') ILIKE :busqueda
        OR COALESCE(e.apellido_paterno, '') ILIKE :busqueda
        OR COALESCE(e.apellido_materno, '') ILIKE :busqueda
        OR COALESCE(o.nombre_empresa, '') ILIKE :busqueda
        OR COALESCE(a.nombre, '') ILIKE :busqueda
        OR COALESCE(a.apellido_paterno, '') ILIKE :busqueda
        OR COALESCE(a.apellido_materno, '') ILIKE :busqueda
    )";
    $params[':busqueda'] = '%' . $busqueda . '%';
}

if (in_array($filtroRol, $rolesPermitidos, true)) {
    $where[] = "u.rol = :rol";
    $params[':rol'] = $filtroRol;
}

if (in_array($filtroEstado, $estadosPermitidos, true)) {
    $where[] = "u.estado = :estado";
    $params[':estado'] = $filtroEstado;
}

$sql = "
    SELECT
        u.id_usuario,
        u.correo_electronico,
        u.rol,
        u.estado,
        u.fecha_registro,
        CASE
            WHEN u.rol = 'estudiante' THEN CONCAT_WS(' ', e.nombre, e.apellido_paterno, e.apellido_materno)
            WHEN u.rol = 'organizacion' THEN o.nombre_empresa
            WHEN u.rol = 'administrador' THEN CONCAT_WS(' ', a.nombre, a.apellido_paterno, a.apellido_materno)
            ELSE u.correo_electronico
        END AS nombre_mostrar,
        COALESCE(a.tipo_admin, '') AS tipo_admin
    FROM usuario u
    LEFT JOIN estudiante e ON e.id_estudiante = u.id_usuario
    LEFT JOIN organizacion o ON o.id_organizacion = u.id_usuario
    LEFT JOIN administrador a ON a.id_admin = u.id_usuario
";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY u.fecha_registro DESC";

$stmtUsuarios = $pdo->prepare($sql);
$stmtUsuarios->execute($params);
$usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios | Superadmin - EduNexo MP</title>
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
                <h1>Gestión de usuarios</h1>
                <p>Consulta, busca y filtra cuentas registradas dentro de EduNexo MP.</p>
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
            <div class="panel-card-head">
                <div>
                    <h2>Buscador y filtros</h2>
                    <p>Encuentra usuarios por nombre, correo, rol o estado</p>
                </div>
            </div>

            <form method="GET" action="usuarios_superadmin.php" class="filters-form">
                <div class="filter-group search-box">
                    <label for="q">Buscar</label>
                    <input
                        type="text"
                        id="q"
                        name="q"
                        placeholder="Nombre o correo"
                        value="<?php echo htmlspecialchars($busqueda); ?>"
                    >
                </div>

                <div class="filter-group">
                    <label for="rol">Rol</label>
                    <select name="rol" id="rol">
                        <option value="">Todos</option>
                        <option value="estudiante" <?php echo $filtroRol === 'estudiante' ? 'selected' : ''; ?>>Estudiante</option>
                        <option value="organizacion" <?php echo $filtroRol === 'organizacion' ? 'selected' : ''; ?>>Organización</option>
                        <option value="administrador" <?php echo $filtroRol === 'administrador' ? 'selected' : ''; ?>>Administrador</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="estado">Estado</label>
                    <select name="estado" id="estado">
                        <option value="">Todos</option>
                        <option value="activo" <?php echo $filtroEstado === 'activo' ? 'selected' : ''; ?>>Activo</option>
                        <option value="inactivo" <?php echo $filtroEstado === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                        <option value="suspendido" <?php echo $filtroEstado === 'suspendido' ? 'selected' : ''; ?>>Suspendido</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary btn-sm">Aplicar</button>
                    <a href="usuarios_superadmin.php" class="btn superadmin-btn-light btn-sm">Limpiar</a>
                </div>
            </form>
        </section>

        <section class="superadmin-panel-card full-card">
            <div class="panel-card-head">
                <div>
                    <h2>Usuarios registrados</h2>
                    <p>Listado general de cuentas del sistema</p>
                </div>
            </div>

            <div class="activity-table-wrap">
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($usuarios)): ?>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo (int) $usuario['id_usuario']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($usuario['nombre_mostrar'] ?: 'Sin nombre'); ?>
                                        <?php if (($usuario['tipo_admin'] ?? '') === 'superadmin'): ?>
                                            <span class="mini-role">Superadmin</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($usuario['correo_electronico']); ?></td>
                                    <td>
                                        <span class="mini-role">
                                            <?php echo htmlspecialchars($usuario['rol']); ?>
                                        </span>
                                    </td>
                                    <td>
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
                                    </td>
                                    <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($usuario['fecha_registro']))); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <?php
                                            $esSuperadminObjetivo = (($usuario['tipo_admin'] ?? '') === 'superadmin');
                                            $esYo = ((int) $usuario['id_usuario'] === (int) $_SESSION['usuario_id']);
                                            ?>

                                            <a href="detalle_usuario_superadmin.php?id=<?php echo (int) $usuario['id_usuario']; ?>" class="btn superadmin-btn-light btn-sm">Ver</a>

                                            <?php if (!$esSuperadminObjetivo && !$esYo): ?>
                                                <?php if (($usuario['estado'] ?? '') === 'suspendido'): ?>
                                                    <form action="../../procesos/cambiar_estado_usuario.php" method="POST">
                                                        <input type="hidden" name="id_usuario" value="<?php echo (int) $usuario['id_usuario']; ?>">
                                                        <input type="hidden" name="nuevo_estado" value="activo">
                                                        <input type="hidden" name="redirect" value="../superadmin/usuarios/usuarios_superadmin.php">
                                                        <button type="submit" class="btn btn-primary btn-sm">Reactivar</button>
                                                    </form>
                                                <?php else: ?>
                                                    <form action="../../procesos/cambiar_estado_usuario.php" method="POST">
                                                        <input type="hidden" name="id_usuario" value="<?php echo (int) $usuario['id_usuario']; ?>">
                                                        <input type="hidden" name="nuevo_estado" value="suspendido">
                                                        <input type="hidden" name="redirect" value="../superadmin/usuarios/usuarios_superadmin.php">
                                                        <button type="submit" class="btn btn-reject btn-sm">Suspender</button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="action-disabled">Protegido</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">No se encontraron usuarios con esos filtros.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../../assets/js/main.js"></script>
</body>
</html>
