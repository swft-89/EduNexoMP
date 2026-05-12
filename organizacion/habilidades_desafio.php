<?php
require_once '../includes/session_organizacion.php';
require_once '../config/conexion.php';

$idUsuario = $_SESSION['usuario_id'];
$idDesafio = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($idDesafio <= 0) {
    header('Location: dashboard_organizacion.php');
    exit;
}

/* Validar que el desafío pertenezca a la organización */
$stmt = $pdo->prepare("
    SELECT id_desafio, titulo
    FROM desafio
    WHERE id_desafio = :id_desafio
      AND id_organizacion = :id_organizacion
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

/* Habilidades disponibles */
$stmt = $pdo->query("
    SELECT id_habilidad, nombre, categoria_habilidad
    FROM habilidad
    ORDER BY categoria_habilidad ASC, nombre ASC
");
$habilidades = $stmt->fetchAll();

/* Habilidades ya asignadas */
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
    $habilidadesAsignadas[$row['id_habilidad']] = [
        'nivel_requerido' => $row['nivel_requerido'],
        'obligatorio' => $row['obligatorio']
    ];
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
    <title>Habilidades del desafío - EduNexo MP</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/org/crear_desafio.css">
    <link rel="stylesheet" href="../assets/css/dark.css">
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
            <a href="dashboard_organizacion.php">
                <i class="bi bi-house-door"></i> Inicio
            </a>
            <a href="crear_desafio.php">
                <i class="bi bi-plus-circle"></i> Crear desafío
            </a>
            <a href="mis_desafios.php" class="active">
                <i class="bi bi-briefcase"></i> Mis desafíos
            </a>
            <a href="propuestas_recibidas.php">
                <i class="bi bi-file-earmark-text"></i> Propuestas
            </a>
            <a href="chat_organizacion.php">
                <i class="bi bi-chat"></i> Chat
            </a>
            <a href="editar_perfil_organizacion.php">
                <i class="bi bi-building"></i> Perfil
            </a>
        </nav>
    </aside>

    <section class="app-content org-content">
        <?php include __DIR__ . '/../includes/app_topbar.php'; ?>
        <div class="crear-desafio-wrap">
            <div class="crear-desafio-head">
                <div>
                    <h1>Habilidades del desafío</h1>
                    <p><?php echo htmlspecialchars($desafio['titulo']); ?></p>
                </div>
                <a href="detalle_desafio_organizacion.php?id=<?php echo (int) $desafio['id_desafio']; ?>" class="btn btn-nav">Volver</a>
            </div>

            <div class="crear-desafio-card">
                <form action="../procesos/guardar_habilidades_desafio.php" method="POST">
                    <input type="hidden" name="id_desafio" value="<?php echo (int) $desafio['id_desafio']; ?>">

                    <div class="form-grid-2">
                        <?php foreach ($habilidades as $habilidad): ?>
                            <?php
                            $idHabilidad = (int) $habilidad['id_habilidad'];
                            $asignada = $habilidadesAsignadas[$idHabilidad] ?? null;
                            ?>
                            <div class="form-group full habilidad-card-item">
                                <label style="display:flex; align-items:center; gap:10px;">
                                    <input
                                        type="checkbox"
                                        name="habilidades[]"
                                        value="<?php echo $idHabilidad; ?>"
                                        <?php echo $asignada ? 'checked' : ''; ?>
                                    >
                                    <span>
                                        <strong><?php echo htmlspecialchars($habilidad['nombre']); ?></strong>
                                        <small style="display:block; color:var(--text-soft);">
                                            <?php echo htmlspecialchars($habilidad['categoria_habilidad'] ?? 'General'); ?>
                                        </small>
                                    </span>
                                </label>

                                <div class="form-grid-2" style="margin-top:12px;">
                                    <div class="form-group">
                                        <label for="nivel_<?php echo $idHabilidad; ?>">Nivel requerido</label>
                                        <select name="nivel_requerido[<?php echo $idHabilidad; ?>]" id="nivel_<?php echo $idHabilidad; ?>">
                                            <option value="">No especificado</option>
                                            <option value="Básico" <?php echo (($asignada['nivel_requerido'] ?? '') === 'Básico') ? 'selected' : ''; ?>>Básico</option>
                                            <option value="Intermedio" <?php echo (($asignada['nivel_requerido'] ?? '') === 'Intermedio') ? 'selected' : ''; ?>>Intermedio</option>
                                            <option value="Avanzado" <?php echo (($asignada['nivel_requerido'] ?? '') === 'Avanzado') ? 'selected' : ''; ?>>Avanzado</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="obligatorio_<?php echo $idHabilidad; ?>">Obligatoria</label>
                                        <select name="obligatorio[<?php echo $idHabilidad; ?>]" id="obligatorio_<?php echo $idHabilidad; ?>">
                                            <option value="0" <?php echo (empty($asignada['obligatorio'])) ? 'selected' : ''; ?>>No</option>
                                            <option value="1" <?php echo (!empty($asignada['obligatorio'])) ? 'selected' : ''; ?>>Sí</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="crear-actions">
                        <a href="./desafios/detalle_desafio_organizacion.php?id=<?php echo (int) $desafio['id_desafio']; ?>" class="btn btn-nav">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Guardar habilidades</button>
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
<script src="../assets/js/main.js"></script>
</body>
</html>