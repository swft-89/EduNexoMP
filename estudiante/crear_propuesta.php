<?php
require_once '../includes/session_estudiante.php';
require_once '../config/conexion.php';

$idUsuario = $_SESSION['usuario_id'];
$idDesafio = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($idDesafio <= 0) {
    header('Location: dashboard_estudiante.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        d.id_desafio,
        d.titulo,
        d.descripcion,
        o.nombre_empresa
    FROM desafio d
    INNER JOIN organizacion o
        ON d.id_organizacion = o.id_organizacion
    WHERE d.id_desafio = :id_desafio
    LIMIT 1
");
$stmt->execute([':id_desafio' => $idDesafio]);
$desafio = $stmt->fetch();

if (!$desafio) {
    header('Location: dashboard_estudiante.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT 1
    FROM propuesta
    WHERE id_estudiante = :id_estudiante
      AND id_desafio = :id_desafio
    LIMIT 1
");
$stmt->execute([
    ':id_estudiante' => $idUsuario,
    ':id_desafio' => $idDesafio
]);

if ($stmt->fetchColumn()) {
    $_SESSION['error'] = 'Ya enviaste una propuesta para este desafío.';
    header('Location: detalle_desafio.php?id=' . $idDesafio);
    exit;
}

$errorForm = $_SESSION['error_form'] ?? [];
$old = $_SESSION['old_propuesta'] ?? [];

unset($_SESSION['error_form'], $_SESSION['old_propuesta']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear propuesta - EduNexo MP</title>

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/estudiante/detalle_desafio.css">
    <link rel="stylesheet" href="../assets/css/dark.css?v=dark-fix-2">
    <link rel="stylesheet" href="../assets/css/estudiante/crear_propuesta.css">

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
                    <h1>Crear propuesta</h1>

                    <p>
                        <?php echo htmlspecialchars($desafio['titulo']); ?>
                        ·
                        <?php echo htmlspecialchars($desafio['nombre_empresa']); ?>
                    </p>
                </div>

                <div class="user-box">
                    <a href="detalle_desafio.php?id=<?php echo (int) $idDesafio; ?>" class="btn btn-nav">
                        Volver
                    </a>
                </div>
            </div>

            <div class="propuesta-card">

                <form action="../procesos/guardar_propuesta.php" method="POST" enctype="multipart/form-data" novalidate>

                    <input type="hidden" name="id_desafio" value="<?php echo (int) $idDesafio; ?>">

                    <div class="propuesta-form-grid">

                        <div class="form-group full">
                            <label for="titulo_propuesta">
                                Título de la propuesta
                            </label>

                            <input
                                type="text"
                                id="titulo_propuesta"
                                name="titulo_propuesta"
                                maxlength="150"
                                placeholder="Ejemplo: Sistema inteligente de análisis educativo"
                                value="<?php echo htmlspecialchars($old['titulo_propuesta'] ?? ''); ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="enlace_portafolio">
                                Portafolio / GitHub / Drive
                            </label>

                            <input
                                type="url"
                                id="enlace_portafolio"
                                name="enlace_portafolio"
                                placeholder="https://github.com/usuario/proyecto"
                                value="<?php echo htmlspecialchars($old['enlace_portafolio'] ?? ''); ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="descripcion_breve">
                                Descripción breve
                            </label>

                            <textarea
                                id="descripcion_breve"
                                name="descripcion_breve"
                                placeholder="Describe brevemente tu enfoque, experiencia o idea principal."
                            ><?php echo htmlspecialchars($old['descripcion_breve'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group full">
                            <label for="archivo_propuesta">
                                Archivo principal
                            </label>

                            <div class="upload-box">
                                <i class="bi bi-cloud-arrow-up"></i>

                                <p>
                                    Arrastra tu archivo aquí o selecciónalo desde tu dispositivo.
                                </p>

                                <input
                                    type="file"
                                    id="archivo_propuesta"
                                    name="archivo_propuesta"
                                    accept=".pdf,.doc,.docx,.ppt,.pptx"
                                >
                            </div>

                            <small class="upload-help">
                                Formatos permitidos:
                                PDF, DOC, DOCX, PPT, PPTX · Máximo 5 MB
                            </small>
                        </div>

                    </div>

                    <div class="propuesta-actions">

                        <a href="detalle_desafio.php?id=<?php echo (int) $idDesafio; ?>" class="btn btn-nav">
                            Cancelar
                        </a>

                        <button type="submit" class="btn btn-primary">
                            Enviar propuesta
                        </button>

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