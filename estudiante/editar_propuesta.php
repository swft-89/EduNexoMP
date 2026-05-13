<?php
require_once '../includes/session_estudiante.php';
require_once '../config/conexion.php';
require_once '../includes/propuesta_utils.php';

$idUsuario = (int) $_SESSION['usuario_id'];
$idPropuesta = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($idPropuesta <= 0) {
    header('Location: mis_propuestas.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        p.id_propuesta,
        p.estado,
        p.fecha_respuesta,
        p.titulo_propuesta,
        p.descripcion_breve,
        p.enlace_portafolio,
        d.id_desafio,
        d.titulo AS titulo_desafio,
        o.nombre_empresa,
        dp.nombre_archivo,
        dp.url_archivo
    FROM propuesta p
    INNER JOIN desafio d
        ON p.id_desafio = d.id_desafio
    INNER JOIN organizacion o
        ON d.id_organizacion = o.id_organizacion
    LEFT JOIN documento_propuesta dp
        ON p.id_propuesta = dp.id_propuesta
    WHERE p.id_propuesta = :id_propuesta
      AND p.id_estudiante = :id_estudiante
    LIMIT 1
");
$stmt->execute([
    ':id_propuesta' => $idPropuesta,
    ':id_estudiante' => $idUsuario
]);
$propuesta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$propuesta) {
    $_SESSION['error'] = 'No se encontrÃ³ la propuesta.';
    header('Location: mis_propuestas.php');
    exit;
}

if (!edunexo_propuesta_editable($propuesta['estado'] ?? '') || !empty($propuesta['fecha_respuesta'])) {
    $_SESSION['error'] = 'Solo puedes editar propuestas que aÃºn estÃ¡n en revisiÃ³n.';
    header('Location: mis_propuestas.php');
    exit;
}

$errorForm = $_SESSION['error_form'] ?? [];
$old = $_SESSION['old_propuesta_edit'] ?? [];
unset($_SESSION['error_form'], $_SESSION['old_propuesta_edit']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar propuesta - EduNexo MP</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/estudiante/detalle_desafio.css">
    <link rel="stylesheet" href="../assets/css/estudiante/crear_propuesta.css">
    <link rel="stylesheet" href="../assets/css/dark.css?v=dark-fix-6">
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
            <a href="mis_propuestas.php" class="active">
                <i class="bi bi-file-earmark-text"></i> Mis propuestas
            </a>
            <a href="mis_favoritos.php">
                <i class="bi bi-heart"></i> Favoritos
            </a>
            <a href="chat.php">
                <i class="bi bi-chat"></i> Chat
            </a>
            <a href="editar_perfil_estudiante.php">
                <i class="bi bi-person"></i> Perfil
            </a>
        </nav>
    </aside>

    <section class="app-content">
        <?php include __DIR__ . '/../includes/app_topbar.php'; ?>
        <div class="propuesta-wrap">
            <div class="propuesta-header">
                <div>
                    <h1>Editar propuesta</h1>
                    <p>
                        <?php echo htmlspecialchars($propuesta['titulo_desafio']); ?>
                        Â·
                        <?php echo htmlspecialchars($propuesta['nombre_empresa']); ?>
                    </p>
                </div>

                <div class="user-box">
                    <a href="mis_propuestas.php" class="btn btn-nav">Volver</a>
                </div>
            </div>

            <div class="propuesta-card">
                <form action="../procesos/estudiante/actualizar_propuesta.php" method="POST" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="id_propuesta" value="<?php echo (int) $idPropuesta; ?>">

                    <div class="propuesta-form-grid">
                        <div class="form-group full">
                            <label for="titulo_propuesta">TÃ­tulo de la propuesta</label>
                            <input
                                type="text"
                                id="titulo_propuesta"
                                name="titulo_propuesta"
                                maxlength="150"
                                value="<?php echo htmlspecialchars($old['titulo_propuesta'] ?? $propuesta['titulo_propuesta'] ?? ''); ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="enlace_portafolio">Portafolio / GitHub / Drive</label>
                            <input
                                type="url"
                                id="enlace_portafolio"
                                name="enlace_portafolio"
                                value="<?php echo htmlspecialchars($old['enlace_portafolio'] ?? $propuesta['enlace_portafolio'] ?? ''); ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="descripcion_breve">DescripciÃ³n breve</label>
                            <textarea id="descripcion_breve" name="descripcion_breve"><?php echo htmlspecialchars($old['descripcion_breve'] ?? $propuesta['descripcion_breve'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group full">
                            <label for="archivo_propuesta">Reemplazar archivo</label>

                            <?php if (!empty($propuesta['nombre_archivo'])): ?>
                                <small class="upload-help">
                                    Archivo actual: <?php echo htmlspecialchars($propuesta['nombre_archivo']); ?>
                                </small>
                            <?php endif; ?>

                            <div class="upload-box">
                                <i class="bi bi-cloud-arrow-up"></i>
                                <p>Sube un nuevo PDF, PPT o PPTX si quieres reemplazar el archivo actual.</p>
                                <input type="file" id="archivo_propuesta" name="archivo_propuesta" accept=".pdf,.ppt,.pptx">
                            </div>

                            <small class="upload-help">Formatos permitidos: PDF, PPT, PPTX · MÃ¡ximo 5 MB</small>
                        </div>
                    </div>

                    <div class="propuesta-actions">
                        <a href="mis_propuestas.php" class="btn btn-nav">Cancelar</a>
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
<script src="../assets/js/main.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.edunexoFormErrors && window.edunexoFormErrors.length > 0) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: 'Revisa tu propuesta',
            html: window.edunexoFormErrors.join('<br>'),
            showConfirmButton: false,
            timer: 4500,
            timerProgressBar: true,
            background: '#1f2937',
            color: '#ffffff'
        });
    }
});
</script>
</body>
</html>
