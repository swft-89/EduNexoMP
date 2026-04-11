<?php
session_start();
require_once __DIR__ . '/../config/conexion.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'administrador') {
    header('Location: index.php');
    exit;
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
    header('Location: dashboard_admin.php');
    exit;
}