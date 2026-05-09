<?php
require_once '../../includes/session_organizacion.php';
require_once '../../config/conexion.php';

$idUsuario = $_SESSION['usuario_id'];
$idDesafio = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($idDesafio <= 0) {
    header('Location: dashboard_organizacion.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        d.id_desafio,
        d.titulo,
        d.descripcion,
        d.fecha_limite,
        d.estado,
        d.requisitos_especificos,
        d.modalidad,
        d.id_categoria
    FROM desafio d
    WHERE d.id_desafio = :id_desafio
      AND d.id_organizacion = :id_organizacion
    LIMIT 1
");
$stmt->execute([
    ':id_desafio' => $idDesafio,
    ':id_organizacion' => $idUsuario
]);
$desafio = $stmt->fetch();

if (!$desafio) {
    $_SESSION['error'] = 'Desafío no encontrado.';
    header('Location: dashboard_organizacion.php');
    exit;
}

$stmt = $pdo->query("
    SELECT id_categoria, nombre_categoria
    FROM categoria
    ORDER BY nombre_categoria ASC
");
$categorias = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT
        h.id_habilidad,
        h.nombre,
        h.categoria_habilidad,
        COALESCE(usos.total_usos, 0) AS total_usos
    FROM habilidad h
    LEFT JOIN (
        SELECT id_habilidad, COUNT(*) AS total_usos
        FROM (
            SELECT id_habilidad FROM estudiante_habilidad
            UNION ALL
            SELECT id_habilidad FROM desafio_habilidad
        ) uso_total
        GROUP BY id_habilidad
    ) usos
        ON h.id_habilidad = usos.id_habilidad
    ORDER BY total_usos DESC, h.nombre ASC
");
$habilidades = $stmt->fetchAll();
$habilidadesDestacadas = array_slice($habilidades, 0, 3);

$stmt = $pdo->prepare("
    SELECT
        id_habilidad,
        nivel_requerido,
        obligatorio
    FROM desafio_habilidad
    WHERE id_desafio = :id_desafio
");
$stmt->execute([':id_desafio' => $idDesafio]);

$habilidadesAsignadas = [];
while ($row = $stmt->fetch()) {
    $habilidadesAsignadas[(int)$row['id_habilidad']] = [
        'nivel_requerido' => $row['nivel_requerido'],
        'obligatorio' => $row['obligatorio']
    ];
}

$errorForm = $_SESSION['error_form'] ?? [];
$errorFields = $_SESSION['error_fields'] ?? [];
$old = $_SESSION['old_editar_desafio'] ?? [];

unset($_SESSION['error_form'], $_SESSION['error_fields'], $_SESSION['old_editar_desafio']);

$valores = [
    'titulo' => $old['titulo'] ?? $desafio['titulo'],
    'descripcion' => $old['descripcion'] ?? $desafio['descripcion'],
    'requisitos_especificos' => $old['requisitos_especificos'] ?? $desafio['requisitos_especificos'],
    'modalidad' => $old['modalidad'] ?? $desafio['modalidad'],
    'estado' => $old['estado'] ?? $desafio['estado'],
    'id_categoria' => $old['id_categoria'] ?? $desafio['id_categoria'],
    'fecha_limite' => $old['fecha_limite'] ?? ($desafio['fecha_limite'] ? date('Y-m-d', strtotime($desafio['fecha_limite'])) : '')
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar desafío - EduNexo MP</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/org/crear_desafio.css">
    <link rel="stylesheet" href="../../assets/css/dark.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<div class="app-layout org-layout">
    <aside class="sidebar org-sidebar">
        <div class="sidebar-top">
            <div class="logo-mini">EN</div>
            <span>EduNexo MP</span>
        </div>

        <nav class="sidebar-menu">
            <a href="../dashboard_organizacion.php">
                <i class="bi bi-house-door"></i> Inicio
            </a>
            <a href="crear_desafio.php">
                <i class="bi bi-plus-circle"></i> Crear desafío
            </a>
            <a href="../mis_desafios.php" class="active">
                <i class="bi bi-briefcase"></i> Mis desafíos
            </a>
            <a href="../propuestas_recibidas.php">
                <i class="bi bi-file-earmark-text"></i> Propuestas
            </a>
            <a href="../chat_organizacion.php">
                <i class="bi bi-chat"></i> Chat
            </a>
            <a href="../editar_perfil_organizacion.php">
                <i class="bi bi-building"></i> Perfil
            </a>
        </nav>
    </aside>

    <section class="app-content org-content">
        <div class="crear-desafio-wrap">
            <div class="crear-desafio-head">
                <div>
                    <h1>Editar desafío</h1>
                    <p>Actualiza la información de tu desafío</p>
                </div>
                <a href="detalle_desafio_organizacion.php?id=<?php echo (int) $desafio['id_desafio']; ?>" class="btn btn-nav">Volver</a>
            </div>

            <div class="crear-desafio-card">
                <form action="../../procesos/actualizar_desafio.php" method="POST" class="crear-desafio-form" novalidate>
                    <input type="hidden" name="id_desafio" value="<?php echo (int) $desafio['id_desafio']; ?>">

                    <div class="form-grid-2">
                        <div class="form-group full">
                            <label for="titulo">Título del desafío</label>
                            <input type="text" id="titulo" name="titulo" maxlength="150"
                                   value="<?php echo htmlspecialchars($valores['titulo'] ?? ''); ?>"
                                   class="<?php echo !empty($errorFields['titulo']) ? 'input-error' : ''; ?>">
                        </div>

                        <div class="form-group full">
                            <label for="descripcion">Descripción</label>
                            <textarea id="descripcion" name="descripcion" rows="5"
                                      class="<?php echo !empty($errorFields['descripcion']) ? 'input-error' : ''; ?>"><?php echo htmlspecialchars($valores['descripcion'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group full">
                            <label for="requisitos_especificos">Requisitos específicos</label>
                            <textarea id="requisitos_especificos" name="requisitos_especificos" rows="4"><?php echo htmlspecialchars($valores['requisitos_especificos'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="id_categoria">Categoría</label>
                            <select id="id_categoria" name="id_categoria"
                                    class="<?php echo !empty($errorFields['id_categoria']) ? 'input-error' : ''; ?>">
                                <option value="">Selecciona una categoría</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo (int) $categoria['id_categoria']; ?>"
                                        <?php echo ((int)($valores['id_categoria'] ?? 0) === (int)$categoria['id_categoria']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($categoria['nombre_categoria']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="modalidad">Modalidad</label>
                            <select id="modalidad" name="modalidad">
                                <option value="">Selecciona una modalidad</option>
                                <option value="Presencial" <?php echo (($valores['modalidad'] ?? '') === 'Presencial') ? 'selected' : ''; ?>>Presencial</option>
                                <option value="Remoto" <?php echo (($valores['modalidad'] ?? '') === 'Remoto') ? 'selected' : ''; ?>>Remoto</option>
                                <option value="Híbrido" <?php echo (($valores['modalidad'] ?? '') === 'Híbrido') ? 'selected' : ''; ?>>Híbrido</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="fecha_limite">Fecha límite</label>
                            <input type="date" id="fecha_limite" name="fecha_limite"
                                   value="<?php echo htmlspecialchars($valores['fecha_limite'] ?? ''); ?>"
                                   class="<?php echo !empty($errorFields['fecha_limite']) ? 'input-error' : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="estado">Estado</label>
                            <select id="estado" name="estado"
                                    class="<?php echo !empty($errorFields['estado']) ? 'input-error' : ''; ?>">
                                <option value="activo" <?php echo (($valores['estado'] ?? '') === 'activo') ? 'selected' : ''; ?>>Activo</option>
                                <option value="cerrado" <?php echo (($valores['estado'] ?? '') === 'cerrado') ? 'selected' : ''; ?>>Cerrado</option>
                            </select>
                        </div>
                    </div>

                    <div class="crear-section-divider">
                        <h2>Habilidades requeridas</h2>
                        <p>Actualiza las habilidades necesarias para este desafío.</p>
                    </div>

                    <?php
                    $habilidadesOld = $old['habilidades'] ?? array_keys($habilidadesAsignadas);
                    $nivelesOld = $old['nivel_requerido'] ?? [];
                    $obligatoriosOld = $old['obligatorio'] ?? [];
                    ?>


                    <div class="habilidades-featured">
                        <div class="habilidades-featured-head">
                            <h3>Habilidades más utilizadas</h3>
                            <button type="button" class="btn btn-nav" id="openSkillsModal">
                                Mostrar más
                            </button>
                        </div>

                        <div class="habilidades-featured-grid">
                            <?php foreach ($habilidadesDestacadas as $habilidad): ?>
                                <?php
                                $idHabilidad = (int) $habilidad['id_habilidad'];
                                $seleccionada = in_array($idHabilidad, array_map('intval', $habilidadesOld), true);

                                $nivelActual = $nivelesOld[$idHabilidad]
                                    ?? ($habilidadesAsignadas[$idHabilidad]['nivel_requerido'] ?? '');

                                $obligatorioActual = $obligatoriosOld[$idHabilidad]
                                    ?? ($habilidadesAsignadas[$idHabilidad]['obligatorio'] ?? 0);
                                ?>

                                <div class="habilidad-mini-card" data-habilidad="<?php echo htmlspecialchars(strtolower($habilidad['nombre'])); ?>">
                                    <label class="habilidad-featured-card">
                                        <input
                                            type="checkbox"
                                            name="habilidades[]"
                                            value="<?php echo $idHabilidad; ?>"
                                            data-skill-id="<?php echo $idHabilidad; ?>"
                                            <?php echo $seleccionada ? 'checked' : ''; ?>
                                        >

                                        <span>
                                            <strong><?php echo htmlspecialchars($habilidad['nombre']); ?></strong>
                                            <small><?php echo htmlspecialchars($habilidad['categoria_habilidad'] ?? 'General'); ?></small>
                                        </span>
                                    </label>

                                    <div class="habilidad-mini-options">
                                        <select name="nivel_requerido[<?php echo $idHabilidad; ?>]" data-skill-level="<?php echo $idHabilidad; ?>">
                                            <option value="">Nivel</option>
                                            <option value="Básico" <?php echo ($nivelActual === 'Básico') ? 'selected' : ''; ?>>Básico</option>
                                            <option value="Intermedio" <?php echo ($nivelActual === 'Intermedio') ? 'selected' : ''; ?>>Intermedio</option>
                                            <option value="Avanzado" <?php echo ($nivelActual === 'Avanzado') ? 'selected' : ''; ?>>Avanzado</option>
                                        </select>

                                        <select name="obligatorio[<?php echo $idHabilidad; ?>]" data-skill-required="<?php echo $idHabilidad; ?>">
                                            <option value="0" <?php echo empty($obligatorioActual) ? 'selected' : ''; ?>>Opcional</option>
                                            <option value="1" <?php echo !empty($obligatorioActual) ? 'selected' : ''; ?>>Obligatoria</option>
                                        </select>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="skills-modal-overlay" id="skillsModal">
                        <div class="skills-modal">
                            <div class="skills-modal-head">
                                <div>
                                    <h3>Catálogo de habilidades</h3>
                                    <p>Selecciona habilidades adicionales para este desafío.</p>
                                </div>

                                <button type="button" class="skills-modal-close" id="closeSkillsModal">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>

                            <div class="skills-modal-search">
                                <i class="bi bi-search"></i>
                                <input type="text" id="buscarHabilidadModal" placeholder="Buscar en el catálogo...">
                            </div>

                            <div class="skills-modal-list" id="skillsModalList">
                                <?php foreach ($habilidades as $habilidad): ?>
                                    <?php
                                    $idHabilidad = (int) $habilidad['id_habilidad'];
                                    $seleccionada = in_array($idHabilidad, array_map('intval', $habilidadesOld), true);

                                    $nivelActual = $nivelesOld[$idHabilidad]
                                        ?? ($habilidadesAsignadas[$idHabilidad]['nivel_requerido'] ?? '');

                                    $obligatorioActual = $obligatoriosOld[$idHabilidad]
                                        ?? ($habilidadesAsignadas[$idHabilidad]['obligatorio'] ?? 0);
                                    ?>

                                    <div
                                        class="skill-modal-item"
                                        data-habilidad="<?php echo htmlspecialchars(strtolower($habilidad['nombre'])); ?>"
                                        data-categoria="<?php echo htmlspecialchars(strtolower($habilidad['categoria_habilidad'] ?? 'general')); ?>"
                                    >
                                        <label class="skill-modal-check">
                                            <input
                                                type="checkbox"
                                                name="habilidades[]"
                                                value="<?php echo $idHabilidad; ?>"
                                                data-skill-id="<?php echo $idHabilidad; ?>"
                                                <?php echo $seleccionada ? 'checked' : ''; ?>
                                            >

                                            <span>
                                                <strong><?php echo htmlspecialchars($habilidad['nombre']); ?></strong>
                                                <small><?php echo htmlspecialchars($habilidad['categoria_habilidad'] ?? 'General'); ?></small>
                                            </span>
                                        </label>

                                        <div class="skill-modal-options">
                                            <select name="nivel_requerido[<?php echo $idHabilidad; ?>]" data-skill-level="<?php echo $idHabilidad; ?>">
                                                <option value="">Nivel</option>
                                                <option value="Básico" <?php echo ($nivelActual === 'Básico') ? 'selected' : ''; ?>>Básico</option>
                                                <option value="Intermedio" <?php echo ($nivelActual === 'Intermedio') ? 'selected' : ''; ?>>Intermedio</option>
                                                <option value="Avanzado" <?php echo ($nivelActual === 'Avanzado') ? 'selected' : ''; ?>>Avanzado</option>
                                            </select>

                                            <select name="obligatorio[<?php echo $idHabilidad; ?>]" data-skill-required="<?php echo $idHabilidad; ?>">
                                                <option value="0" <?php echo empty($obligatorioActual) ? 'selected' : ''; ?>>Opcional</option>
                                                <option value="1" <?php echo !empty($obligatorioActual) ? 'selected' : ''; ?>>Obligatoria</option>
                                            </select>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="skills-modal-actions">
                                <button type="button" class="btn btn-primary" id="applySkillsModal">
                                    Listo
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="crear-actions">
                        <a href="detalle_desafio_organizacion.php?id=<?php echo (int) $desafio['id_desafio']; ?>" class="btn btn-nav">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<?php if (!empty($errorForm)): ?>
<script>
window.edunexoFormErrors = <?php echo json_encode($errorForm); ?>;
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/main.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.edunexoFormErrors && window.edunexoFormErrors.length > 0) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: 'Corrige los campos marcados',
            html: window.edunexoFormErrors.join('<br>'),
            showConfirmButton: false,
            timer: 4500,
            timerProgressBar: true,
            background: '#1f2937',
            color: '#ffffff'
        });
    }

    const inputs = document.querySelectorAll('input, textarea');
    inputs.forEach(input => {
        input.addEventListener('input', function () {
            this.classList.remove('input-error');
        });
    });

    const selects = document.querySelectorAll('select');
    selects.forEach(select => {
        select.addEventListener('change', function () {
            this.classList.remove('input-error');
        });
    });
});
</script>

<!Moadal>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('skillsModal');
    const openBtn = document.getElementById('openSkillsModal');
    const closeBtn = document.getElementById('closeSkillsModal');
    const applyBtn = document.getElementById('applySkillsModal');

    if (openBtn && modal) {
        openBtn.addEventListener('click', () => modal.classList.add('active'));
    }

    if (closeBtn && modal) {
        closeBtn.addEventListener('click', () => modal.classList.remove('active'));
    }

    if (applyBtn && modal) {
        applyBtn.addEventListener('click', () => modal.classList.remove('active'));
    }

    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });
    }

    const buscarPrincipal = document.getElementById('buscarHabilidad');
    const miniCards = document.querySelectorAll('.habilidad-mini-card');

    if (buscarPrincipal) {
        buscarPrincipal.addEventListener('input', function () {
            const value = this.value.toLowerCase().trim();

            miniCards.forEach(card => {
                const habilidad = card.dataset.habilidad || '';
                card.style.display = habilidad.includes(value) || value === '' ? '' : 'none';
            });
        });
    }

    const buscarModal = document.getElementById('buscarHabilidadModal');
    const modalItems = document.querySelectorAll('.skill-modal-item');

    if (buscarModal) {
        buscarModal.addEventListener('input', function () {
            const value = this.value.toLowerCase().trim();

            modalItems.forEach(item => {
                const habilidad = item.dataset.habilidad || '';
                const categoria = item.dataset.categoria || '';

                item.style.display =
                    habilidad.includes(value) || categoria.includes(value) || value === ''
                        ? ''
                        : 'none';
            });
        });
    }

    document.querySelectorAll('input[data-skill-id]').forEach(input => {
        input.addEventListener('change', function () {
            const skillId = this.dataset.skillId;
            const checked = this.checked;

            document.querySelectorAll('input[data-skill-id="' + skillId + '"]').forEach(other => {
                other.checked = checked;
            });
        });
    });

    document.querySelectorAll('select[data-skill-level]').forEach(select => {
        select.addEventListener('change', function () {
            const skillId = this.dataset.skillLevel;
            const value = this.value;

            document.querySelectorAll('select[data-skill-level="' + skillId + '"]').forEach(other => {
                other.value = value;
            });
        });
    });

    document.querySelectorAll('select[data-skill-required]').forEach(select => {
        select.addEventListener('change', function () {
            const skillId = this.dataset.skillRequired;
            const value = this.value;

            document.querySelectorAll('select[data-skill-required="' + skillId + '"]').forEach(other => {
                other.value = value;
            });
        });
    });
});
</script>

</body>
</html>