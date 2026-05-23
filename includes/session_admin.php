<?php
session_start();
require_once __DIR__ . '/auth_redirect.php';
require_once __DIR__ . '/csrf.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'administrador') {
    edunexo_redirect('index.php');
}
