<?php
require_once '../../includes/session_organizacion.php';
require_once '../../config/conexion.php';

$idUsuario = $_SESSION['usuario_id'];

$stmt = $pdo->query("
    SELECT id_categoria, nombre_categoria
    FROM categoria
    ORDER BY nombre_categoria ASC
");
$categorias = $stmt->fetchAll();

$errorForm = $_SESSION['error_form'] ?? [];
$errorFields = $_SESSION['error_fields'] ?? [];
$old = $_SESSION['old_crear_desafio'] ?? [];

unset($_SESSION['error_form'], $_SESSION['error_fields'], $_SESSION['old_crear_desafio']);

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear desafío - EduNexo MP</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/org/crear_desafio.css">
    <link rel="stylesheet" href="../../assets/css/org/dashboard_organizacion.css">
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
            <a href="crear_desafio.php" class="active">
                <i class="bi bi-plus-circle"></i> Crear desafío
            </a>
            <a href="../mis_desafios.php">
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
        <div class="org-topbar">
            <div></div>
            <div class="org-topbar-right">
                <button class="org-icon-btn" type="button">
                    <i class="bi bi-bell"></i>
                </button>
                <div class="org-avatar">
                    <?php echo htmlspecialchars(strtoupper(substr($_SESSION['rol'], 0, 1))); ?>
                </div>
            </div>
        </div>

        <div class="crear-desafio-wrap">
            <div class="crear-desafio-head">
                <div>
                    <h1>Crear nuevo desafío</h1>
                    <p>Publica una oportunidad para conectar con talento estudiantil</p>
                </div>
                <a href="dashboard_organizacion.php" class="btn btn-nav">Volver</a>
            </div>

            <div class="crear-desafio-card">
                <form action="../../procesos/guardar_desafio.php" method="POST" class="crear-desafio-form" novalidate>
                    <div class="form-grid-2">

                        <div class="form-group full">
                            <label for="titulo">Título del desafío</label>
                            <input
                                type="text"
                                id="titulo"
                                name="titulo"
                                maxlength="150"
                                value="<?php echo htmlspecialchars($old['titulo'] ?? ''); ?>"
                                class="<?php echo !empty($errorFields['titulo']) ? 'input-error' : ''; ?>"
                            >
                        </div>

                        <div class="form-group full">
                            <label for="descripcion">Descripción</label>
                            <textarea
                                id="descripcion"
                                name="descripcion"
                                rows="5"
                                class="<?php echo !empty($errorFields['descripcion']) ? 'input-error' : ''; ?>"
                            ><?php echo htmlspecialchars($old['descripcion'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group full">
                            <label for="requisitos_especificos">Requisitos específicos</label>
                            <textarea
                                id="requisitos_especificos"
                                name="requisitos_especificos"
                                rows="4"
                            ><?php echo htmlspecialchars($old['requisitos_especificos'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="id_categoria">Categoría</label>
                            <select
                                id="id_categoria"
                                name="id_categoria"
                                class="<?php echo !empty($errorFields['id_categoria']) ? 'input-error' : ''; ?>"
                            >
                                <option value="">Selecciona una categoría</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo (int) $categoria['id_categoria']; ?>"
                                        <?php echo ((int)($old['id_categoria'] ?? 0) === (int)$categoria['id_categoria']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($categoria['nombre_categoria']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="modalidad">Modalidad</label>
                            <select id="modalidad" name="modalidad">
                                <option value="">Selecciona una modalidad</option>
                                <option value="Presencial" <?php echo (($old['modalidad'] ?? '') === 'Presencial') ? 'selected' : ''; ?>>Presencial</option>
                                <option value="Remoto" <?php echo (($old['modalidad'] ?? '') === 'Remoto') ? 'selected' : ''; ?>>Remoto</option>
                                <option value="Híbrido" <?php echo (($old['modalidad'] ?? '') === 'Híbrido') ? 'selected' : ''; ?>>Híbrido</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="fecha_limite">Fecha límite</label>
                            <input
                                type="date"
                                id="fecha_limite"
                                name="fecha_limite"
                                value="<?php echo htmlspecialchars($old['fecha_limite'] ?? ''); ?>"
                                class="<?php echo !empty($errorFields['fecha_limite']) ? 'input-error' : ''; ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="estado">Estado</label>
                            <select
                                id="estado"
                                name="estado"
                                class="<?php echo !empty($errorFields['estado']) ? 'input-error' : ''; ?>"
                            >
                                <option value="activo" <?php echo (($old['estado'] ?? 'activo') === 'activo') ? 'selected' : ''; ?>>Activo</option>
                                <option value="cerrado" <?php echo (($old['estado'] ?? '') === 'cerrado') ? 'selected' : ''; ?>>Cerrado</option>
                            </select>
                        </div>

                    </div>

                    <div class="crear-actions">
                        <a href="../dashboard_organizacion.php" class="btn btn-nav">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Publicar desafío</button>
                    </div>
                </form>
            </div>
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
<script src="../../assets/js/main.js"></script>
<?php if ($success): ?>
<script>
    window.edunexoSuccess = <?php echo json_encode($success); ?>;
</script>
<?php endif; ?>

<?php if (!empty($errorForm)): ?>
<script>
    window.edunexoFormErrors = <?php echo json_encode($errorForm); ?>;
</script>
<?php endif; ?>


<script>
document.addEventListener('DOMContentLoaded', function () {


    // Inputs y textareas
    const inputs = document.querySelectorAll('input, textarea');

    inputs.forEach(input => {
        input.addEventListener('input', function () {
            if (this.classList.contains('input-error')) {
                this.classList.remove('input-error');
            }
        });
    });

    // Selects
    const selects = document.querySelectorAll('select');

    selects.forEach(select => {
        select.addEventListener('change', function () {
            if (this.classList.contains('input-error')) {
                this.classList.remove('input-error');
            }
        });
    });

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

    if (window.edunexoSuccess) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: window.edunexoSuccess,
            showConfirmButton: false,
            timer: 3000
        });
    }

});
</script>

</body>
</html>