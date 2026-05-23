<?php
require_once __DIR__ . '/../includes/session_admin.php';
require_once __DIR__ . '/../config/conexion.php';

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

$whereSql = !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '';

$stmtResumen = $pdo->query("
    SELECT
        COUNT(*) AS total,
        COUNT(*) FILTER (WHERE LOWER(COALESCE(estado, '')) = 'activo') AS activos,
        COUNT(*) FILTER (WHERE rol = 'estudiante') AS estudiantes,
        COUNT(*) FILTER (WHERE rol = 'organizacion') AS organizaciones
    FROM usuario
");
$resumen = $stmtResumen->fetch(PDO::FETCH_ASSOC) ?: [];

$sql = "
    SELECT
        u.id_usuario,
        u.correo_electronico,
        u.rol,
        u.estado,
        u.fecha_registro,
        COALESCE(
            NULLIF(CONCAT_WS(' ', e.nombre, e.apellido_paterno, e.apellido_materno), ''),
            o.nombre_empresa,
            NULLIF(CONCAT_WS(' ', a.nombre, a.apellido_paterno, a.apellido_materno), ''),
            u.correo_electronico
        ) AS nombre_mostrar,
        COALESCE(a.tipo_admin, '') AS tipo_admin
    FROM usuario u
    LEFT JOIN estudiante e ON e.id_estudiante = u.id_usuario
    LEFT JOIN organizacion o ON o.id_organizacion = u.id_usuario
    LEFT JOIN administrador a ON a.id_admin = u.id_usuario
    {$whereSql}
    ORDER BY u.fecha_registro DESC
    LIMIT 80
";
$stmtUsuarios = $pdo->prepare($sql);
$stmtUsuarios->execute($params);
$usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

function admin_page_status_class(?string $estado): string
{
    $estado = strtolower(trim((string) $estado));

    if (in_array($estado, ['activo', 'aceptada', 'aprobada'], true)) {
        return 'is-ok';
    }

    if (in_array($estado, ['pendiente', 'en revision', 'en revision', 'borrador'], true)) {
        return 'is-waiting';
    }

    if (in_array($estado, ['suspendido', 'inactivo', 'cerrado', 'rechazada', 'rechazado'], true)) {
        return 'is-danger';
    }

    return 'is-neutral';
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
    <title>Usuarios | Admin - EduNexo MP</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin/dashboard_admin.css?v=admin-dashboard-3">
    <link rel="stylesheet" href="../assets/css/dark.css?v=dark-fix-6">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <main class="app-content admin-content">
        <?php include __DIR__ . '/../includes/app_topbar.php'; ?>

        <section class="admin-page-head">
            <div>
                <span>Operacion admin</span>
                <h1>Usuarios</h1>
                <p>Consulta cuentas registradas, filtra por rol y detecta estados que necesitan seguimiento.</p>
            </div>
            <a href="dashboard_admin.php" class="btn btn-nav">
                <i class="bi bi-arrow-left"></i>
                Volver
            </a>
        </section>

        <?php if ($success): ?>
            <script>window.edunexoSuccess = <?php echo json_encode($success); ?>;</script>
        <?php endif; ?>

        <?php if ($error): ?>
            <script>window.edunexoError = <?php echo json_encode($error); ?>;</script>
        <?php endif; ?>

        <section class="admin-mini-summary">
            <article>
                <span>Total usuarios</span>
                <strong><?php echo (int) ($resumen['total'] ?? 0); ?></strong>
            </article>
            <article>
                <span>Activos</span>
                <strong><?php echo (int) ($resumen['activos'] ?? 0); ?></strong>
            </article>
            <article>
                <span>Estudiantes</span>
                <strong><?php echo (int) ($resumen['estudiantes'] ?? 0); ?></strong>
            </article>
            <article>
                <span>Organizaciones</span>
                <strong><?php echo (int) ($resumen['organizaciones'] ?? 0); ?></strong>
            </article>
        </section>

        <section class="admin-filter-card">
            <form method="GET" action="usuarios_admin.php" class="admin-filter-form">
                <label class="admin-field admin-field-wide" for="q">
                    <span>Buscar</span>
                    <input type="text" id="q" name="q" placeholder="Nombre, correo o empresa" value="<?php echo htmlspecialchars($busqueda); ?>">
                </label>

                <label class="admin-field" for="rol">
                    <span>Rol</span>
                    <select id="rol" name="rol">
                        <option value="">Todos</option>
                        <option value="estudiante" <?php echo $filtroRol === 'estudiante' ? 'selected' : ''; ?>>Estudiante</option>
                        <option value="organizacion" <?php echo $filtroRol === 'organizacion' ? 'selected' : ''; ?>>Organizacion</option>
                        <option value="administrador" <?php echo $filtroRol === 'administrador' ? 'selected' : ''; ?>>Administrador</option>
                    </select>
                </label>

                <label class="admin-field" for="estado">
                    <span>Estado</span>
                    <select id="estado" name="estado">
                        <option value="">Todos</option>
                        <option value="activo" <?php echo $filtroEstado === 'activo' ? 'selected' : ''; ?>>Activo</option>
                        <option value="inactivo" <?php echo $filtroEstado === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                        <option value="suspendido" <?php echo $filtroEstado === 'suspendido' ? 'selected' : ''; ?>>Suspendido</option>
                    </select>
                </label>

                <div class="admin-filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel"></i>
                        Filtrar
                    </button>
                    <a href="usuarios_admin.php" class="btn btn-nav">Limpiar</a>
                </div>
            </form>
        </section>

        <section class="admin-table-card">
            <div class="admin-panel-head">
                <div>
                    <h2>Listado de usuarios</h2>
                    <p>Ultimos 80 resultados segun los filtros activos.</p>
                </div>
            </div>

            <div class="admin-table-wrap">
                <table class="admin-data-table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Correo</th>
                            <th>Registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($usuarios)): ?>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td>
                                        <div class="admin-user-cell">
                                            <span class="admin-row-icon"><?php echo htmlspecialchars(strtoupper(substr($usuario['nombre_mostrar'] ?? 'U', 0, 1))); ?></span>
                                            <div>
                                                <strong><?php echo htmlspecialchars($usuario['nombre_mostrar'] ?: 'Sin nombre'); ?></strong>
                                                <?php if (($usuario['tipo_admin'] ?? '') === 'superadmin'): ?>
                                                    <small>Superadmin</small>
                                                <?php else: ?>
                                                    <small>ID <?php echo (int) $usuario['id_usuario']; ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="admin-chip"><?php echo htmlspecialchars(ucfirst($usuario['rol'] ?? 'Sin rol')); ?></span></td>
                                    <td>
                                        <span class="admin-status <?php echo admin_page_status_class($usuario['estado'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($usuario['estado'] ?: 'Sin estado'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($usuario['correo_electronico'] ?? 'Sin correo'); ?></td>
                                    <td>
                                        <?php echo !empty($usuario['fecha_registro']) ? htmlspecialchars(date('d/m/Y', strtotime($usuario['fecha_registro']))) : 'Sin fecha'; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="admin-empty-row">No se encontraron usuarios con esos filtros.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
