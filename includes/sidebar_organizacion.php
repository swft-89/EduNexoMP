<?php
require_once __DIR__ . '/sidebar_helpers.php';

$orgSidebarItems = [
    [
        'href' => 'organizacion/dashboard_organizacion.php',
        'icon' => 'bi-house-door',
        'label' => 'Inicio',
        'active' => ['organizacion/dashboard_organizacion.php'],
    ],
    [
        'href' => 'organizacion/desafios/crear_desafio.php',
        'icon' => 'bi-plus-circle',
        'label' => 'Crear desafío',
        'active' => ['organizacion/desafios/crear_desafio.php'],
    ],
    [
        'href' => 'organizacion/desafios/mis_desafios.php',
        'icon' => 'bi-briefcase',
        'label' => 'Mis desafíos',
        'active' => [
            'organizacion/desafios/mis_desafios.php',
            'organizacion/desafios/editar_desafio.php',
            'organizacion/desafios/detalle_desafio_organizacion.php',
            'organizacion/desafios/talentos_desafio.php',
            'organizacion/habilidades_desafio.php',
        ],
    ],
    [
        'href' => 'organizacion/propuestas_organizacion.php',
        'icon' => 'bi-file-earmark-text',
        'label' => 'Propuestas',
        'active' => ['organizacion/propuestas_organizacion.php'],
    ],
    [
        'href' => 'organizacion/reportes_organizacion.php',
        'icon' => 'bi-bar-chart-line',
        'label' => 'Reportes',
        'active' => ['organizacion/reportes_organizacion.php'],
    ],
    [
        'href' => 'organizacion/chat_organizacion.php',
        'icon' => 'bi-chat',
        'label' => 'Chat',
        'active' => ['organizacion/chat_organizacion.php'],
    ],
    [
        'href' => 'organizacion/editar_perfil_organizacion.php',
        'icon' => 'bi-building',
        'label' => 'Perfil',
        'active' => ['organizacion/editar_perfil_organizacion.php'],
    ],
];
?>
<aside class="sidebar org-sidebar">
    <div class="sidebar-top">
        <div class="logo-mini"><i class="bi bi-mortarboard"></i></div>
        <span>EduNexo MP</span>
    </div>

    <nav class="sidebar-menu">
        <?php foreach ($orgSidebarItems as $item): ?>
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
