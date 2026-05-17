<?php
require_once __DIR__ . '/profile_photo.php';
$topbarUsuarioId = $_SESSION['usuario_id'] ?? null;
$topbarRol = $_SESSION['rol'] ?? '';
$topbarNombre = 'Usuario';
$topbarCorreo = $_SESSION['correo'] ?? '';
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
$topbarLogoutUrl = edunexo_topbar_asset('procesos/logout.php');
$topbarHelpUrl = edunexo_topbar_asset('ayuda.php');
$topbarRolLabel = [
    'estudiante' => 'Estudiante',
    'organizacion' => 'Organizacion',
    'administrador' => 'Administrador',
][$topbarRol] ?? 'Usuario';
$topbarPerfilPath = [
    'estudiante' => 'estudiante/perfil_estudiante.php',
    'organizacion' => 'organizacion/perfil_organizacion.php',
    'administrador' => 'admin/perfil_admin.php',
][$topbarRol] ?? '';
$topbarPerfilUrl = $topbarPerfilPath !== '' ? edunexo_topbar_asset($topbarPerfilPath) : '';
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

    body.org-modal-open .app-topbar,
    body.modal-open .app-topbar,
    body.skills-modal-open .app-topbar,
    body:has(.org-propuesta-modal.is-open) .app-topbar,
    body:has(.propuesta-modal-overlay.active) .app-topbar,
    body:has(.skills-modal-overlay.active) .app-topbar,
    body:has(.student-skills-modal-overlay.active) .app-topbar,
    body:has(.modal-overlay.active) .app-topbar,
    body:has(.help-history-modal.is-open) .app-topbar {
        z-index: 1 !important;
    }

    .org-content .app-topbar,
    .superadmin-content .app-topbar {
        margin: 0;
    }

    .org-page-content .app-topbar {
        margin: -28px -28px 28px;
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
        content: none !important;
        display: none !important;
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
        z-index: 1300;
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

    .sidebar-notification-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.18);
        margin-left: auto;
        flex: 0 0 auto;
    }

    .app-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        border: none;
        background: var(--primary);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1.25rem;
        overflow: hidden;
        cursor: pointer;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }

    .app-avatar:hover,
    .app-avatar:focus-visible {
        box-shadow: 0 0 0 4px rgba(39, 72, 166, 0.14);
        outline: none;
        transform: translateY(-1px);
    }

    .app-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .app-user-menu {
        position: absolute;
        top: calc(100% + 14px);
        right: 0;
        width: min(310px, calc(100vw - 32px));
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.16);
        overflow: hidden;
        z-index: 1300;
        display: none;
    }

    .app-user-menu.is-open {
        display: block;
    }

    .app-user-card {
        padding: 18px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 1px solid #e5e7eb;
    }

    .app-user-card .app-avatar {
        width: 54px;
        height: 54px;
        flex: 0 0 54px;
        cursor: default;
        transform: none;
    }

    .app-user-card .app-avatar:hover {
        box-shadow: none;
    }

    .app-user-info {
        min-width: 0;
    }

    .app-user-info strong,
    .app-user-info span,
    .app-user-info small {
        display: block;
    }

    .app-user-info strong {
        color: var(--text);
        font-size: 0.98rem;
        line-height: 1.25;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .app-user-info span {
        margin-top: 4px;
        color: var(--text-soft);
        font-size: 0.84rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .app-user-info small {
        margin-top: 8px;
        width: fit-content;
        padding: 4px 9px;
        border-radius: 999px;
        background: #eef2ff;
        color: var(--primary);
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0;
    }

    .app-user-actions {
        padding: 12px;
        display: grid;
        gap: 8px;
    }

    .app-user-action {
        min-height: 42px;
        padding: 0 12px;
        border-radius: 10px;
        color: var(--text);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.92rem;
        font-weight: 700;
        transition: background 0.18s ease, color 0.18s ease;
    }

    .app-user-action:hover {
        background: #f1f5f9;
        color: var(--primary);
    }

    .app-user-action.logout {
        color: #dc2626;
    }

    .app-user-action.logout:hover {
        background: #fef2f2;
        color: #b91c1c;
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

    :root[data-theme="dark"] .app-notification-count {
        border-color: #111827;
    }

    :root[data-theme="dark"] .app-notification-menu {
        background: #1e293b;
        border-color: #334155;
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.45);
    }

    :root[data-theme="dark"] .app-user-menu {
        background: #1e293b;
        border-color: #334155;
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.45);
    }

    :root[data-theme="dark"] .app-notification-head,
    :root[data-theme="dark"] .app-notification-item,
    :root[data-theme="dark"] .app-user-card {
        border-color: #334155;
    }

    :root[data-theme="dark"] .app-notification-item:hover,
    :root[data-theme="dark"] .app-notification-item.unread {
        background: #172033;
    }

    :root[data-theme="dark"] .app-user-info small {
        background: #172033;
        color: var(--primary-light);
    }

    :root[data-theme="dark"] .app-user-action:hover {
        background: #172033;
        color: var(--primary-light);
    }

    :root[data-theme="dark"] .app-user-action.logout:hover {
        background: rgba(220, 38, 38, 0.14);
        color: #fca5a5;
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

        .org-page-content .app-topbar {
            margin: -20px -16px 24px;
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

        <button class="app-avatar" id="appUserToggle" type="button" title="<?php echo htmlspecialchars($topbarNombre); ?>" aria-label="Abrir menu de usuario" aria-expanded="false">
            <?php if (!empty($topbarFotoSrc)): ?>
                <img src="<?php echo htmlspecialchars($topbarFotoSrc); ?>" alt="Foto de perfil">
            <?php else: ?>
                <?php echo htmlspecialchars($topbarInicial); ?>
            <?php endif; ?>
        </button>

        <div class="app-user-menu" id="appUserMenu">
            <div class="app-user-card">
                <div class="app-avatar" aria-hidden="true">
                    <?php if (!empty($topbarFotoSrc)): ?>
                        <img src="<?php echo htmlspecialchars($topbarFotoSrc); ?>" alt="">
                    <?php else: ?>
                        <?php echo htmlspecialchars($topbarInicial); ?>
                    <?php endif; ?>
                </div>

                <div class="app-user-info">
                    <strong><?php echo htmlspecialchars($topbarNombre); ?></strong>
                    <span><?php echo htmlspecialchars($topbarCorreo !== '' ? $topbarCorreo : 'Sin correo registrado'); ?></span>
                    <small><?php echo htmlspecialchars($topbarRolLabel); ?></small>
                </div>
            </div>

            <div class="app-user-actions">
                <?php if ($topbarPerfilUrl !== ''): ?>
                    <a class="app-user-action" href="<?php echo htmlspecialchars($topbarPerfilUrl); ?>">
                        <i class="bi bi-pencil-square"></i>
                        Editar perfil
                    </a>
                <?php endif; ?>

                <a class="app-user-action logout" href="<?php echo htmlspecialchars($topbarLogoutUrl); ?>">
                    <i class="bi bi-box-arrow-right"></i>
                    Cerrar sesion
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('click', function (event) {
    const notificationToggle = document.getElementById('appNotificationToggle');
    const notificationMenu = document.getElementById('appNotificationMenu');
    const userToggle = document.getElementById('appUserToggle');
    const userMenu = document.getElementById('appUserMenu');

    if (notificationToggle && notificationMenu && event.target.closest('#appNotificationToggle')) {
        notificationMenu.classList.toggle('is-open');
        notificationToggle.setAttribute('aria-expanded', notificationMenu.classList.contains('is-open') ? 'true' : 'false');

        if (userMenu && userToggle) {
            userMenu.classList.remove('is-open');
            userToggle.setAttribute('aria-expanded', 'false');
        }

        return;
    }

    if (userToggle && userMenu && event.target.closest('#appUserToggle')) {
        userMenu.classList.toggle('is-open');
        userToggle.setAttribute('aria-expanded', userMenu.classList.contains('is-open') ? 'true' : 'false');

        if (notificationMenu && notificationToggle) {
            notificationMenu.classList.remove('is-open');
            notificationToggle.setAttribute('aria-expanded', 'false');
        }

        return;
    }

    if (notificationToggle && notificationMenu && !event.target.closest('#appNotificationMenu')) {
        notificationMenu.classList.remove('is-open');
        notificationToggle.setAttribute('aria-expanded', 'false');
    }

    if (userToggle && userMenu && !event.target.closest('#appUserMenu')) {
        userMenu.classList.remove('is-open');
        userToggle.setAttribute('aria-expanded', 'false');
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const unreadNotifications = <?php echo (int) $topbarNotificacionesNoLeidas; ?>;
    const sidebarTop = document.querySelector('.sidebar-top');
    const sidebarMenu = document.querySelector('.sidebar-menu');

    if (unreadNotifications > 0 && sidebarTop && !sidebarTop.querySelector('.sidebar-notification-dot')) {
        const dot = document.createElement('span');
        dot.className = 'sidebar-notification-dot';
        dot.title = unreadNotifications + ' notificaciones nuevas';
        sidebarTop.appendChild(dot);
    }

    if (sidebarMenu && !sidebarMenu.querySelector('.sidebar-help')) {
        const utility = document.createElement('div');
        utility.className = 'sidebar-utility';

        const help = document.createElement('a');
        help.className = 'sidebar-help';
        help.href = <?php echo json_encode($topbarHelpUrl); ?>;
        help.innerHTML = '<i class="bi bi-question-circle"></i><span>Ayuda</span>';
        if (window.location.pathname.replace(/\\/g, '/').endsWith('/ayuda.php')) {
            help.classList.add('active');
        }
        utility.appendChild(help);

        if (!sidebarMenu.querySelector('.sidebar-logout')) {
            const logout = document.createElement('a');
            logout.className = 'sidebar-logout';
            logout.href = <?php echo json_encode($topbarLogoutUrl); ?>;
            logout.innerHTML = '<i class="bi bi-box-arrow-right"></i><span>Cerrar sesion</span>';
            utility.appendChild(logout);
        }

        const existingBottom = sidebarMenu.querySelector('.sidebar-bottom');
        if (existingBottom) {
            sidebarMenu.insertBefore(utility, existingBottom);
        } else {
            sidebarMenu.appendChild(utility);
        }
    }
});
</script>
