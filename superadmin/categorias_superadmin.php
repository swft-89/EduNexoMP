<?php
require_once __DIR__ . '../../includes/session_superadmin.php';
require_once __DIR__ . '../../config/conexion.php';

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

$busquedaCategoria = trim($_GET['q_categoria'] ?? '');
$busquedaHabilidad = trim($_GET['q_habilidad'] ?? '');

$paramsCategorias = [];
$sqlCategorias = "
    SELECT
        c.id_categoria,
        c.nombre_categoria,
        c.descripcion_categoria,
        (
            SELECT COUNT(*)
            FROM desafio d
            WHERE d.id_categoria = c.id_categoria
        ) AS total_desafios
    FROM categoria c
";

if ($busquedaCategoria !== '') {
    $sqlCategorias .= " WHERE c.nombre_categoria ILIKE :busqueda OR COALESCE(c.descripcion_categoria, '') ILIKE :busqueda";
    $paramsCategorias[':busqueda'] = '%' . $busquedaCategoria . '%';
}

$sqlCategorias .= " ORDER BY c.nombre_categoria ASC";

$stmtCategorias = $pdo->prepare($sqlCategorias);
$stmtCategorias->execute($paramsCategorias);
$categorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);

$paramsHabilidades = [];
$sqlHabilidades = "
    SELECT
        h.id_habilidad,
        h.nombre,
        h.categoria_habilidad,
        (
            SELECT COUNT(*)
            FROM estudiante_habilidad eh
            WHERE eh.id_habilidad = h.id_habilidad
        ) AS total_estudiantes,
        (
            SELECT COUNT(*)
            FROM desafio_habilidad dh
            WHERE dh.id_habilidad = h.id_habilidad
        ) AS total_desafios
    FROM habilidad h
";

if ($busquedaHabilidad !== '') {
    $sqlHabilidades .= " WHERE h.nombre ILIKE :busqueda_habilidad OR COALESCE(h.categoria_habilidad, '') ILIKE :busqueda_habilidad";
    $paramsHabilidades[':busqueda_habilidad'] = '%' . $busquedaHabilidad . '%';
}

$sqlHabilidades .= " ORDER BY h.nombre ASC";

$stmtHabilidades = $pdo->prepare($sqlHabilidades);
$stmtHabilidades->execute($paramsHabilidades);
$habilidades = $stmtHabilidades->fetchAll(PDO::FETCH_ASSOC);

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
$oldCategoria = $_SESSION['old_categoria'] ?? [];
$oldHabilidad = $_SESSION['old_habilidad'] ?? [];

unset(
    $_SESSION['success'],
    $_SESSION['error'],
    $_SESSION['old_categoria'],
    $_SESSION['old_habilidad']
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogos globales | Superadmin - EduNexo MP</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dark.css?v=dark-fix-2">
    <link rel="stylesheet" href="../assets/css/superadmin/dashboard_superadmin.css">
    <link rel="stylesheet" href="../assets/css/superadmin/superadmin_sections.css">
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
            <a href="dashboard_superadmin.php" class="active">
                <i class="bi bi-bar-chart-line"></i> Dashboard
            </a>
            <a href="usuarios/usuarios_superadmin.php">
                <i class="bi bi-people"></i> Usuarios
            </a>
            <a href="usuarios/solicitudes_admin.php">
                <i class="bi bi-person-check"></i> Solicitudes admin
            </a>
            <a href="reportes_superadmin.php">
                <i class="bi bi-clipboard-data"></i> Reportes
            </a>
            <a href="desafios/desafios_superadmin.php">
                <i class="bi bi-file-earmark-text"></i> Desafíos
            </a>
            <a href="propuestas/propuestas_superadmin.php">
                <i class="bi bi-send"></i> Propuestas
            </a>
            <a href="categorias_superadmin.php">
                <i class="bi bi-tags"></i> Categorías
            </a>
        </nav>
    </aside>

    <section class="app-content superadmin-content">
        <?php include __DIR__ . '/../includes/app_topbar.php'; ?>

        <div class="superadmin-header">
            <div>
                <h1>Catálogos globales</h1>
                <p>Administra categorías y habilidades disponibles en toda la plataforma.</p>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="superadmin-alert success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="superadmin-alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <section class="superadmin-panel-card full-card">
            <div class="panel-card-head">
                <div>
                    <h2>Categorías</h2>
                    <p>Clasificaciones generales para los desafíos publicados</p>
                </div>
            </div>

            <div class="detail-grid">
                <section class="superadmin-panel-card inner-card">
                    <div class="panel-card-head">
                        <div>
                            <h2>Nueva categoría</h2>
                            <p>Crea una categoría global</p>
                        </div>
                    </div>

                    <form action="../procesos/guardar_categoria.php" method="POST" class="stack-form">
                        <div class="filter-group">
                            <label for="nombre_categoria">Nombre</label>
                            <input
                                type="text"
                                id="nombre_categoria"
                                name="nombre_categoria"
                                maxlength="100"
                                value="<?php echo htmlspecialchars($oldCategoria['nombre_categoria'] ?? ''); ?>"
                                required
                            >
                        </div>

                        <div class="filter-group">
                            <label for="descripcion_categoria">Descripción</label>
                            <textarea
                                id="descripcion_categoria"
                                name="descripcion_categoria"
                                rows="5"
                            ><?php echo htmlspecialchars($oldCategoria['descripcion_categoria'] ?? ''); ?></textarea>
                        </div>

                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">Guardar categoría</button>
                        </div>
                    </form>
                </section>

                <section class="superadmin-panel-card inner-card">
                    <div class="panel-card-head">
                        <div>
                            <h2>Buscar categorías</h2>
                            <p>Filtra por nombre o descripción</p>
                        </div>
                    </div>

                    <form method="GET" action="categorias_superadmin.php" class="stack-form">
                        <div class="filter-group">
                            <label for="q_categoria">Buscar</label>
                            <input
                                type="text"
                                id="q_categoria"
                                name="q_categoria"
                                placeholder="Nombre o descripción"
                                value="<?php echo htmlspecialchars($busquedaCategoria); ?>"
                            >
                        </div>

                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">Aplicar</button>
                            <a href="categorias_superadmin.php" class="btn superadmin-btn-light">Limpiar</a>
                        </div>
                    </form>
                </section>
            </div>

            <div class="activity-table-wrap top-space">
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Desafíos asociados</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($categorias)): ?>
                            <?php foreach ($categorias as $categoria): ?>
                                <tr>
                                    <td><?php echo (int) $categoria['id_categoria']; ?></td>
                                    <td><?php echo htmlspecialchars($categoria['nombre_categoria']); ?></td>
                                    <td><?php echo htmlspecialchars($categoria['descripcion_categoria'] ?: 'Sin descripción'); ?></td>
                                    <td><?php echo (int) $categoria['total_desafios']; ?></td>
                                    <td>
                                        <div class="table-actions table-actions-inline">
                                            <button
                                                type="button"
                                                class="btn superadmin-btn-light btn-sm"
                                                onclick="abrirModalEditarCategoria(
                                                    '<?php echo (int) $categoria['id_categoria']; ?>',
                                                    '<?php echo htmlspecialchars(addslashes($categoria['nombre_categoria'])); ?>',
                                                    '<?php echo htmlspecialchars(addslashes($categoria['descripcion_categoria'] ?? '')); ?>'
                                                )"
                                            >
                                                Editar
                                            </button>

                                            <form action="../procesos/eliminar_categoria.php" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar esta categoría?');">
                                                <input type="hidden" name="id_categoria" value="<?php echo (int) $categoria['id_categoria']; ?>">
                                                <button type="submit" class="btn btn-reject btn-sm">Eliminar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">No se encontraron categorías.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="superadmin-panel-card full-card">
            <div class="panel-card-head">
                <div>
                    <h2>Habilidades</h2>
                    <p>Catálogo global de habilidades utilizadas en perfiles y desafíos</p>
                </div>
            </div>

            <div class="detail-grid">
                <section class="superadmin-panel-card inner-card">
                    <div class="panel-card-head">
                        <div>
                            <h2>Nueva habilidad</h2>
                            <p>Agrega una habilidad global</p>
                        </div>
                    </div>

                    <form action="../procesos/guardar_habilidad.php" method="POST" class="stack-form">
                        <div class="filter-group">
                            <label for="nombre_habilidad">Nombre</label>
                            <input
                                type="text"
                                id="nombre_habilidad"
                                name="nombre"
                                maxlength="100"
                                value="<?php echo htmlspecialchars($oldHabilidad['nombre'] ?? ''); ?>"
                                required
                            >
                        </div>

                        <div class="filter-group">
                            <label for="categoria_habilidad">Categoría de habilidad</label>
                            <input
                                type="text"
                                id="categoria_habilidad"
                                name="categoria_habilidad"
                                maxlength="100"
                                placeholder="Ej. Desarrollo, Diseño, Datos"
                                value="<?php echo htmlspecialchars($oldHabilidad['categoria_habilidad'] ?? ''); ?>"
                            >
                        </div>

                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">Guardar habilidad</button>
                        </div>
                    </form>
                </section>

                <section class="superadmin-panel-card inner-card">
                    <div class="panel-card-head">
                        <div>
                            <h2>Buscar habilidades</h2>
                            <p>Filtra por nombre o categoría</p>
                        </div>
                    </div>

                    <form method="GET" action="categorias_superadmin.php" class="stack-form">
                        <div class="filter-group">
                            <label for="q_habilidad">Buscar</label>
                            <input
                                type="text"
                                id="q_habilidad"
                                name="q_habilidad"
                                placeholder="Nombre o categoría"
                                value="<?php echo htmlspecialchars($busquedaHabilidad); ?>"
                            >
                        </div>

                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">Aplicar</button>
                            <a href="categorias_superadmin.php" class="btn superadmin-btn-light">Limpiar</a>
                        </div>
                    </form>
                </section>
            </div>

            <div class="activity-table-wrap top-space">
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>En estudiantes</th>
                            <th>En desafíos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($habilidades)): ?>
                            <?php foreach ($habilidades as $habilidad): ?>
                                <tr>
                                    <td><?php echo (int) $habilidad['id_habilidad']; ?></td>
                                    <td><?php echo htmlspecialchars($habilidad['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($habilidad['categoria_habilidad'] ?: 'Sin categoría'); ?></td>
                                    <td><?php echo (int) $habilidad['total_estudiantes']; ?></td>
                                    <td><?php echo (int) $habilidad['total_desafios']; ?></td>
                                    <td>
                                        <div class="table-actions table-actions-inline">
                                            <button
                                                type="button"
                                                class="btn superadmin-btn-light btn-sm"
                                                onclick="abrirModalEditarHabilidad(
                                                    '<?php echo (int) $habilidad['id_habilidad']; ?>',
                                                    '<?php echo htmlspecialchars(addslashes($habilidad['nombre'])); ?>',
                                                    '<?php echo htmlspecialchars(addslashes($habilidad['categoria_habilidad'] ?? '')); ?>'
                                                )"
                                            >
                                                Editar
                                            </button>

                                            <form action="../procesos/eliminar_habilidad.php" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar esta habilidad?');">
                                                <input type="hidden" name="id_habilidad" value="<?php echo (int) $habilidad['id_habilidad']; ?>">
                                                <button type="submit" class="btn btn-reject btn-sm">Eliminar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">No se encontraron habilidades.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</div>

<div class="modal-overlay" id="modalEditarCategoria">
    <div class="custom-modal">
        <div class="custom-modal-header">
            <h3>Editar categoría</h3>
            <button type="button" class="modal-close-btn" onclick="cerrarModalEditarCategoria()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form action="../procesos/editar_categoria.php" method="POST" class="stack-form">
            <input type="hidden" name="id_categoria" id="edit_id_categoria">

            <div class="filter-group">
                <label for="edit_nombre_categoria">Nombre</label>
                <input type="text" id="edit_nombre_categoria" name="nombre_categoria" maxlength="100" required>
            </div>

            <div class="filter-group">
                <label for="edit_descripcion_categoria">Descripción</label>
                <textarea id="edit_descripcion_categoria" name="descripcion_categoria" rows="5"></textarea>
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
                <button type="button" class="btn superadmin-btn-light" onclick="cerrarModalEditarCategoria()">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="modalEditarHabilidad">
    <div class="custom-modal">
        <div class="custom-modal-header">
            <h3>Editar habilidad</h3>
            <button type="button" class="modal-close-btn" onclick="cerrarModalEditarHabilidad()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form action="../procesos/editar_habilidad.php" method="POST" class="stack-form">
            <input type="hidden" name="id_habilidad" id="edit_id_habilidad">

            <div class="filter-group">
                <label for="edit_nombre_habilidad">Nombre</label>
                <input type="text" id="edit_nombre_habilidad" name="nombre" maxlength="100" required>
            </div>

            <div class="filter-group">
                <label for="edit_categoria_habilidad">Categoría de habilidad</label>
                <input type="text" id="edit_categoria_habilidad" name="categoria_habilidad" maxlength="100">
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
                <button type="button" class="btn superadmin-btn-light" onclick="cerrarModalEditarHabilidad()">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalEditarCategoria(id, nombre, descripcion) {
    document.getElementById('edit_id_categoria').value = id;
    document.getElementById('edit_nombre_categoria').value = nombre;
    document.getElementById('edit_descripcion_categoria').value = descripcion;
    document.getElementById('modalEditarCategoria').classList.add('active');
}

function cerrarModalEditarCategoria() {
    document.getElementById('modalEditarCategoria').classList.remove('active');
}

function abrirModalEditarHabilidad(id, nombre, categoria) {
    document.getElementById('edit_id_habilidad').value = id;
    document.getElementById('edit_nombre_habilidad').value = nombre;
    document.getElementById('edit_categoria_habilidad').value = categoria;
    document.getElementById('modalEditarHabilidad').classList.add('active');
}

function cerrarModalEditarHabilidad() {
    document.getElementById('modalEditarHabilidad').classList.remove('active');
}
</script>
<script src="../assets/js/main.js"></script>
</body>
</html>