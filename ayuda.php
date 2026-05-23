<?php
session_start();
require_once 'config/conexion.php';
require_once 'includes/help_schema.php';
require_once 'includes/csrf.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

edunexo_ensure_help_tables($pdo);

$idUsuario = (int) $_SESSION['usuario_id'];
$rol = $_SESSION['rol'] ?? '';
$esAdmin = $rol === 'administrador';
$old = $_SESSION['old_ayuda'] ?? [];
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['old_ayuda'], $_SESSION['success'], $_SESSION['error']);

$misSugerencias = [];
$sugerenciasPendientes = [];
$sugerenciasRecientes = [];

$stmt = $pdo->prepare("
    SELECT tipo, titulo, estado, fecha_creacion, fecha_revision, respuesta_admin
    FROM ayuda_sugerencia
    WHERE id_usuario = :id_usuario
    ORDER BY fecha_creacion DESC
    LIMIT 50
");
$stmt->execute([':id_usuario' => $idUsuario]);
$misSugerencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
$misSugerenciasPreview = array_slice($misSugerencias, 0, 3);

if ($esAdmin) {
    $stmt = $pdo->query("
        SELECT
            s.*,
            u.correo_electronico
        FROM ayuda_sugerencia s
        LEFT JOIN usuario u
            ON u.id_usuario = s.id_usuario
        WHERE s.estado = 'pendiente'
        ORDER BY s.fecha_creacion ASC
    ");
    $sugerenciasPendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("
        SELECT
            s.*,
            u.correo_electronico
        FROM ayuda_sugerencia s
        LEFT JOIN usuario u
            ON u.id_usuario = s.id_usuario
        WHERE s.estado <> 'pendiente'
        ORDER BY s.fecha_revision DESC NULLS LAST, s.fecha_creacion DESC
        LIMIT 8
    ");
    $sugerenciasRecientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function ayuda_tipo_label(string $tipo): string
{
    return [
        'habilidad' => 'Habilidad',
        'categoria' => 'Categoria',
        'ayuda_general' => 'Ayuda general',
    ][$tipo] ?? 'Sugerencia';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ayuda | EduNexo MP</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dark.css?v=dark-fix-7">
    <link rel="stylesheet" href="assets/css/ayuda.css?v=help-1">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
<div class="app-layout">
    <?php
    if (($rol ?? '') === 'estudiante') {
        include __DIR__ . '/includes/sidebar_estudiante.php';
    } elseif (($rol ?? '') === 'organizacion') {
        include __DIR__ . '/includes/sidebar_organizacion.php';
    } else {
        include __DIR__ . '/includes/sidebar_admin.php';
    }
    ?>

    <main class="app-content help-page">
        <?php include __DIR__ . '/includes/app_topbar.php'; ?>

        <section class="help-hero">
            <div>
                <span>Centro de ayuda</span>
                <h1>Ayuda y sugerencias</h1>
                <p>Reporta dudas, pide soporte o sugiere nuevas habilidades y categorias para los catalogos globales.</p>
            </div>
        </section>

        <?php if ($success): ?>
            <div class="help-alert success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="help-alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!$esAdmin): ?>
            <section class="help-grid">
                <article class="help-card">
                    <h2>Enviar solicitud</h2>
                    <p>Usa este formulario para sugerir catalogos o para pedir ayuda general.</p>

                    <form action="procesos/guardar_sugerencia_ayuda.php" method="POST" class="help-form">
                        <?php echo edunexo_csrf_input(); ?>
                        <div class="help-field">
                            <label for="tipo">Tipo</label>
                            <select id="tipo" name="tipo" required>
                                <option value="ayuda_general" <?php echo (($old['tipo'] ?? '') === 'ayuda_general') ? 'selected' : ''; ?>>Ayuda general</option>
                                <option value="habilidad" <?php echo (($old['tipo'] ?? '') === 'habilidad') ? 'selected' : ''; ?>>Sugerir habilidad</option>
                                <option value="categoria" <?php echo (($old['tipo'] ?? '') === 'categoria') ? 'selected' : ''; ?>>Sugerir categoria</option>
                            </select>
                        </div>

                        <div class="help-field">
                            <label for="titulo">Titulo</label>
                            <input type="text" id="titulo" name="titulo" maxlength="150" value="<?php echo htmlspecialchars($old['titulo'] ?? ''); ?>" required>
                        </div>

                        <div class="help-field help-skill-category-field" id="helpSkillCategoryField">
                            <label for="categoria_habilidad">Categoria de habilidad</label>
                            <input type="text" id="categoria_habilidad" name="categoria_habilidad" maxlength="100" placeholder="Solo aplica para sugerencias de habilidad" value="<?php echo htmlspecialchars($old['categoria_habilidad'] ?? ''); ?>">
                        </div>

                        <div class="help-field">
                            <label for="descripcion">Descripcion</label>
                            <textarea id="descripcion" name="descripcion" rows="6" maxlength="2000"><?php echo htmlspecialchars($old['descripcion'] ?? ''); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Enviar sugerencia</button>
                    </form>
                </article>

                <article class="help-card">
                    <div class="help-card-head">
                        <div>
                            <h2>Mis solicitudes recientes</h2>
                            <p>Consulta el estado de lo que has enviado.</p>
                        </div>

                        <?php if (count($misSugerencias) > 3): ?>
                            <button type="button" class="btn help-more-btn" id="openHelpHistory">
                                Mostrar mas
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($misSugerenciasPreview)): ?>
                        <div class="help-list">
                            <?php foreach ($misSugerenciasPreview as $sugerencia): ?>
                                <div class="help-item">
                                    <div>
                                        <span><?php echo htmlspecialchars(ayuda_tipo_label($sugerencia['tipo'])); ?></span>
                                        <strong><?php echo htmlspecialchars($sugerencia['titulo']); ?></strong>
                                        <?php if (!empty($sugerencia['respuesta_admin'])): ?>
                                            <p><?php echo htmlspecialchars($sugerencia['respuesta_admin']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <em class="status-<?php echo htmlspecialchars($sugerencia['estado']); ?>">
                                        <?php echo htmlspecialchars($sugerencia['estado']); ?>
                                    </em>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="help-empty">Aun no has enviado solicitudes.</div>
                    <?php endif; ?>
                </article>
            </section>
        <?php endif; ?>

        <?php if ($esAdmin): ?>
            <section class="help-admin">
                <div class="help-section-head">
                    <div>
                        <h2>Sugerencias pendientes</h2>
                        <p>Al aprobar una habilidad o categoria, se agrega automaticamente al catalogo correspondiente.</p>
                    </div>
                    <span><?php echo count($sugerenciasPendientes); ?> pendientes</span>
                </div>

                <?php if (!empty($sugerenciasPendientes)): ?>
                    <div class="help-admin-list">
                        <?php foreach ($sugerenciasPendientes as $sugerencia): ?>
                            <article class="help-review-card">
                                <div class="help-review-main">
                                    <span><?php echo htmlspecialchars(ayuda_tipo_label($sugerencia['tipo'])); ?></span>
                                    <h3><?php echo htmlspecialchars($sugerencia['titulo']); ?></h3>
                                    <?php if (!empty($sugerencia['categoria_habilidad'])): ?>
                                        <p><strong>Categoria:</strong> <?php echo htmlspecialchars($sugerencia['categoria_habilidad']); ?></p>
                                    <?php endif; ?>
                                    <p><?php echo nl2br(htmlspecialchars($sugerencia['descripcion'] ?: 'Sin descripcion adicional.')); ?></p>
                                    <small>Enviado por <?php echo htmlspecialchars($sugerencia['correo_electronico'] ?: 'usuario no disponible'); ?></small>
                                </div>

                                <form action="procesos/admin/revisar_sugerencia_ayuda.php" method="POST" class="help-review-form">
                                    <?php echo edunexo_csrf_input(); ?>
                                    <input type="hidden" name="id_sugerencia" value="<?php echo (int) $sugerencia['id_sugerencia']; ?>">
                                    <textarea name="respuesta_admin" rows="3" placeholder="Respuesta opcional para el usuario"></textarea>
                                    <div>
                                        <button type="submit" name="accion" value="aprobar" class="btn btn-primary">Aprobar</button>
                                        <button type="submit" name="accion" value="rechazar" class="btn btn-reject">Rechazar</button>
                                    </div>
                                </form>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="help-empty">No hay sugerencias pendientes.</div>
                <?php endif; ?>

                <?php if (!empty($sugerenciasRecientes)): ?>
                    <div class="help-section-head compact">
                        <div>
                            <h2>Revisadas recientemente</h2>
                            <p>Ultimas decisiones registradas.</p>
                        </div>
                    </div>

                    <div class="help-list">
                        <?php foreach ($sugerenciasRecientes as $sugerencia): ?>
                            <div class="help-item">
                                <div>
                                    <span><?php echo htmlspecialchars(ayuda_tipo_label($sugerencia['tipo'])); ?></span>
                                    <strong><?php echo htmlspecialchars($sugerencia['titulo']); ?></strong>
                                    <p><?php echo htmlspecialchars($sugerencia['correo_electronico'] ?: 'Usuario no disponible'); ?></p>
                                </div>
                                <em class="status-<?php echo htmlspecialchars($sugerencia['estado']); ?>">
                                    <?php echo htmlspecialchars($sugerencia['estado']); ?>
                                </em>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
</div>

<?php if (!$esAdmin && count($misSugerencias) > 3): ?>
    <div class="help-history-modal" id="helpHistoryModal" aria-hidden="true">
        <div class="help-history-backdrop" data-help-history-close></div>
        <section class="help-history-panel" role="dialog" aria-modal="true" aria-labelledby="helpHistoryTitle">
            <div class="help-history-head">
                <div>
                    <span>Historial</span>
                    <h2 id="helpHistoryTitle">Todas mis solicitudes</h2>
                </div>
                <button type="button" class="help-history-close" data-help-history-close aria-label="Cerrar historial">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="help-history-body">
                <div class="help-list">
                    <?php foreach ($misSugerencias as $sugerencia): ?>
                        <div class="help-item">
                            <div>
                                <span><?php echo htmlspecialchars(ayuda_tipo_label($sugerencia['tipo'])); ?></span>
                                <strong><?php echo htmlspecialchars($sugerencia['titulo']); ?></strong>
                                <?php if (!empty($sugerencia['respuesta_admin'])): ?>
                                    <p><?php echo htmlspecialchars($sugerencia['respuesta_admin']); ?></p>
                                <?php endif; ?>
                            </div>
                            <em class="status-<?php echo htmlspecialchars($sugerencia['estado']); ?>">
                                <?php echo htmlspecialchars($sugerencia['estado']); ?>
                            </em>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/main.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tipo = document.getElementById('tipo');
    const categoriaField = document.getElementById('helpSkillCategoryField');
    const categoriaInput = document.getElementById('categoria_habilidad');

    function toggleCategoriaField() {
        const mostrar = tipo && tipo.value === 'habilidad';

        if (categoriaField) {
            categoriaField.hidden = !mostrar;
        }

        if (!mostrar && categoriaInput) {
            categoriaInput.value = '';
        }
    }

    if (tipo) {
        tipo.addEventListener('change', toggleCategoriaField);
        toggleCategoriaField();
    }

    const historyModal = document.getElementById('helpHistoryModal');
    const openHistory = document.getElementById('openHelpHistory');
    const closeHistoryButtons = document.querySelectorAll('[data-help-history-close]');

    function closeHistory() {
        if (!historyModal) {
            return;
        }

        historyModal.classList.remove('is-open');
        historyModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('help-modal-open');
    }

    if (openHistory && historyModal) {
        openHistory.addEventListener('click', function () {
            historyModal.classList.add('is-open');
            historyModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('help-modal-open');
        });
    }

    closeHistoryButtons.forEach(button => {
        button.addEventListener('click', closeHistory);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeHistory();
        }
    });
});
</script>
</body>
</html>
