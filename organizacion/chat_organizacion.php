<?php
require_once '../includes/session_organizacion.php';
require_once '../config/conexion.php';
require_once '../includes/profile_photo.php';

$idUsuario = $_SESSION['usuario_id'];
$idConversacion = isset($_GET['id']) ? (int) $_GET['id'] : 0;

/* Conversaciones */
$stmt = $pdo->prepare("
    SELECT
        c.id_conversacion,
        c.id_propuesta,
        d.id_desafio,
        d.titulo,
        e.nombre,
        e.apellido_paterno,
        e.apellido_materno,
        e.foto_url,
        m.contenido AS ultimo_mensaje,
        m.fecha_hora AS ultima_fecha,
        COALESCE(nr.no_leidos, 0) AS no_leidos
    FROM conversacion c
    INNER JOIN propuesta p
        ON c.id_propuesta = p.id_propuesta
    INNER JOIN desafio d
        ON p.id_desafio = d.id_desafio
    INNER JOIN estudiante e
        ON p.id_estudiante = e.id_estudiante
    LEFT JOIN LATERAL (
        SELECT contenido, fecha_hora
        FROM mensaje
        WHERE id_conversacion = c.id_conversacion
        ORDER BY fecha_hora DESC
        LIMIT 1
    ) m ON TRUE
    LEFT JOIN LATERAL (
        SELECT COUNT(*) AS no_leidos
        FROM mensaje
        WHERE id_conversacion = c.id_conversacion
          AND id_emisor <> :id_organizacion
          AND leido = FALSE
    ) nr ON TRUE
    WHERE d.id_organizacion = :id_organizacion
    ORDER BY m.fecha_hora DESC NULLS LAST, c.id_conversacion DESC
");
$stmt->execute([
    ':id_organizacion' => $idUsuario
]);
$conversaciones = $stmt->fetchAll();

/* Conversacion activa */
$conversacionActiva = null;
$mensajes = [];

if ($idConversacion > 0) {
    $stmt = $pdo->prepare("
        SELECT
            c.id_conversacion,
            d.titulo,
            e.nombre,
            e.apellido_paterno,
            e.apellido_materno,
            e.foto_url,
            e.carrera,
            e.semestre
        FROM conversacion c
        INNER JOIN propuesta p
            ON c.id_propuesta = p.id_propuesta
        INNER JOIN desafio d
            ON p.id_desafio = d.id_desafio
        INNER JOIN estudiante e
            ON p.id_estudiante = e.id_estudiante
        WHERE c.id_conversacion = :id_conversacion
          AND d.id_organizacion = :id_organizacion
        LIMIT 1
    ");
    $stmt->execute([
        ':id_conversacion' => $idConversacion,
        ':id_organizacion' => $idUsuario
    ]);
    $conversacionActiva = $stmt->fetch();

    if ($conversacionActiva) {
        $stmt = $pdo->prepare("
            UPDATE mensaje
            SET leido = TRUE
            WHERE id_conversacion = :id_conversacion
              AND id_emisor <> :id_usuario
              AND leido = FALSE
        ");
        $stmt->execute([
            ':id_conversacion' => $idConversacion,
            ':id_usuario' => $idUsuario
        ]);

        $stmt = $pdo->prepare("
            SELECT
                id_mensaje,
                contenido,
                fecha_hora,
                leido,
                id_emisor
            FROM mensaje
            WHERE id_conversacion = :id_conversacion
            ORDER BY fecha_hora ASC, id_mensaje ASC
        ");
        $stmt->execute([
            ':id_conversacion' => $idConversacion
        ]);
        $mensajes = $stmt->fetchAll();
    }
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
    <title>Chat Organización - EduNexo MP</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/chat.css">
    <link rel="stylesheet" href="../assets/css/dark.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<div class="app-layout">
    <aside class="sidebar">
        <div class="sidebar-top">
            <div class="logo-mini">EN</div>
            <span>EduNexo MP</span>
        </div>

        <nav class="sidebar-menu">
            <a href="dashboard_organizacion.php">
                <i class="bi bi-house-door"></i> Inicio
            </a>
            <a href="desafios/crear_desafio.php">
                <i class="bi bi-plus-circle"></i> Crear desafío
            </a>
            <a href="desafios/mis_desafios.php">
                <i class="bi bi-briefcase"></i> Mis desafíos
            </a>
            <a href="propuestas_organizacion.php">
                <i class="bi bi-file-earmark-text"></i> Propuestas
            </a>
            <a href="chat_organizacion.php" class="active">
                <i class="bi bi-chat"></i> Chat
            </a>
            <a href="editar_perfil_organizacion.php">
                <i class="bi bi-building"></i> Perfil
            </a>
            <div class="sidebar-bottom">
                <a href="../procesos/logout.php" class="sidebar-logout">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Cerrar sesión</span>
                </a>
            </div>
        </nav>
    </aside>

    <section class="app-content chat-page">
        <?php include __DIR__ . '/../includes/app_topbar.php'; ?>
        <div class="chat-shell">
            <aside class="chat-list-panel">
                <div class="chat-list-header">
                    <h1>Conversaciones</h1>
                    <div class="chat-search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="chatSearch" placeholder="Buscar conversaciones...">
                    </div>
                </div>

                <div class="chat-list">
                    <?php if (!empty($conversaciones)): ?>
                        <?php foreach ($conversaciones as $conv): ?>
                            <?php
                            $nombreEstudiante = trim(
                                $conv['nombre'] . ' ' .
                                $conv['apellido_paterno'] . ' ' .
                                ($conv['apellido_materno'] ?? '')
                            );
                            $iniciales = strtoupper(substr($conv['nombre'], 0, 1));
                            $activa = ($idConversacion === (int) $conv['id_conversacion']);
                            ?>
                            <a
                                href="chat_organizacion.php?id=<?php echo (int) $conv['id_conversacion']; ?>"
                                class="chat-list-item <?php echo $activa ? 'active' : ''; ?>"
                                data-nombre="<?php echo htmlspecialchars(strtolower($nombreEstudiante)); ?>"
                                data-titulo="<?php echo htmlspecialchars(strtolower($conv['titulo'])); ?>"
                            >
                                <div class="chat-avatar" style="width:52px;height:52px;min-width:52px;max-width:52px;min-height:52px;max-height:52px;border-radius:50%;overflow:hidden;display:flex;align-items:center;justify-content:center;flex:0 0 52px;">
                                    <?php if (!empty($conv['foto_url'])): ?>
                                        <img src="<?php echo htmlspecialchars(edunexo_asset_url($conv['foto_url'])); ?>" alt="" style="width:100%;height:100%;object-fit:cover;display:block;">
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($iniciales); ?>
                                    <?php endif; ?>
                                </div>

                                <div class="chat-item-body">
                                    <div class="chat-item-top">
                                        <strong><?php echo htmlspecialchars($nombreEstudiante); ?></strong>

                                        <div class="chat-item-right">
                                            <span>
                                                <?php
                                                echo !empty($conv['ultima_fecha'])
                                                    ? htmlspecialchars(date('d/m/Y H:i', strtotime($conv['ultima_fecha'])))
                                                    : '';
                                                ?>
                                            </span>

                                            <?php if (!empty($conv['no_leidos'])): ?>
                                                <span class="chat-unread-badge">
                                                    <?php echo (int) $conv['no_leidos']; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <p class="chat-preview">
                                        <?php
                                        echo !empty($conv['ultimo_mensaje'])
                                            ? htmlspecialchars(mb_strimwidth($conv['ultimo_mensaje'], 0, 60, '...'))
                                            : 'Sin mensajes aún';
                                        ?>
                                    </p>

                                    <small><?php echo htmlspecialchars($conv['titulo']); ?></small>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="chat-empty-list">
                            <i class="bi bi-chat-square-text"></i>
                            <p>Aún no tienes conversaciones.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </aside>

            <section class="chat-main-panel">
                <?php if ($conversacionActiva): ?>
                    <?php
                    $nombreActivo = trim(
                        $conversacionActiva['nombre'] . ' ' .
                        $conversacionActiva['apellido_paterno'] . ' ' .
                        ($conversacionActiva['apellido_materno'] ?? '')
                    );
                    ?>
                    <div class="chat-main-header">
                        <div>
                            <h2><?php echo htmlspecialchars($nombreActivo); ?></h2>
                            <p>
                                <?php echo htmlspecialchars($conversacionActiva['titulo']); ?>
                                ·
                                <?php echo htmlspecialchars($conversacionActiva['carrera']); ?>
                                ·
                                <?php echo htmlspecialchars($conversacionActiva['semestre']); ?>°
                            </p>
                        </div>
                    </div>

                    <div class="chat-messages-area" id="chatMessages">
                        <?php if (!empty($mensajes)): ?>
                            <?php foreach ($mensajes as $mensaje): ?>
                                <?php $esMio = ((int) $mensaje['id_emisor'] === (int) $idUsuario); ?>
                                <div class="chat-bubble-row <?php echo $esMio ? 'mine' : 'theirs'; ?>">
                                    <div class="chat-bubble">
                                        <p><?php echo nl2br(htmlspecialchars($mensaje['contenido'])); ?></p>
                                        <span><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($mensaje['fecha_hora']))); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="chat-empty-state-inside">
                                <i class="bi bi-chat-dots"></i>
                                <h3>Sin mensajes todavía</h3>
                                <p>Envía el primer mensaje a este estudiante.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <form action="../procesos/enviar_mensaje_organizacion.php" method="POST" class="chat-send-form">
                        <input type="hidden" name="id_conversacion" value="<?php echo (int) $conversacionActiva['id_conversacion']; ?>">
                        <input type="text" name="contenido" placeholder="Escribe un mensaje..." required>
                        <button type="submit" class="btn btn-primary">Enviar</button>
                    </form>
                <?php else: ?>
                    <div class="chat-placeholder">
                        <div class="chat-placeholder-icon">
                            <i class="bi bi-send"></i>
                        </div>
                        <h2>Selecciona una conversación</h2>
                        <p>Elige un chat para comunicarte con un estudiante</p>
                    </div>
                <?php endif; ?>
            </section>
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

<?php if ($conversacionActiva): ?>
<script>
    function scrollChatToBottom() {
        const chatBox = document.getElementById('chatMessages');
        if (chatBox) {
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    }

    window.addEventListener('load', function () {
        scrollChatToBottom();
    });

    setInterval(function () {
        const activeElement = document.activeElement;
        const isTyping =
            activeElement &&
            (activeElement.tagName === 'INPUT' || activeElement.tagName === 'TEXTAREA');

        if (!isTyping) {
            window.location.reload();
        }
    }, 3000);
</script>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('chatSearch');

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const value = this.value.toLowerCase();
            const items = document.querySelectorAll('.chat-list-item');

            items.forEach(item => {
                const nombre = item.dataset.nombre || '';
                const titulo = item.dataset.titulo || '';

                if (nombre.includes(value) || titulo.includes(value)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
});
</script>

</body>
</html>
