<?php
require_once '../includes/session_estudiante.php';
require_once '../config/conexion.php';

$idUsuario = $_SESSION['usuario_id'] ?? null;

if (!$idUsuario) {
    header('Location: ../index.php');
    exit;
}

/* Datos del estudiante */
$stmt = $pdo->prepare("
    SELECT
        e.id_estudiante,
        e.nombre,
        e.apellido_paterno,
        e.apellido_materno,
        e.carrera,
        e.semestre,
        e.foto_url
    FROM estudiante e
    WHERE e.id_estudiante = :id_estudiante
    LIMIT 1
");
$stmt->execute([':id_estudiante' => $idUsuario]);
$estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$estudiante) {
    $_SESSION['error'] = 'No se encontró la información del estudiante.';
    header('Location: dashboard_estudiante.php');
    exit;
}

$nombreCompleto = trim(
    ($estudiante['nombre'] ?? '') . ' ' .
    ($estudiante['apellido_paterno'] ?? '') . ' ' .
    ($estudiante['apellido_materno'] ?? '')
);

/*Habilidades*/
$stmt = $pdo->prepare("
    SELECT
        h.id_habilidad,
        h.nombre,
        h.categoria_habilidad,
        eh.nivel,
        eh.fecha_registro
    FROM estudiante_habilidad eh
    INNER JOIN habilidad h
        ON h.id_habilidad = eh.id_habilidad
    WHERE eh.id_estudiante = :id_estudiante
    ORDER BY
        COALESCE(h.categoria_habilidad, 'General') ASC,
        h.nombre ASC
");
$stmt->execute([':id_estudiante' => $idUsuario]);
$misHabilidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Contadores de habilidades y de niveles */
$totalHabilidades = count($misHabilidades);

$nivelesResumen = [
    'Básico' => 0,
    'Intermedio' => 0,
    'Avanzado' => 0,
    'No especificado' => 0
];

foreach ($misHabilidades as $hab) {
    $nivel = trim($hab['nivel'] ?? '');
    if ($nivel === 'Básico') {
        $nivelesResumen['Básico']++;
    } elseif ($nivel === 'Intermedio') {
        $nivelesResumen['Intermedio']++;
    } elseif ($nivel === 'Avanzado') {
        $nivelesResumen['Avanzado']++;
    } else {
        $nivelesResumen['No especificado']++;
    }
}

function obtenerIniciales($nombreCompleto)
{
    $partes = preg_split('/\s+/', trim($nombreCompleto));
    $iniciales = '';

    foreach ($partes as $parte) {
        if ($parte !== '') {
            $iniciales .= mb_strtoupper(mb_substr($parte, 0, 1));
        }
        if (mb_strlen($iniciales) >= 2) {
            break;
        }
    }

    return $iniciales ?: 'E';
}

$iniciales = obtenerIniciales($nombreCompleto);

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis habilidades | EduNexo MP</title>

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/estudiante/habilidades_estudiante.css">
    <link rel="stylesheet" href="../assets/css/dark.css">
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
            <a href="dashboard_estudiante.php">
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
            <a href="habilidades_estudiante.php" class="active">
                <i class="bi bi-stars"></i> Mis habilidades
            </a>
            <a href="editar_perfil_estudiante.php">
                <i class="bi bi-person"></i> Perfil
            </a>
        </nav>
    </aside>

    <main class="app-content">
        <?php include __DIR__ . '/../includes/app_topbar.php'; ?>
        <div class="skills-page">

            <section class="skills-hero">
                <div class="skills-hero-top">
                    <div class="skills-hero-user">
                        <div class="skills-avatar">
                            <?php if (!empty($estudiante['foto_url'])): ?>
                                <img src="<?php echo htmlspecialchars($estudiante['foto_url']); ?>" alt="Foto de perfil">
                            <?php else: ?>
                                <?php echo htmlspecialchars($iniciales); ?>
                            <?php endif; ?>
                        </div>

                        <div>
                            <h1>Mis habilidades</h1>
                            <p>
                                <?php echo htmlspecialchars($nombreCompleto); ?>
                                · <?php echo htmlspecialchars($estudiante['carrera']); ?>
                                · <?php echo (int) $estudiante['semestre']; ?>° semestre
                            </p>
                        </div>
                    </div>

                    <div class="skills-hero-actions">
                        <a href="dashboard_estudiante.php" class="btn-en btn-en-secondary">
                            <i class="bi bi-arrow-left"></i>
                            Volver
                        </a>
                        <a href="editar_habilidades_estudiante.php" class="btn-en btn-en-primary">
                            <i class="bi bi-pencil-square"></i>
                            Editar habilidades
                        </a>
                    </div>
                </div>

                <div class="skills-summary">
                    <div class="summary-card">
                        <div class="summary-label">Total registradas</div>
                        <div class="summary-value"><?php echo $totalHabilidades; ?></div>
                    </div>

                    <div class="summary-card">
                        <div class="summary-label">Nivel básico</div>
                        <div class="summary-value"><?php echo $nivelesResumen['Básico']; ?></div>
                    </div>

                    <div class="summary-card">
                        <div class="summary-label">Nivel intermedio</div>
                        <div class="summary-value"><?php echo $nivelesResumen['Intermedio']; ?></div>
                    </div>

                    <div class="summary-card">
                        <div class="summary-label">Nivel avanzado</div>
                        <div class="summary-value"><?php echo $nivelesResumen['Avanzado']; ?></div>
                    </div>
                </div>
            </section>

            <section class="skills-main-card">
                <div class="skills-main-head">
                    <div>
                        <h2>Resumen de habilidades</h2>
                        <p>Aquí solo se muestran las habilidades que ya agregaste a tu perfil.</p>
                    </div>

                    <?php if ($totalHabilidades > 0): ?>
                        <a href="editar_habilidades_estudiante.php" class="btn-en btn-en-secondary">
                            <i class="bi bi-plus-circle"></i>
                            Agregar o modificar
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (!empty($misHabilidades)): ?>
                    <div class="skills-grid">
                        <?php foreach ($misHabilidades as $habilidad): ?>
                            <?php
                                $nivel = trim($habilidad['nivel'] ?? '');
                                $textoNivel = $nivel !== '' ? $nivel : 'No especificado';

                                $claseNivel = 'nivel-neutro';
                                if ($nivel === 'Básico') {
                                    $claseNivel = 'nivel-basico';
                                } elseif ($nivel === 'Intermedio') {
                                    $claseNivel = 'nivel-intermedio';
                                } elseif ($nivel === 'Avanzado') {
                                    $claseNivel = 'nivel-avanzado';
                                }
                            ?>
                            <article class="skill-card">
                                <div class="skill-top">
                                    <div>
                                        <h3 class="skill-name">
                                            <?php echo htmlspecialchars($habilidad['nombre']); ?>
                                        </h3>

                                        <span class="skill-category">
                                            <i class="bi bi-tag"></i>
                                            <?php echo htmlspecialchars($habilidad['categoria_habilidad'] ?: 'General'); ?>
                                        </span>
                                    </div>

                                    <span class="skill-level <?php echo $claseNivel; ?>">
                                        <?php echo htmlspecialchars($textoNivel); ?>
                                    </span>
                                </div>

                                <div class="skill-meta">
                                    <i class="bi bi-check2-circle"></i>
                                    Registrada en tu perfil
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-skills">
                        <div class="empty-icon">
                            <i class="bi bi-stars"></i>
                        </div>

                        <h3>Aún no has agregado habilidades</h3>
                        <p>
                            Completa esta sección para mejorar tu perfil y facilitar el porcentaje de coincidencia
                            con los desafíos publicados por las organizaciones.
                        </p>

                        <a href="editar_habilidades_estudiante.php" class="btn-en btn-en-primary">
                            <i class="bi bi-plus-circle"></i>
                            Agregar mis habilidades
                        </a>
                    </div>
                <?php endif; ?>
            </section>

        </div>
    </main>
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