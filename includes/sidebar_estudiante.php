<?php
require_once __DIR__ . '/sidebar_helpers.php';

$studentSidebarItems = [
    [
        'href' => 'estudiante/dashboard_estudiante.php',
        'icon' => 'bi-house-door',
        'label' => 'Inicio',
        'active' => ['estudiante/dashboard_estudiante.php'],
    ],
    [
        'href' => 'estudiante/mis_propuestas.php',
        'icon' => 'bi-file-earmark-text',
        'label' => 'Mis propuestas',
        'active' => ['estudiante/mis_propuestas.php', 'estudiante/crear_propuesta.php', 'estudiante/editar_propuesta.php'],
    ],
    [
        'href' => 'estudiante/mis_favoritos.php',
        'icon' => 'bi-heart',
        'label' => 'Favoritos',
        'active' => ['estudiante/mis_favoritos.php'],
    ],
    [
        'href' => 'estudiante/chat.php',
        'icon' => 'bi-chat',
        'label' => 'Chat',
        'active' => ['estudiante/chat.php'],
    ],
    [
        'href' => 'estudiante/habilidades_estudiante.php',
        'icon' => 'bi-stars',
        'label' => 'Mis habilidades',
        'active' => ['estudiante/habilidades_estudiante.php', 'estudiante/editar_habilidades_estudiante.php'],
    ],
    [
        'href' => 'estudiante/editar_perfil_estudiante.php',
        'icon' => 'bi-person',
        'label' => 'Perfil',
        'active' => ['estudiante/editar_perfil_estudiante.php'],
    ],
];
?>
<aside class="sidebar">
    <div class="sidebar-top">
        <div class="logo-mini"><i class="bi bi-mortarboard"></i></div>
        <span>EduNexo MP</span>
    </div>

    <nav class="sidebar-menu">
        <?php foreach ($studentSidebarItems as $item): ?>
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
