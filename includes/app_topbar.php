<?php
require_once __DIR__ . '/profile_photo.php';
$topbarUsuarioId = $_SESSION['usuario_id'] ?? null;
$topbarRol = $_SESSION['rol'] ?? '';
$topbarNombre = 'Usuario';
$topbarFoto = null;
$topbarNotificaciones = [];
$topbarNotificacionesNoLeidas = 0;

if (!function_exists('edunexo_topbar_asset')) {
    function edunexo_topbar_asset(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        $root = realpath(__DIR__ . '/..');
        $scriptDir = realpath(dirname($_SERVER['SCRIPT_FILENAME'] ?? '')) ?: $root;
        $relativeDir = $root && strpos($scriptDir, $root) === 0
            ? trim(substr($scriptDir, strlen($root)), DIRECTORY_SEPARATOR)
            : '';
        $depth = $relativeDir === '' ? 0 : substr_count($relativeDir, DIRECTORY_SEPARATOR) + 1;

        return str_repeat('../', $depth) . ltrim($path, '/');
    }
}

if ($topbarUsuarioId && isset($pdo)) {
    if ($topbarRol === 'estudiante') {
        $stmtTopbar = $pdo->prepare("
            SELECT nombre, apellido_paterno, foto_url
            FROM estudiante
            WHERE id_estudiante = :id
            LIMIT 1
        ");
        $stmtTopbar->execute([':id' => $topbarUsuarioId]);
        $topbarPerfil = $stmtTopbar->fetch(PDO::FETCH_ASSOC);

        if ($topbarPerfil) {
            $topbarNombre = trim(($topbarPerfil['nombre'] ?? '') . ' ' . ($topbarPerfil['apellido_paterno'] ?? ''));
            $topbarFoto = $topbarPerfil['foto_url'] ?? null;
        }
    } elseif ($topbarRol === 'organizacion') {
        $stmtTopbar = $pdo->prepare("
            SELECT nombre_empresa, foto_url
            FROM organizacion
            WHERE id_organizacion = :id
            LIMIT 1
        ");
        $stmtTopbar->execute([':id' => $topbarUsuarioId]);
        $topbarPerfil = $stmtTopbar->fetch(PDO::FETCH_ASSOC);

        if ($topbarPerfil) {
            $topbarNombre = $topbarPerfil['nombre_empresa'] ?? 'Organizacion';
            $topbarFoto = $topbarPerfil['foto_url'] ?? null;
        }
    } elseif ($topbarRol === 'administrador') {
        $stmtTopbar = $pdo->prepare("
            SELECT nombre, apellido_paterno, foto_url
            FROM administrador
            WHERE id_admin = :id
            LIMIT 1
        ");
        $stmtTopbar->execute([':id' => $topbarUsuarioId]);
        $topbarPerfil = $stmtTopbar->fetch(PDO::FETCH_ASSOC);

        if ($topbarPerfil) {
            $topbarNombre = trim(($topbarPerfil['nombre'] ?? '') . ' ' . ($topbarPerfil['apellido_paterno'] ?? ''));
            $topbarFoto = $topbarPerfil['foto_url'] ?? null;
        }
    }

    $stmtTopbarNotificaciones = $pdo->prepare("
        SELECT id_notificacion, tipo, mensaje, fecha_envio, leida
        FROM notificacion
        WHERE id_usuario = :id_usuario
        ORDER BY leida ASC, fecha_envio DESC, id_notificacion DESC
        LIMIT 8
    ");
    $stmtTopbarNotificaciones->execute([':id_usuario' => $topbarUsuarioId]);
    $topbarNotificaciones = $stmtTopbarNotificaciones->fetchAll(PDO::FETCH_ASSOC);

    $stmtTopbarNoLeidas = $pdo->prepare("
        SELECT COUNT(*)
        FROM notificacion
        WHERE id_usuario = :id_usuario
          AND COALESCE(leida, FALSE) = FALSE
    ");
    $stmtTopbarNoLeidas->execute([':id_usuario' => $topbarUsuarioId]);
    $topbarNotificacionesNoLeidas = (int) $stmtTopbarNoLeidas->fetchColumn();
}

$topbarNombre = trim($topbarNombre) !== '' ? trim($topbarNombre) : 'Usuario';
$topbarInicial = strtoupper(substr($topbarNombre, 0, 1));
$topbarFotoSrc = edunexo_asset_url($topbarFoto);
$topbarNotificacionUrl = edunexo_topbar_asset('procesos/ver_notificacion.php');
?>
<style>
    .app-topbar {
        height: 90px;
        background: #ffffff;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 28px;
        margin: -28px -28px 28px;
    }

    .org-content .app-topbar,
    .superadmin-content .app-topbar {
        margin: 0;
    }

    .app-topbar-right {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-left: auto;
        position: relative;
    }

    .app-icon-btn {
        width: 42px;
        height: 42px;
        border: none;
        border-radius: 50%;
        background: transparent;
        color: #334155;
        font-size: 1.25rem;
        cursor: pointer;
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
    }

    .app-icon-btn:hover {
        background: #eef2ff;
        color: var(--primary);
        transform: translateY(-1px);
    }

    .app-notification-btn::after {
        content: "";
        position: absolute;
        top: 8px;
        right: 8px;
        width: 10px;
        height: 10px;
        background: #10b981;
        border: 2px solid #ffffff;
        border-radius: 50%;
    }

    .app-notification-btn.has-unread::after {
        display: block;
    }

    .app-notification-btn:not(.has-unread)::after {
        display: none;
    }

    .app-notification-count {
        position: absolute;
        top: -3px;
        right: -3px;
        min-width: 19px;
        height: 19px;
        padding: 0 5px;
        border-radius: 999px;
        background: #ef4444;
        color: #ffffff;
        border: 2px solid #ffffff;
        font-size: 0.68rem;
        font-weight: 800;
        line-height: 15px;
        text-align: center;
    }

    .app-notification-menu {
        position: absolute;
        top: calc(100% + 14px);
        right: 62px;
        width: min(380px, calc(100vw - 32px));
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.16);
        overflow: hidden;
        z-index: 2500;
        display: none;
    }

    .app-notification-menu.is-open {
        display: block;
    }

    .app-notification-head {
        padding: 16px 18px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .app-notification-head strong {
        color: var(--text);
        font-size: 1rem;
    }

    .app-notification-head span {
        color: var(--text-soft);
        font-size: 0.86rem;
        font-weight: 700;
    }

    .app-notification-list {
        max-height: 420px;
        overflow-y: auto;
    }

    .app-notification-item {
        display: block;
        text-decoration: none;
        color: var(--text);
        padding: 14px 18px;
        border-bottom: 1px solid #eef2f7;
        transition: background 0.18s ease;
    }

    .app-notification-item:hover {
        background: #f8fafc;
    }

    .app-notification-item.unread {
        background: #eef4ff;
    }

    .app-notification-item p {
        margin: 0;
        color: var(--text);
        font-size: 0.92rem;
        line-height: 1.45;
    }

    .app-notification-item small {
        display: block;
        margin-top: 6px;
        color: var(--text-soft);
        font-size: 0.78rem;
        font-weight: 600;
    }

    .app-notification-empty {
        padding: 28px 18px;
        text-align: center;
        color: var(--text-soft);
        font-size: 0.92rem;
    }

    .app-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: var(--primary);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1.25rem;
        overflow: hidden;
    }

    .app-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    :root[data-theme="dark"] .app-topbar {
        background: #111827;
        border-bottom-color: #334155;
    }

    :root[data-theme="dark"] .app-icon-btn {
        color: var(--text);
    }

    :root[data-theme="dark"] .app-icon-btn:hover {
        background: #1e293b;
        color: var(--primary-light);
    }

    :root[data-theme="dark"] .app-notification-btn::after {
        border-color: #111827;
    }

    :root[data-theme="dark"] .app-notification-count {
        border-color: #111827;
    }

    :root[data-theme="dark"] .app-notification-menu {
        background: #1e293b;
        border-color: #334155;
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.45);
    }

    :root[data-theme="dark"] .app-notification-head,
    :root[data-theme="dark"] .app-notification-item {
        border-color: #334155;
    }

    :root[data-theme="dark"] .app-notification-item:hover,
    :root[data-theme="dark"] .app-notification-item.unread {
        background: #172033;
    }

    @media (max-width: 640px) {
        .app-topbar {
            margin: -20px -16px 24px;
            padding: 0 16px;
        }

        .org-content .app-topbar,
        .superadmin-content .app-topbar {
            margin: 0;
        }

        .app-notification-menu {
            right: 0;
        }
    }
</style>
<div class="app-topbar">
    <div></div>

    <div class="app-topbar-right">
        <button class="app-icon-btn app-theme-btn" id="toggleTheme" type="button" title="Cambiar tema" aria-label="Cambiar tema">
            <i class="bi bi-moon-stars"></i>
        </button>

        <button class="app-icon-btn app-notification-btn <?php echo $topbarNotificacionesNoLeidas > 0 ? 'has-unread' : ''; ?>" id="appNotificationToggle" type="button" title="Notificaciones" aria-label="Notificaciones" aria-expanded="false">
            <i class="bi bi-bell"></i>
            <?php if ($topbarNotificacionesNoLeidas > 0): ?>
                <span class="app-notification-count"><?php echo $topbarNotificacionesNoLeidas > 9 ? '9+' : $topbarNotificacionesNoLeidas; ?></span>
            <?php endif; ?>
        </button>

        <div class="app-notification-menu" id="appNotificationMenu">
            <div class="app-notification-head">
                <strong>Notificaciones</strong>
                <span><?php echo $topbarNotificacionesNoLeidas; ?> nuevas</span>
            </div>

            <div class="app-notification-list">
                <?php if (!empty($topbarNotificaciones)): ?>
                    <?php foreach ($topbarNotificaciones as $topbarNotificacion): ?>
                        <?php
                        $notificacionLeida = !empty($topbarNotificacion['leida']);
                        $notificacionFecha = !empty($topbarNotificacion['fecha_envio'])
                            ? date('d/m/Y H:i', strtotime($topbarNotificacion['fecha_envio']))
                            : '';
                        ?>
                        <a
                            class="app-notification-item <?php echo $notificacionLeida ? '' : 'unread'; ?>"
                            href="<?php echo htmlspecialchars($topbarNotificacionUrl . '?id=' . (int) $topbarNotificacion['id_notificacion']); ?>"
                        >
                            <p><?php echo htmlspecialchars(preg_replace('/\s*ID_[A-Z_]+:\d+/', '', $topbarNotificacion['mensaje'])); ?></p>
                            <?php if ($notificacionFecha !== ''): ?>
                                <small><?php echo htmlspecialchars($notificacionFecha); ?></small>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="app-notification-empty">No tienes notificaciones por ahora.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="app-avatar" title="<?php echo htmlspecialchars($topbarNombre); ?>">
            <?php if (!empty($topbarFotoSrc)): ?>
                <img src="<?php echo htmlspecialchars($topbarFotoSrc); ?>" alt="Foto de perfil">
            <?php else: ?>
                <?php echo htmlspecialchars($topbarInicial); ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('click', function (event) {
    const toggle = document.getElementById('appNotificationToggle');
    const menu = document.getElementById('appNotificationMenu');

    if (!toggle || !menu) {
        return;
    }

    if (event.target.closest('#appNotificationToggle')) {
        menu.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', menu.classList.contains('is-open') ? 'true' : 'false');
        return;
    }

    if (!event.target.closest('#appNotificationMenu')) {
        menu.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
    }
});
</script>
