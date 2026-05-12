<?php
require_once '../includes/session_estudiante.php';
require_once '../config/conexion.php';
require_once '../includes/profile_photo.php';

$idUsuario = $_SESSION['usuario_id'] ?? null;

if (!$idUsuario) {
    header('Location: ../index.php');
    exit;
}

/*Datos del estudiante*/
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

/* Catalogo de habilidades*/
$stmt = $pdo->query("
    SELECT
        id_habilidad,
        nombre,
        categoria_habilidad
    FROM habilidad
    ORDER BY
        COALESCE(categoria_habilidad, 'General') ASC,
        nombre ASC
");
$catalogoHabilidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Habilidades del estudiante */
$stmt = $pdo->prepare("
    SELECT
        id_habilidad,
        nivel
    FROM estudiante_habilidad
    WHERE id_estudiante = :id_estudiante
");
$stmt->execute([':id_estudiante' => $idUsuario]);
$habilidadesActuales = $stmt->fetchAll(PDO::FETCH_ASSOC);

$habilidadesMap = [];
foreach ($habilidadesActuales as $habilidad) {
    $habilidadesMap[(int)$habilidad['id_habilidad']] = [
        'nivel' => $habilidad['nivel']
    ];
}

/* Categorias unicas */
$categorias = [];
foreach ($catalogoHabilidades as $habilidad) {
    $cat = trim($habilidad['categoria_habilidad'] ?? '');
    $cat = $cat !== '' ? $cat : 'General';
    if (!in_array($cat, $categorias, true)) {
        $categorias[] = $cat;
    }
}
sort($categorias);

$totalCatalogo = count($catalogoHabilidades);
$totalSeleccionadas = count($habilidadesMap);

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar habilidades | EduNexo MP</title>

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
            <div class="logo-mini"><i class="bi bi-mortarboard"></i></div>
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
        <div class="skills-edit-page">

            <section class="skills-edit-hero">
                <div class="skills-edit-top">
                    <div class="skills-user">
                        <div class="skills-avatar">
                            <?php if (!empty($estudiante['foto_url'])): ?>
                                <img src="<?php echo htmlspecialchars(edunexo_asset_url($estudiante['foto_url'])); ?>" alt="Foto de perfil">
                            <?php else: ?>
                                <?php echo htmlspecialchars($iniciales); ?>
                            <?php endif; ?>
                        </div>

                        <div>
                            <h1>Editar habilidades</h1>
                            <p>
                                <?php echo htmlspecialchars($nombreCompleto); ?>
                                · <?php echo htmlspecialchars($estudiante['carrera']); ?>
                                · <?php echo (int)$estudiante['semestre']; ?>° semestre
                            </p>
                        </div>
                    </div>

                    <div class="skills-actions">
                        <a href="habilidades_estudiante.php" class="btn-en btn-en-secondary">
                            <i class="bi bi-arrow-left"></i>
                            Volver a mis habilidades
                        </a>
                    </div>
                </div>

                <div class="skills-stats">
                    <div class="stat-card">
                        <div class="stat-label">Catálogo disponible</div>
                        <div class="stat-value"><?php echo $totalCatalogo; ?></div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Ya seleccionadas</div>
                        <div class="stat-value" id="contadorSeleccionadas"><?php echo $totalSeleccionadas; ?></div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Pendientes por explorar</div>
                        <div class="stat-value" id="contadorPendientes"><?php echo max(0, $totalCatalogo - $totalSeleccionadas); ?></div>
                    </div>
                </div>
            </section>

            <section class="skills-edit-card">
                <form action="../procesos/estudiante/guardar_habilidades_estudiante.php" method="POST" id="formHabilidadesEstudiante">

                    <div class="skills-toolbar">
                        <div class="search-box">
                            <i class="bi bi-search"></i>
                            <input type="text" id="buscarHabilidad" placeholder="Buscar habilidad por nombre...">
                        </div>

                        <div class="filter-box">
                            <select id="filtroCategoria">
                                <option value="">Todas las categorías</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo htmlspecialchars($categoria); ?>">
                                        <?php echo htmlspecialchars($categoria); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="skills-helper">
                        Selecciona las habilidades que forman parte de tu perfil y define tu nivel en cada una.
                        Estas habilidades ayudarán a mejorar la coincidencia con los desafíos publicados.
                    </div>

                    <div class="skills-grid" id="skillsGrid">
                        <?php foreach ($catalogoHabilidades as $habilidad): ?>
                            <?php
                                $idHabilidad = (int)$habilidad['id_habilidad'];
                                $categoria = trim($habilidad['categoria_habilidad'] ?? '');
                                $categoria = $categoria !== '' ? $categoria : 'General';

                                $seleccionada = isset($habilidadesMap[$idHabilidad]);
                                $nivelSeleccionado = $seleccionada ? ($habilidadesMap[$idHabilidad]['nivel'] ?? '') : '';
                            ?>
                            <article
                                class="skill-option <?php echo $seleccionada ? 'is-checked' : ''; ?>"
                                data-skill-name="<?php echo htmlspecialchars(mb_strtolower($habilidad['nombre'])); ?>"
                                data-skill-category="<?php echo htmlspecialchars($categoria); ?>"
                            >
                                <div class="skill-check-row">
                                    <label class="skill-check-label">
                                        <input
                                            type="checkbox"
                                            name="habilidades[]"
                                            value="<?php echo $idHabilidad; ?>"
                                            class="checkbox-habilidad"
                                            <?php echo $seleccionada ? 'checked' : ''; ?>
                                        >

                                        <div>
                                            <h3 class="skill-name">
                                                <?php echo htmlspecialchars($habilidad['nombre']); ?>
                                            </h3>

                                            <span class="skill-category">
                                                <i class="bi bi-tag"></i>
                                                <?php echo htmlspecialchars($categoria); ?>
                                            </span>
                                        </div>
                                    </label>

                                    <?php if ($seleccionada): ?>
                                        <span class="selected-pill">Seleccionada</span>
                                    <?php else: ?>
                                        <span class="selected-pill" style="display:none;">Seleccionada</span>
                                    <?php endif; ?>
                                </div>

                                <div class="skill-level-wrap">
                                    <label for="nivel_<?php echo $idHabilidad; ?>">Nivel</label>
                                    <select
                                        name="nivel[<?php echo $idHabilidad; ?>]"
                                        id="nivel_<?php echo $idHabilidad; ?>"
                                        class="select-nivel"
                                    >
                                        <option value="" <?php echo $nivelSeleccionado === '' ? 'selected' : ''; ?>>No especificado</option>
                                        <option value="Básico" <?php echo $nivelSeleccionado === 'Básico' ? 'selected' : ''; ?>>Básico</option>
                                        <option value="Intermedio" <?php echo $nivelSeleccionado === 'Intermedio' ? 'selected' : ''; ?>>Intermedio</option>
                                        <option value="Avanzado" <?php echo $nivelSeleccionado === 'Avanzado' ? 'selected' : ''; ?>>Avanzado</option>
                                    </select>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <div class="no-results" id="noResults">
                        <i class="bi bi-search"></i>
                        No se encontraron habilidades con esos filtros.
                    </div>

                    <div class="skills-footer">
                        <div class="skills-footer-note">
                            Puedes seleccionar solo las habilidades que realmente forman parte de tu perfil.
                            Después podrás volver a esta sección para actualizarlas cuando quieras.
                        </div>

                        <div class="skills-actions">
                            <a href="habilidades_estudiante.php" class="btn-en btn-en-secondary">
                                Cancelar
                            </a>
                            <button type="submit" class="btn-en btn-en-primary">
                                <i class="bi bi-check2-circle"></i>
                                Guardar cambios
                            </button>
                        </div>
                    </div>

                </form>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('buscarHabilidad');
    const categoryFilter = document.getElementById('filtroCategoria');
    const cards = Array.from(document.querySelectorAll('.skill-option'));
    const noResults = document.getElementById('noResults');
    const checkboxes = Array.from(document.querySelectorAll('.checkbox-habilidad'));
    const contadorSeleccionadas = document.getElementById('contadorSeleccionadas');
    const contadorPendientes = document.getElementById('contadorPendientes');
    const totalCatalogo = <?php echo (int)$totalCatalogo; ?>;

    function actualizarContadores() {
        const seleccionadas = checkboxes.filter(cb => cb.checked).length;
        contadorSeleccionadas.textContent = seleccionadas;
        contadorPendientes.textContent = Math.max(0, totalCatalogo - seleccionadas);
    }

    function aplicarFiltros() {
        const texto = (searchInput.value || '').trim().toLowerCase();
        const categoria = categoryFilter.value.trim();
        let visibles = 0;

        cards.forEach(card => {
            const nombre = card.dataset.skillName || '';
            const categoriaCard = card.dataset.skillCategory || '';

            const coincideTexto = nombre.includes(texto);
            const coincideCategoria = categoria === '' || categoriaCard === categoria;

            if (coincideTexto && coincideCategoria) {
                card.style.display = '';
                visibles++;
            } else {
                card.style.display = 'none';
            }
        });

        noResults.style.display = visibles === 0 ? 'block' : 'none';
    }

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            const card = this.closest('.skill-option');
            const pill = card.querySelector('.selected-pill');

            if (this.checked) {
                card.classList.add('is-checked');
                if (pill) {
                    pill.style.display = 'inline-block';
                }
            } else {
                card.classList.remove('is-checked');
                if (pill) {
                    pill.style.display = 'none';
                }
            }

            actualizarContadores();
        });
    });

    searchInput.addEventListener('input', aplicarFiltros);
    categoryFilter.addEventListener('change', aplicarFiltros);

    actualizarContadores();
    aplicarFiltros();
});
</script>
</body>
</html>
