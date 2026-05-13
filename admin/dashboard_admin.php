<?php
require_once __DIR__ . '../../includes/session_admin.php';
require_once __DIR__ . '../../config/conexion.php';

$stmt = $pdo->prepare("
    SELECT a.nombre, a.apellido_paterno, a.apellido_materno, a.puesto, a.departamento, a.tipo_admin
    FROM administrador a
    WHERE a.id_admin = :id_admin
    LIMIT 1
");
$stmt->execute([
    ':id_admin' => $_SESSION['usuario_id']
]);

$admin = $stmt->fetch(PDO::FETCH_ASSOC);
$nombreCompleto = trim(($admin['nombre'] ?? '') . ' ' . ($admin['apellido_paterno'] ?? '') . ' ' . ($admin['apellido_materno'] ?? ''));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard administrador | EduNexo MP</title>
    <link rel="stylesheet" href="../../assets/css/admin/dashboard_admin.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/org/dashboard_organizacion.css">
    <link rel="stylesheet" href="../assets/css/dark.css?v=dark-fix-2">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main class="admin-dashboard">
        <section class="admin-hero">
            <div class="admin-hero__content">
                <span class="admin-badge">Panel administrativo</span>
                <h1>Bienvenido, <?php echo htmlspecialchars($nombreCompleto ?: 'Administrador'); ?></h1>
                <p>
                    Desde aquí podrás gestionar categorías, habilidades, reportes y herramientas de control
                    para el funcionamiento general de EduNexo MP.
                </p>
            </div>
        </section>

        <section class="admin-grid">
            <article class="admin-card">
                <h3>Categorías</h3>
                <p>Administra las categorías disponibles para los desafíos.</p>
                <a href="#">Gestionar categorías</a>
            </article>

            <article class="admin-card">
                <h3>Habilidades</h3>
                <p>Define y organiza habilidades globales del sistema.</p>
                <a href="#">Gestionar habilidades</a>
            </article>

            <article class="admin-card">
                <h3>Usuarios</h3>
                <p>Consulta cuentas registradas y su estado actual.</p>
                <a href="#">Ver usuarios</a>
            </article>

            <article class="admin-card">
                <h3>Reportes</h3>
                <p>Visualiza métricas generales de estudiantes, organizaciones y desafíos.</p>
                <a href="#">Ver reportes</a>
            </article>
        </section>
    </main>

    <?php include '../../includes/footer.php'; ?>
</body>
</html>