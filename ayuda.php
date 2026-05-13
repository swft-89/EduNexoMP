<?php
session_start();
require_once 'config/conexion.php';
require_once 'includes/help_schema.php';

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
    LIMIT 8
");
$stmt->execute([':id_usuario' => $idUsuario]);
$misSugerencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

$sidebarLinks = [
    'estudiante' => [
        ['estudiante/dashboard_estudiante.php', 'bi-house-door', 'Inicio'],
        ['estudiante/mis_propuestas.php', 'bi-file-earmark-text', 'Mis propuestas'],
        ['estudiante/mis_favoritos.php', 'bi-heart', 'Favoritos'],
        ['estudiante/chat.php', 'bi-chat', 'Chat'],
        ['estudiante/habilidades_estudiante.php', 'bi-stars', 'Mis habilidades'],
        ['estudiante/editar_perfil_estudiante.php', 'bi-person', 'Perfil'],
    ],
    'organizacion' => [
        ['organizacion/dashboard_organizacion.php', 'bi-house-door', 'Inicio'],
        ['organizacion/desafios/crear_desafio.php', 'bi-plus-circle', 'Crear desafio'],
        ['organizacion/desafios/mis_desafios.php', 'bi-briefcase', 'Mis desafios'],
        ['organizacion/propuestas_organizacion.php', 'bi-file-earmark-text', 'Propuestas'],
        ['organizacion/chat_organizacion.php', 'bi-chat', 'Chat'],
        ['organizacion/editar_perfil_organizacion.php', 'bi-building', 'Perfil'],
    ],
    'administrador' => [
        ['superadmin/dashboard_superadmin.php', 'bi-bar-chart-line', 'Dashboard'],
        ['superadmin/usuarios/usuarios_superadmin.php', 'bi-people', 'Usuarios'],
        ['superadmin/usuarios/solicitudes_admin.php', 'bi-person-check', 'Solicitudes admin'],
        ['superadmin/reportes_superadmin.php', 'bi-clipboard-data', 'Reportes'],
        ['superadmin/desafio/desafios_superadmin.php', 'bi-file-earmark-text', 'Desafios'],
        ['superadmin/propuestas/propuestas_superadmin.php', 'bi-send', 'Propuestas'],
        ['superadmin/categorias_superadmin.php', 'bi-tags', 'Categorias'],
    ],
];

$links = $sidebarLinks[$rol] ?? [];

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
    <aside class="sidebar">
        <div class="sidebar-top">
            <div class="logo-mini"><i class="bi bi-mortarboard"></i></div>
            <span>EduNexo MP</span>
        </div>

        <nav class="sidebar-menu">
            <?php foreach ($links as $link): ?>
                <a href="<?php echo htmlspecialchars($link[0]); ?>">
                    <i class="bi <?php echo htmlspecialchars($link[1]); ?>"></i>
                    <?php echo htmlspecialchars($link[2]); ?>
                </a>
            <?php endforeach; ?>
            <a href="ayuda.php" class="active">
                <i class="bi bi-question-circle"></i>
                Ayuda
            </a>
        </nav>
    </aside>

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

        <section class="help-grid">
            <article class="help-card">
                <h2>Enviar solicitud</h2>
                <p>Usa este formulario para sugerir catalogos o para pedir ayuda general.</p>

                <form action="procesos/guardar_sugerencia_ayuda.php" method="POST" class="help-form">
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

                    <div class="help-field">
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
                <h2>Mis solicitudes recientes</h2>
                <p>Consulta el estado de lo que has enviado.</p>

                <?php if (!empty($misSugerencias)): ?>
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
                <?php else: ?>
                    <div class="help-empty">Aun no has enviado solicitudes.</div>
                <?php endif; ?>
            </article>
        </section>

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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
