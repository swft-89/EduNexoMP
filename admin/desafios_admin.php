<?php
require_once __DIR__ . '/../includes/session_admin.php';
require_once __DIR__ . '/../config/conexion.php';

$busqueda = trim($_GET['q'] ?? '');
$filtroEstado = trim($_GET['estado'] ?? '');
$filtroModalidad = trim($_GET['modalidad'] ?? '');

$estadosPermitidos = ['activo', 'pausado', 'cerrado', 'borrador'];

$stmtModalidades = $pdo->query("
    SELECT DISTINCT modalidad
    FROM desafio
    WHERE modalidad IS NOT NULL
      AND TRIM(modalidad) <> ''
    ORDER BY modalidad ASC
");
$modalidadesDisponibles = $stmtModalidades->fetchAll(PDO::FETCH_COLUMN);

$params = [];
$where = [];

if ($busqueda !== '') {
    $where[] = "(
        d.titulo ILIKE :busqueda
        OR COALESCE(d.descripcion, '') ILIKE :busqueda
        OR COALESCE(o.nombre_empresa, '') ILIKE :busqueda
        OR COALESCE(c.nombre_categoria, '') ILIKE :busqueda
    )";
    $params[':busqueda'] = '%' . $busqueda . '%';
}

if (in_array($filtroEstado, $estadosPermitidos, true)) {
    $where[] = "d.estado = :estado";
    $params[':estado'] = $filtroEstado;
}

if ($filtroModalidad !== '' && in_array($filtroModalidad, $modalidadesDisponibles, true)) {
    $where[] = "d.modalidad = :modalidad";
    $params[':modalidad'] = $filtroModalidad;
}

$whereSql = !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '';

$stmtResumen = $pdo->query("
    SELECT
        COUNT(*) AS total,
        COUNT(*) FILTER (WHERE LOWER(COALESCE(estado, '')) = 'activo') AS activos,
        COUNT(*) FILTER (WHERE LOWER(COALESCE(estado, '')) = 'cerrado') AS cerrados,
        COUNT(*) FILTER (
            WHERE EXISTS (
                SELECT 1
                FROM propuesta p
                WHERE p.id_desafio = desafio.id_desafio
            )
        ) AS con_propuestas
    FROM desafio
");
$resumen = $stmtResumen->fetch(PDO::FETCH_ASSOC) ?: [];

$sql = "
    SELECT
        d.id_desafio,
        d.titulo,
        d.estado,
        d.modalidad,
        d.fecha_publicacion,
        d.fecha_limite,
        o.nombre_empresa,
        c.nombre_categoria,
        COUNT(p.id_propuesta) AS total_propuestas
    FROM desafio d
    INNER JOIN organizacion o ON o.id_organizacion = d.id_organizacion
    LEFT JOIN categoria c ON c.id_categoria = d.id_categoria
    LEFT JOIN propuesta p ON p.id_desafio = d.id_desafio
    {$whereSql}
    GROUP BY d.id_desafio, d.titulo, d.estado, d.modalidad, d.fecha_publicacion, d.fecha_limite, o.nombre_empresa, c.nombre_categoria
    ORDER BY d.fecha_publicacion DESC
    LIMIT 80
";
$stmtDesafios = $pdo->prepare($sql);
$stmtDesafios->execute($params);
$desafios = $stmtDesafios->fetchAll(PDO::FETCH_ASSOC);

function admin_page_status_class(?string $estado): string
{
    $estado = strtolower(trim((string) $estado));

    if (in_array($estado, ['activo', 'aceptada', 'aprobada'], true)) {
        return 'is-ok';
    }

    if (in_array($estado, ['pendiente', 'en revision', 'en revision', 'borrador', 'pausado'], true)) {
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
    <title>Desafios | Admin - EduNexo MP</title>
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
                <h1>Desafios</h1>
                <p>Supervisa publicaciones, fechas limite y volumen de propuestas por organizacion.</p>
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
                <span>Total desafios</span>
                <strong><?php echo (int) ($resumen['total'] ?? 0); ?></strong>
            </article>
            <article>
                <span>Activos</span>
                <strong><?php echo (int) ($resumen['activos'] ?? 0); ?></strong>
            </article>
            <article>
                <span>Cerrados</span>
                <strong><?php echo (int) ($resumen['cerrados'] ?? 0); ?></strong>
            </article>
            <article>
                <span>Con propuestas</span>
                <strong><?php echo (int) ($resumen['con_propuestas'] ?? 0); ?></strong>
            </article>
        </section>

        <section class="admin-filter-card">
            <form method="GET" action="desafios_admin.php" class="admin-filter-form">
                <label class="admin-field admin-field-wide" for="q">
                    <span>Buscar</span>
                    <input type="text" id="q" name="q" placeholder="Titulo, organizacion o categoria" value="<?php echo htmlspecialchars($busqueda); ?>">
                </label>

                <label class="admin-field" for="estado">
                    <span>Estado</span>
                    <select id="estado" name="estado">
                        <option value="">Todos</option>
                        <option value="activo" <?php echo $filtroEstado === 'activo' ? 'selected' : ''; ?>>Activo</option>
                        <option value="pausado" <?php echo $filtroEstado === 'pausado' ? 'selected' : ''; ?>>Pausado</option>
                        <option value="cerrado" <?php echo $filtroEstado === 'cerrado' ? 'selected' : ''; ?>>Cerrado</option>
                        <option value="borrador" <?php echo $filtroEstado === 'borrador' ? 'selected' : ''; ?>>Borrador</option>
                    </select>
                </label>

                <label class="admin-field" for="modalidad">
                    <span>Modalidad</span>
                    <select id="modalidad" name="modalidad">
                        <option value="">Todas</option>
                        <?php foreach ($modalidadesDisponibles as $modalidad): ?>
                            <option value="<?php echo htmlspecialchars($modalidad); ?>" <?php echo $filtroModalidad === $modalidad ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($modalidad); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <div class="admin-filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel"></i>
                        Filtrar
                    </button>
                    <a href="desafios_admin.php" class="btn btn-nav">Limpiar</a>
                </div>
            </form>
        </section>

        <section class="admin-table-card">
            <div class="admin-panel-head">
                <div>
                    <h2>Listado de desafios</h2>
                    <p>Ultimos 80 resultados segun los filtros activos.</p>
                </div>
            </div>

            <div class="admin-table-wrap">
                <table class="admin-data-table">
                    <thead>
                        <tr>
                            <th>Desafio</th>
                            <th>Organizacion</th>
                            <th>Categoria</th>
                            <th>Modalidad</th>
                            <th>Estado</th>
                            <th>Propuestas</th>
                            <th>Limite</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($desafios)): ?>
                            <?php foreach ($desafios as $desafio): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($desafio['titulo'] ?? 'Sin titulo'); ?></strong>
                                        <span class="admin-muted">ID <?php echo (int) $desafio['id_desafio']; ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($desafio['nombre_empresa'] ?? 'Sin organizacion'); ?></td>
                                    <td><?php echo htmlspecialchars($desafio['nombre_categoria'] ?? 'Sin categoria'); ?></td>
                                    <td><span class="admin-chip"><?php echo htmlspecialchars(ucfirst($desafio['modalidad'] ?? 'Sin dato')); ?></span></td>
                                    <td>
                                        <span class="admin-status <?php echo admin_page_status_class($desafio['estado'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($desafio['estado'] ?: 'Sin estado'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo (int) $desafio['total_propuestas']; ?></td>
                                    <td>
                                        <?php echo !empty($desafio['fecha_limite']) ? htmlspecialchars(date('d/m/Y', strtotime($desafio['fecha_limite']))) : 'Sin fecha'; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="admin-empty-row">No se encontraron desafios con esos filtros.</td>
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
