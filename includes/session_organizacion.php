<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'organizacion') {
    header('Location: index.php');
    exit;
}
?>