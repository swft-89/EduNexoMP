<?php
require_once '../includes/session_estudiante.php';
require_once '../config/conexion.php';

$idUsuario = $_SESSION['usuario_id'];

/* Datos del estudiante */
$stmt = $pdo->prepare("
    SELECT
        u.correo_electronico,
        e.id_estudiante,
        e.nombre,
        e.apellido_paterno,
        e.apellido_materno,
        e.carrera,
        e.no_control,
        e.semestre,
        e.curp,
        e.telefono,
        d.pais,
        d.estado,
        d.ciudad,
        d.colonia,
        d.codigo_postal,
        d.calle,
        d.num_exterior
    FROM usuario u
    INNER JOIN estudiante e
        ON u.id_usuario = e.id_estudiante
    LEFT JOIN direccion d
        ON e.id_direccion = d.id_direccion
    WHERE u.id_usuario = :id
    LIMIT 1
");
$stmt->execute([':id' => $idUsuario]);
$estudiante = $stmt->fetch();

if (!$estudiante) {
    session_unset();
    session_destroy();
    header('Location: ../../index.php');
    exit;
}

$nombreCompleto = trim(
    $estudiante['nombre'] . ' ' .
    $estudiante['apellido_paterno'] . ' ' .
    ($estudiante['apellido_materno'] ?? '')
);

$primerNombre = explode(' ', trim($estudiante['nombre']))[0] ?? $estudiante['nombre'];

/* Metricas*/

// Propuestas enviadas
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM propuesta
    WHERE id_estudiante = :id
");
$stmt->execute([':id' => $idUsuario]);
$totalPropuestas = (int) $stmt->fetchColumn();

// Favoritos
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM favoritos
    WHERE id_estudiante = :id
");
$stmt->execute([':id' => $idUsuario]);
$totalFavoritos = (int) $stmt->fetchColumn();

// Mensajes en conversaciones de sus propuestas
$stmt = $pdo->prepare("
    SELECT COUNT(m.id_mensaje)
    FROM mensaje m
    INNER JOIN conversacion c
        ON m.id_conversacion = c.id_conversacion
    INNER JOIN propuesta p
        ON c.id_propuesta = p.id_propuesta
    WHERE p.id_estudiante = :id
");
$stmt->execute([':id' => $idUsuario]);
$totalMensajes = (int) $stmt->fetchColumn();

/* Desafios recomendados */
$stmt = $pdo->prepare("
    SELECT
        d.id_desafio,
        d.titulo,
        d.descripcion,
        d.fecha_limite,
        o.nombre_empresa,

        CASE
            WHEN f.id_desafio IS NOT NULL THEN 1
            ELSE 0
        END AS es_favorito,

        COUNT(DISTINCT dh.id_habilidad) AS total_habilidades_desafio,
        COUNT(DISTINCT CASE
            WHEN eh.id_habilidad IS NOT NULL THEN dh.id_habilidad
        END) AS habilidades_coincidentes,
        CASE
            WHEN COUNT(DISTINCT dh.id_habilidad) = 0 THEN 0
            ELSE ROUND(
                COUNT(DISTINCT CASE WHEN eh.id_habilidad IS NOT NULL THEN dh.id_habilidad END) * 100.0
                / COUNT(DISTINCT dh.id_habilidad)
            )
        END AS match_porcentaje
    FROM desafio d
    INNER JOIN organizacion o
        ON d.id_organizacion = o.id_organizacion
    LEFT JOIN favoritos f
        ON d.id_desafio = f.id_desafio
        AND f.id_estudiante = :id_estudiante_fav
    LEFT JOIN desafio_habilidad dh
        ON d.id_desafio = dh.id_desafio
    LEFT JOIN estudiante_habilidad eh
        ON eh.id_habilidad = dh.id_habilidad
        AND eh.id_estudiante = :id_estudiante
    GROUP BY
        d.id_desafio,
        d.titulo,
        d.descripcion,
        d.fecha_limite,
        o.nombre_empresa,
        f.id_desafio
    ORDER BY match_porcentaje DESC, d.fecha_publicacion DESC
    LIMIT 6
");
$stmt->execute([
    ':id_estudiante' => $idUsuario,
    ':id_estudiante_fav' => $idUsuario
]);
$desafiosRecomendados = $stmt->fetchAll();

/* Habilidades por desafio*/
$desafioIds = array_column($desafiosRecomendados, 'id_desafio');
$habilidadesPorDesafio = [];

if (!empty($desafioIds)) {
    $placeholders = implode(',', array_fill(0, count($desafioIds), '?'));

    $stmt = $pdo->prepare("
        SELECT
            dh.id_desafio,
            h.nombre
        FROM desafio_habilidad dh
        INNER JOIN habilidad h
            ON dh.id_habilidad = h.id_habilidad
        WHERE dh.id_desafio IN ($placeholders)
        ORDER BY dh.id_desafio, h.nombre
    ");
    $stmt->execute($desafioIds);

    while ($row = $stmt->fetch()) {
        $idDesafio = $row['id_desafio'];
        if (!isset($habilidadesPorDesafio[$idDesafio])) {
            $habilidadesPorDesafio[$idDesafio] = [];
        }
        if (count($habilidadesPorDesafio[$idDesafio]) < 3) {
            $habilidadesPorDesafio[$idDesafio][] = $row['nombre'];
        }
    }
}

/* Perfil completo*/
$camposPerfil = [
    $estudiante['nombre'],
    $estudiante['apellido_paterno'],
    $estudiante['carrera'],
    $estudiante['no_control'],
    $estudiante['semestre'],
    $estudiante['curp'],
    $estudiante['telefono'],
    $estudiante['pais'],
    $estudiante['estado'],
    $estudiante['ciudad'],
    $estudiante['colonia'],
    $estudiante['codigo_postal'],
    $estudiante['calle'],
    $estudiante['num_exterior']
];

$camposLlenos = 0;
foreach ($camposPerfil as $campo) {
    if (!empty($campo)) {
        $camposLlenos++;
    }
}
$perfilCompleto = count($camposPerfil) > 0
    ? round(($camposLlenos / count($camposPerfil)) * 100)
    : 0;

/* Alertas de sesion */
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Estudiante - EduNexo MP</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dark.css?v=dark-fix-2">
    <link rel="stylesheet" href="../assets/css/estudiante/favoritos.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<div class="app-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-top">
            <div class="logo-mini"><i class="bi bi-mortarboard"></i></div>
            <span>EduNexo MP</span>
        </div>

        <nav class="sidebar-menu">
            <a href="dashboard_estudiante.php" class="active">
                <i class="bi bi-house-door"></i> Inicio
            </a>
            <a href="mis_propuestas.php">
                <i class="bi bi-file-earmark-text"></i> Mis propuestas
            </a>
            <a href="mis_favoritos.php">
                <i class="bi bi-heart"></i> Favoritos
            </a>
            <a href="chat.php">
                <i class="bi bi-chat"></i> Chat
            </a>
            <a href="habilidades_estudiante.php">
                <i class="bi bi-stars"></i> Mis habilidades
            </a>
            <a href="editar_perfil_estudiante.php">
                <i class="bi bi-person"></i> Perfil
            </a>
        </nav>
    </aside>

    <!-- Contenido -->
    <section class="app-content">
        <?php include __DIR__ . '/../includes/app_topbar.php'; ?>
        <div class="content-header">
            <div>
                <h1>Desafíos recomendados</h1>
                <p>Proyectos que coinciden con tu perfil y habilidades</p>
            </div>

        </div>

        <!-- Resumen del estudiante -->
        <div class="dashboard-summary-grid">
            <div class="dashboard-summary-card">
                <span>Estudiante</span>
                <strong><?php echo htmlspecialchars($nombreCompleto); ?></strong>
                <p><?php echo htmlspecialchars($estudiante['carrera']); ?> · <?php echo htmlspecialchars($estudiante['semestre']); ?>° semestre</p>
            </div>

            <div class="dashboard-summary-card">
                <span>Perfil completo</span>
                <strong><?php echo $perfilCompleto; ?>%</strong>
                <p>Información académica y de contacto</p>
            </div>

            <div class="dashboard-summary-card">
                <span>Propuestas enviadas</span>
                <strong><?php echo $totalPropuestas; ?></strong>
                <p>Postulaciones registradas</p>
            </div>

            <div class="dashboard-summary-card">
                <span>Mensajes activos</span>
                <strong><?php echo $totalMensajes; ?></strong>
                <p>Conversaciones en curso</p>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters-bar">
            <input type="text" placeholder="Buscar proyectos o empresas...">
            <select>
                <option>Todas las categorías</option>
            </select>
            <select>
                <option>Todas</option>
            </select>
        </div>

        <!-- Cards de desafios -->
        <div class="cards-grid">
            <?php if (!empty($desafiosRecomendados)): ?>
                <?php foreach ($desafiosRecomendados as $d): ?>
                    <article class="challenge-card">
                        <div class="challenge-card-header">
                            <h3><?php echo htmlspecialchars($d['titulo']); ?></h3>
                            <form action="../procesos/estudiante/toggle_favorito.php" method="POST" style="margin:0;">
                                <input type="hidden" name="id_desafio" value="<?php echo (int) $d['id_desafio']; ?>">
                                <input type="hidden" name="redirect" value="dashboard_estudiante.php">

                                <button class="favorite-btn <?php echo !empty($d['es_favorito']) ? 'active' : ''; ?>" type="submit">
                                    <i class="bi <?php echo !empty($d['es_favorito']) ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
                                </button>
                            </form>
                        </div>

                        <p class="empresa">
                            <i class="bi bi-building"></i>
                            <?php echo htmlspecialchars($d['nombre_empresa']); ?>
                        </p>

                        <div class="tags">
                            <?php
                            $tags = $habilidadesPorDesafio[$d['id_desafio']] ?? [];
                            if (!empty($tags)):
                                foreach ($tags as $tag):
                            ?>
                                <span><?php echo htmlspecialchars($tag); ?></span>
                            <?php
                                endforeach;
                            else:
                            ?>
                                <span>Sin habilidades</span>
                            <?php endif; ?>
                        </div>

                        <p class="fecha">
                            <i class="bi bi-calendar-event"></i>
                            Fecha límite:
                            <?php
                            echo !empty($d['fecha_limite'])
                                ? htmlspecialchars(date('d/m/Y', strtotime($d['fecha_limite'])))
                                : 'Sin definir';
                            ?>
                        </p>

                        <div class="match">
                            <div class="bar">
                                <div class="progress" style="width: <?php echo (int)$d['match_porcentaje']; ?>%"></div>
                            </div>
                            <span>Match <?php echo (int)$d['match_porcentaje']; ?>%</span>
                        </div>

                        <a href="detalle_desafio.php?id=<?php echo (int)$d['id_desafio']; ?>" class="btn btn-primary challenge-btn">
                            Ver detalles
                        </a>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <article class="challenge-card">
                    <div class="challenge-card-header">
                        <h3>Sin recomendaciones aún</h3>
                    </div>

                    <p class="empresa">
                        Aún no hay desafíos compatibles o faltan habilidades registradas para calcular coincidencias.
                    </p>

                    <div class="tags">
                        <span>Match 0%</span>
                    </div>

                    <div class="match">
                        <div class="bar">
                            <div class="progress" style="width: 0%"></div>
                        </div>
                        <span>Match 0%</span>
                    </div>
                </article>
            <?php endif; ?>
        </div>
    </section>
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
