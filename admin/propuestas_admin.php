<?php
require_once __DIR__ . '/../includes/session_admin.php';
require_once __DIR__ . '/../config/conexion.php';

$busqueda = trim($_GET['q'] ?? '');
$filtroEstado = trim($_GET['estado'] ?? '');

$stmtEstados = $pdo->query("
    SELECT DISTINCT estado
    FROM propuesta
    WHERE estado IS NOT NULL
      AND TRIM(estado) <> ''
    ORDER BY estado ASC
");
$estadosDisponibles = $stmtEstados->fetchAll(PDO::FETCH_COLUMN);

$params = [];
$where = [];

if ($busqueda !== '') {
    $where[] = "(
        COALESCE(p.titulo_propuesta, '') ILIKE :busqueda
        OR COALESCE(d.titulo, '') ILIKE :busqueda
        OR COALESCE(o.nombre_empresa, '') ILIKE :busqueda
        OR COALESCE(e.nombre, '') ILIKE :busqueda
        OR COALESCE(e.apellido_paterno, '') ILIKE :busqueda
        OR COALESCE(u.correo_electronico, '') ILIKE :busqueda
    )";
    $params[':busqueda'] = '%' . $busqueda . '%';
}

if ($filtroEstado !== '' && in_array($filtroEstado, $estadosDisponibles, true)) {
    $where[] = "p.estado = :estado";
    $params[':estado'] = $filtroEstado;
}

$whereSql = !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '';

$stmtResumen = $pdo->query("
    SELECT
        COUNT(*) AS total,
        COUNT(*) FILTER (WHERE LOWER(COALESCE(estado, '')) IN ('pendiente', 'en revision', 'en revision')) AS pendientes,
        COUNT(*) FILTER (WHERE LOWER(COALESCE(estado, '')) IN ('aceptada', 'aprobada')) AS aceptadas,
        COUNT(*) FILTER (WHERE LOWER(COALESCE(estado, '')) = 'rechazada') AS rechazadas
    FROM propuesta
");
$resumen = $stmtResumen->fetch(PDO::FETCH_ASSOC) ?: [];

$sql = "
    SELECT
        p.id_propuesta,
        COALESCE(p.titulo_propuesta, d.titulo) AS titulo_propuesta,
        p.estado,
        p.fecha_envio,
        p.fecha_respuesta,
        d.titulo AS desafio,
        o.nombre_empresa,
        COALESCE(NULLIF(CONCAT_WS(' ', e.nombre, e.apellido_paterno, e.apellido_materno), ''), u.correo_electronico) AS estudiante,
        u.correo_electronico
    FROM propuesta p
    INNER JOIN desafio d ON d.id_desafio = p.id_desafio
    INNER JOIN organizacion o ON o.id_organizacion = d.id_organizacion
    INNER JOIN estudiante e ON e.id_estudiante = p.id_estudiante
    INNER JOIN usuario u ON u.id_usuario = e.id_estudiante
    {$whereSql}
    ORDER BY p.fecha_envio DESC
    LIMIT 80
";
$stmtPropuestas = $pdo->prepare($sql);
$stmtPropuestas->execute($params);
$propuestas = $stmtPropuestas->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Propuestas | Admin - EduNexo MP</title>
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
                <h1>Propuestas</h1>
                <p>Revisa el flujo de postulaciones entre estudiantes y organizaciones.</p>
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
                <span>Total propuestas</span>
                <strong><?php echo (int) ($resumen['total'] ?? 0); ?></strong>
            </article>
            <article>
                <span>Pendientes</span>
                <strong><?php echo (int) ($resumen['pendientes'] ?? 0); ?></strong>
            </article>
            <article>
                <span>Aceptadas</span>
                <strong><?php echo (int) ($resumen['aceptadas'] ?? 0); ?></strong>
            </article>
            <article>
                <span>Rechazadas</span>
                <strong><?php echo (int) ($resumen['rechazadas'] ?? 0); ?></strong>
            </article>
        </section>

        <section class="admin-filter-card">
            <form method="GET" action="propuestas_admin.php" class="admin-filter-form">
                <label class="admin-field admin-field-wide" for="q">
                    <span>Buscar</span>
                    <input type="text" id="q" name="q" placeholder="Propuesta, estudiante, desafio u organizacion" value="<?php echo htmlspecialchars($busqueda); ?>">
                </label>

                <label class="admin-field" for="estado">
                    <span>Estado</span>
                    <select id="estado" name="estado">
                        <option value="">Todos</option>
                        <?php foreach ($estadosDisponibles as $estado): ?>
                            <option value="<?php echo htmlspecialchars($estado); ?>" <?php echo $filtroEstado === $estado ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst($estado)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <div class="admin-filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel"></i>
                        Filtrar
                    </button>
                    <a href="propuestas_admin.php" class="btn btn-nav">Limpiar</a>
                </div>
            </form>
        </section>

        <section class="admin-table-card">
            <div class="admin-panel-head">
                <div>
                    <h2>Listado de propuestas</h2>
                    <p>Ultimos 80 resultados segun los filtros activos.</p>
                </div>
            </div>

            <div class="admin-table-wrap">
                <table class="admin-data-table">
                    <thead>
                        <tr>
                            <th>Propuesta</th>
                            <th>Estudiante</th>
                            <th>Desafio</th>
                            <th>Organizacion</th>
                            <th>Estado</th>
                            <th>Envio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($propuestas)): ?>
                            <?php foreach ($propuestas as $propuesta): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($propuesta['titulo_propuesta'] ?? 'Sin titulo'); ?></strong>
                                        <span class="admin-muted">ID <?php echo (int) $propuesta['id_propuesta']; ?></span>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($propuesta['estudiante'] ?? 'Sin estudiante'); ?>
                                        <span class="admin-muted"><?php echo htmlspecialchars($propuesta['correo_electronico'] ?? 'Sin correo'); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($propuesta['desafio'] ?? 'Sin desafio'); ?></td>
                                    <td><?php echo htmlspecialchars($propuesta['nombre_empresa'] ?? 'Sin organizacion'); ?></td>
                                    <td>
                                        <span class="admin-status <?php echo admin_page_status_class($propuesta['estado'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($propuesta['estado'] ?: 'Sin estado'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo !empty($propuesta['fecha_envio']) ? htmlspecialchars(date('d/m/Y', strtotime($propuesta['fecha_envio']))) : 'Sin fecha'; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="admin-empty-row">No se encontraron propuestas con esos filtros.</td>
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
