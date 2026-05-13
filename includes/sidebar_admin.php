<?php
require_once __DIR__ . '/sidebar_helpers.php';

$adminSidebarItems = [
    [
        'href' => 'superadmin/dashboard_superadmin.php',
        'icon' => 'bi-bar-chart-line',
        'label' => 'Dashboard',
        'active' => ['superadmin/dashboard_superadmin.php', 'admin/dashboard_admin.php'],
    ],
    [
        'href' => 'superadmin/usuarios/usuarios_superadmin.php',
        'icon' => 'bi-people',
        'label' => 'Usuarios',
        'active' => ['superadmin/usuarios/usuarios_superadmin.php', 'superadmin/usuarios/detalle_usuario_superadmin.php'],
    ],
    [
        'href' => 'superadmin/usuarios/solicitudes_admin.php',
        'icon' => 'bi-person-check',
        'label' => 'Solicitudes admin',
        'active' => ['superadmin/usuarios/solicitudes_admin.php'],
    ],
    [
        'href' => 'superadmin/reportes_superadmin.php',
        'icon' => 'bi-clipboard-data',
        'label' => 'Reportes',
        'active' => ['superadmin/reportes_superadmin.php'],
    ],
    [
        'href' => 'superadmin/desafio/desafios_superadmin.php',
        'icon' => 'bi-file-earmark-text',
        'label' => 'Desafíos',
        'active' => ['superadmin/desafio/desafios_superadmin.php', 'superadmin/desafio/detalle_desafio_superadmin.php'],
    ],
    [
        'href' => 'superadmin/propuestas/propuestas_superadmin.php',
        'icon' => 'bi-send',
        'label' => 'Propuestas',
        'active' => ['superadmin/propuestas/propuestas_superadmin.php', 'superadmin/propuestas/detalle_propuesta_superadmin.php'],
    ],
    [
        'href' => 'superadmin/categorias_superadmin.php',
        'icon' => 'bi-tags',
        'label' => 'Categorías',
        'active' => ['superadmin/categorias_superadmin.php'],
    ],
];
?>
<aside class="sidebar">
    <div class="sidebar-top">
        <div class="logo-mini"><i class="bi bi-mortarboard"></i></div>
        <span>EduNexo MP</span>
    </div>

    <nav class="sidebar-menu">
        <?php foreach ($adminSidebarItems as $item): ?>
            <a href="<?php echo htmlspecialchars(edunexo_sidebar_url($item['href'])); ?>"<?php echo edunexo_sidebar_active($item['active']); ?>>
                <i class="bi <?php echo htmlspecialchars($item['icon']); ?>"></i>
                <?php echo htmlspecialchars($item['label']); ?>
            </a>
        <?php endforeach; ?>

        <div class="sidebar-utility">
            <a href="<?php echo htmlspecialchars(edunexo_sidebar_url('ayuda.php')); ?>" class="sidebar-help<?php echo edunexo_sidebar_is_active(['ayuda.php']) ? ' active' : ''; ?>">
                <i class="bi bi-question-circle"></i>
                <span>Ayuda</span>
            </a>
            <a href="<?php echo htmlspecialchars(edunexo_sidebar_url('procesos/logout.php')); ?>" class="sidebar-logout">
                <i class="bi bi-box-arrow-right"></i>
                <span>Cerrar sesión</span>
            </a>
        </div>
    </nav>
</aside>
