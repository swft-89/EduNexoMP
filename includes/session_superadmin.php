<?php
session_start();
require_once __DIR__ . '/auth_redirect.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/../config/conexion.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'administrador') {
    edunexo_redirect('index.php');
}

$stmt = $pdo->prepare("
    SELECT tipo_admin
    FROM administrador
    WHERE id_admin = :id_admin
    LIMIT 1
");
$stmt->execute([
    ':id_admin' => $_SESSION['usuario_id']
]);

$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin || ($admin['tipo_admin'] ?? '') !== 'superadmin') {
    edunexo_redirect('admin/dashboard_admin.php');
}
