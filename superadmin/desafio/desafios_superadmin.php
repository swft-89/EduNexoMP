<?php
require_once __DIR__ . '/../../includes/session_superadmin.php';
require_once __DIR__ . '/../../config/conexion.php';

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

$busqueda = trim($_GET['q'] ?? '');
$filtroEstado = trim($_GET['estado'] ?? '');

$estadosPermitidos = ['activo', 'pausado', 'cerrado', 'borrador'];

$params = [];
$where = [];

if ($busqueda !== '') {
    $where[] = "(
        d.titulo ILIKE :busqueda
        OR o.nombre_empresa ILIKE :busqueda
        OR COALESCE(c.nombre_categoria, '') ILIKE :busqueda
    )";
    $params[':busqueda'] = '%' . $busqueda . '%';
}

if (in_array($filtroEstado, $estadosPermitidos, true)) {
    $where[] = "LOWER(COALESCE(d.estado, '')) = :estado";
    $params[':estado'] = $filtroEstado;
}

$sql = "
    SELECT
        d.id_desafio,
        d.titulo,
        d.descripcion,
        d.fecha_publicacion,
        d.fecha_limite,
        d.estado,
        d.modalidad,
        o.nombre_empresa,
        c.nombre_categoria,
        (
            SELECT COUNT(*)
            FROM propuesta p
            WHERE p.id_desafio = d.id_desafio
        ) AS total_propuestas
    FROM desafio d
    INNER JOIN organizacion o ON o.id_organizacion = d.id_organizacion
    LEFT JOIN categoria c ON c.id_categoria = d.id_categoria
";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY d.fecha_publicacion DESC";

$stmtDesafios = $pdo->prepare($sql);
$stmtDesafios->execute($params);
$desafios = $stmtDesafios->fetchAll(PDO::FETCH_ASSOC);

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desafíos | Superadmin - EduNexo MP</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/dark.css?v=dark-fix-2">
    <link rel="stylesheet" href="../../assets/css/superadmin/dashboard_superadmin.css">
    <link rel="stylesheet" href="../../assets/css/superadmin/superadmin_sections.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/../../includes/sidebar_admin.php'; ?>

    <section class="app-content superadmin-content">
        <?php include __DIR__ . '/../../includes/app_topbar.php'; ?>

        <div class="superadmin-header">
            <div>
                <h1>Gestión de desafíos</h1>
                <p>Consulta, busca y supervisa los desafíos publicados por las organizaciones.</p>
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
                    <p>Encuentra desafíos por título, empresa, categoría o estado</p>
                </div>
            </div>

            <form method="GET" action="desafios_superadmin.php" class="filters-form">
                <div class="filter-group search-box">
                    <label for="q">Buscar</label>
                    <input
                        type="text"
                        id="q"
                        name="q"
                        placeholder="Título, empresa o categoría"
                        value="<?php echo htmlspecialchars($busqueda); ?>"
                    >
                </div>

                <div class="filter-group">
                    <label for="estado">Estado</label>
                    <select name="estado" id="estado">
                        <option value="">Todos</option>
                        <option value="activo" <?php echo $filtroEstado === 'activo' ? 'selected' : ''; ?>>Activo</option>
                        <option value="pausado" <?php echo $filtroEstado === 'pausado' ? 'selected' : ''; ?>>Pausado</option>
                        <option value="cerrado" <?php echo $filtroEstado === 'cerrado' ? 'selected' : ''; ?>>Cerrado</option>
                        <option value="borrador" <?php echo $filtroEstado === 'borrador' ? 'selected' : ''; ?>>Borrador</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary btn-sm">Aplicar</button>
                    <a href="desafios_superadmin.php" class="btn superadmin-btn-light btn-sm">Limpiar</a>
                </div>
            </form>
        </section>

        <section class="superadmin-panel-card full-card">
            <div class="panel-card-head">
                <div>
                    <h2>Desafíos registrados</h2>
                    <p>Listado general de desafíos en la plataforma</p>
                </div>
            </div>

            <div class="activity-table-wrap">
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Organización</th>
                            <th>Categoría</th>
                            <th>Estado</th>
                            <th>Fecha límite</th>
                            <th>Propuestas</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($desafios)): ?>
                            <?php foreach ($desafios as $desafio): ?>
                                <tr>
                                    <td><?php echo (int) $desafio['id_desafio']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($desafio['titulo']); ?></strong>
                                        <div class="row-subtext">
                                            <?php echo htmlspecialchars($desafio['modalidad'] ?: 'Sin modalidad'); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($desafio['nombre_empresa']); ?></td>
                                    <td><?php echo htmlspecialchars($desafio['nombre_categoria'] ?: 'Sin categoría'); ?></td>
                                    <td>
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
                                    </td>
                                    <td>
                                        <?php echo !empty($desafio['fecha_limite']) ? htmlspecialchars(date('Y-m-d', strtotime($desafio['fecha_limite']))) : 'Sin fecha'; ?>
                                    </td>
                                    <td><?php echo (int) $desafio['total_propuestas']; ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="detalle_desafio_superadmin.php?id=<?php echo (int) $desafio['id_desafio']; ?>" class="btn superadmin-btn-light btn-sm">
                                                Ver
                                            </a>

                                            <?php if ($estado !== 'activo'): ?>
                                                <form action="../../procesos/cambiar_estado_desafio_admin.php" method="POST">
                                                    <?php echo edunexo_csrf_input(); ?>
                                                    <input type="hidden" name="id_desafio" value="<?php echo (int) $desafio['id_desafio']; ?>">
                                                    <input type="hidden" name="nuevo_estado" value="activo">
                                                    <button type="submit" class="btn btn-primary btn-sm">Activar</button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($estado !== 'pausado'): ?>
                                                <form action="../../procesos/cambiar_estado_desafio_admin.php" method="POST">
                                                    <?php echo edunexo_csrf_input(); ?>
                                                    <input type="hidden" name="id_desafio" value="<?php echo (int) $desafio['id_desafio']; ?>">
                                                    <input type="hidden" name="nuevo_estado" value="pausado">
                                                    <button type="submit" class="btn superadmin-btn-light btn-sm">Pausar</button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($estado !== 'cerrado'): ?>
                                                <form action="../../procesos/cambiar_estado_desafio_admin.php" method="POST">
                                                    <?php echo edunexo_csrf_input(); ?>
                                                    <input type="hidden" name="id_desafio" value="<?php echo (int) $desafio['id_desafio']; ?>">
                                                    <input type="hidden" name="nuevo_estado" value="cerrado">
                                                    <button type="submit" class="btn btn-reject btn-sm">Cerrar</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">No se encontraron desafíos con esos filtros.</td>
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
