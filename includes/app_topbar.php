<?php
$topbarUsuarioId = $_SESSION['usuario_id'] ?? null;
$topbarRol = $_SESSION['rol'] ?? '';
$topbarNombre = 'Usuario';
$topbarFoto = null;

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
            SELECT nombre, apellido_paterno
            FROM administrador
            WHERE id_admin = :id
            LIMIT 1
        ");
        $stmtTopbar->execute([':id' => $topbarUsuarioId]);
        $topbarPerfil = $stmtTopbar->fetch(PDO::FETCH_ASSOC);

        if ($topbarPerfil) {
            $topbarNombre = trim(($topbarPerfil['nombre'] ?? '') . ' ' . ($topbarPerfil['apellido_paterno'] ?? ''));
        }
    }
}

$topbarNombre = trim($topbarNombre) !== '' ? trim($topbarNombre) : 'Usuario';
$topbarInicial = strtoupper(substr($topbarNombre, 0, 1));
$topbarFotoSrc = edunexo_topbar_asset($topbarFoto);
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

    @media (max-width: 640px) {
        .app-topbar {
            margin: -20px -16px 24px;
            padding: 0 16px;
        }

        .org-content .app-topbar,
        .superadmin-content .app-topbar {
            margin: 0;
        }
    }
</style>
<div class="app-topbar">
    <div></div>

    <div class="app-topbar-right">
        <button class="app-icon-btn app-theme-btn" id="toggleTheme" type="button" title="Cambiar tema" aria-label="Cambiar tema">
            <i class="bi bi-moon-stars"></i>
        </button>

        <button class="app-icon-btn app-notification-btn" type="button" title="Notificaciones" aria-label="Notificaciones">
            <i class="bi bi-bell"></i>
        </button>

        <div class="app-avatar" title="<?php echo htmlspecialchars($topbarNombre); ?>">
            <?php if (!empty($topbarFotoSrc)): ?>
                <img src="<?php echo htmlspecialchars($topbarFotoSrc); ?>" alt="Foto de perfil">
            <?php else: ?>
                <?php echo htmlspecialchars($topbarInicial); ?>
            <?php endif; ?>
        </div>
    </div>
</div>
