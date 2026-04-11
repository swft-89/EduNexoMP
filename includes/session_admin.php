<?php
session_start();

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'administrador') {
    header('Location: index.php');
    exit;
}