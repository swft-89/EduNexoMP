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

$busqueda = trim($_GET['q'] ?? '');
$filtroEstado = trim($_GET['estado'] ?? '');

$params = [];
$where = [];

if ($busqueda !== '') {
    $where[] = "(
        CONCAT_WS(' ', e.nombre, e.apellido_paterno, e.apellido_materno) ILIKE :busqueda
        OR d.titulo ILIKE :busqueda
        OR o.nombre_empresa ILIKE :busqueda
    )";
    $params[':busqueda'] = '%' . $busqueda . '%';
}

if ($filtroEstado !== '') {
    $where[] = "LOWER(COALESCE(p.estado, '')) = :estado";
    $params[':estado'] = strtolower($filtroEstado);
}

$sql = "
    SELECT
        p.id_propuesta,
        p.fecha_envio,
        p.estado,
        p.feedback,
        p.fecha_respuesta,
        CONCAT_WS(' ', e.nombre, e.apellido_paterno, e.apellido_materno) AS nombre_estudiante,
        d.titulo AS titulo_desafio,
        o.nombre_empresa
    FROM propuesta p
    INNER JOIN estudiante e ON e.id_estudiante = p.id_estudiante
    INNER JOIN desafio d ON d.id_desafio = p.id_desafio
    INNER JOIN organizacion o ON o.id_organizacion = d.id_organizacion
";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY p.fecha_envio DESC";

$stmtPropuestas = $pdo->prepare($sql);
$stmtPropuestas->execute($params);
$propuestas = $stmtPropuestas->fetchAll(PDO::FETCH_ASSOC);

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Propuestas | Superadmin - EduNexo MP</title>
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
                <h1>Gestión de propuestas</h1>
                <p>Consulta las postulaciones realizadas por los estudiantes a los desafíos publicados.</p>
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
                    <h2>Buscador y filtros</h2>
                    <p>Encuentra propuestas por estudiante, desafío, organización o estado</p>
                </div>
            </div>

            <form method="GET" action="propuestas_superadmin.php" class="filters-form">
                <div class="filter-group search-box">
                    <label for="q">Buscar</label>
                    <input
                        type="text"
                        id="q"
                        name="q"
                        placeholder="Estudiante, desafío u organización"
                        value="<?php echo htmlspecialchars($busqueda); ?>"
                    >
                </div>

                <div class="filter-group">
                    <label for="estado">Estado</label>
                    <select name="estado" id="estado">
                        <option value="">Todos</option>
                        <option value="pendiente" <?php echo $filtroEstado === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                        <option value="aceptada" <?php echo $filtroEstado === 'aceptada' ? 'selected' : ''; ?>>Aceptada</option>
                        <option value="rechazada" <?php echo $filtroEstado === 'rechazada' ? 'selected' : ''; ?>>Rechazada</option>
                        <option value="en revision" <?php echo $filtroEstado === 'en revision' ? 'selected' : ''; ?>>En revisión</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary btn-sm">Aplicar</button>
                    <a href="propuestas_superadmin.php" class="btn superadmin-btn-light btn-sm">Limpiar</a>
                </div>
            </form>
        </section>

        <section class="superadmin-panel-card full-card">
            <div class="panel-card-head">
                <div>
                    <h2>Propuestas registradas</h2>
                    <p>Listado general de propuestas enviadas en la plataforma</p>
                </div>
            </div>

            <div class="activity-table-wrap">
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Estudiante</th>
                            <th>Desafío</th>
                            <th>Organización</th>
                            <th>Estado</th>
                            <th>Fecha envío</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($propuestas)): ?>
                            <?php foreach ($propuestas as $propuesta): ?>
                                <tr>
                                    <td><?php echo (int) $propuesta['id_propuesta']; ?></td>
                                    <td><?php echo htmlspecialchars($propuesta['nombre_estudiante'] ?: 'Sin nombre'); ?></td>
                                    <td><?php echo htmlspecialchars($propuesta['titulo_desafio']); ?></td>
                                    <td><?php echo htmlspecialchars($propuesta['nombre_empresa']); ?></td>
                                    <td>
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
                                    </td>
                                    <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($propuesta['fecha_envio']))); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="detalle_propuesta_superadmin.php?id=<?php echo (int) $propuesta['id_propuesta']; ?>" class="btn superadmin-btn-light btn-sm">
                                                Ver
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">No se encontraron propuestas con esos filtros.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</div>
<script src="../../assets/js/main.js"></script>
</body>
</html>