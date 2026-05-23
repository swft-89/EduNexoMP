<?php
session_start();
require_once __DIR__ . '/auth_redirect.php';
require_once __DIR__ . '/csrf.php';

if (!isset($_SESSION['usuario_id'])) {
    edunexo_redirect('index.php');
}

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'organizacion') {
    edunexo_redirect('index.php');
}
