<?php
session_start();
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/profile_photo.php';
require_once __DIR__ . '/../../includes/validation.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'administrador') {
    header('Location: ../../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../admin/editar_perfil_admin.php');
    exit;
}

$idAdmin = (int) $_SESSION['usuario_id'];

$nombre = trim($_POST['nombre'] ?? '');
$apellidoPaterno = trim($_POST['apellido_paterno'] ?? '');
$apellidoMaterno = trim($_POST['apellido_materno'] ?? '');
$correoElectronico = trim($_POST['correo_electronico'] ?? '');
$puesto = trim($_POST['puesto'] ?? '');
$departamento = trim($_POST['departamento'] ?? '');
$fotoUrlActual = trim($_POST['foto_url_actual'] ?? '');
$fotoUrl = $fotoUrlActual;

$_SESSION['old'] = $_POST;

if ($nombre === '' || $apellidoPaterno === '' || $correoElectronico === '') {
    $_SESSION['error'] = 'Completa los campos obligatorios.';
    header('Location: ../../admin/editar_perfil_admin.php');
    exit;
}

if (!filter_var($correoElectronico, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'El correo electronico no es valido.';
    header('Location: ../../admin/editar_perfil_admin.php');
    exit;
}

$validationErrors = [];
edunexo_add_error_if(!edunexo_is_valid_email($correoElectronico), $validationErrors, 'El correo electronico no es valido.');
edunexo_add_error_if(!edunexo_is_valid_person_name($nombre), $validationErrors, 'El nombre solo debe contener letras y espacios.');
edunexo_add_error_if(!edunexo_is_valid_person_name($apellidoPaterno), $validationErrors, 'El apellido paterno solo debe contener letras y espacios.');
edunexo_add_error_if($apellidoMaterno !== '' && !edunexo_is_valid_person_name($apellidoMaterno), $validationErrors, 'El apellido materno solo debe contener letras y espacios.');
edunexo_add_error_if($puesto !== '' && !edunexo_is_valid_simple_text($puesto, 100), $validationErrors, 'El puesto contiene caracteres no validos.');
edunexo_add_error_if($departamento !== '' && !edunexo_is_valid_simple_text($departamento, 100), $validationErrors, 'El departamento contiene caracteres no validos.');

if (!empty($validationErrors)) {
    $_SESSION['error'] = implode(' ', $validationErrors);
    header('Location: ../../admin/editar_perfil_admin.php');
    exit;
}

try {
    $fotoSubida = edunexo_upload_profile_photo('foto_perfil', 'admin_' . $idAdmin);

    if ($fotoSubida !== null) {
        $fotoUrl = $fotoSubida;
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT id_usuario
        FROM usuario
        WHERE correo_electronico = :correo
          AND id_usuario <> :id_usuario
        LIMIT 1
    ");
    $stmt->execute([
        ':correo' => $correoElectronico,
        ':id_usuario' => $idAdmin
    ]);

    if ($stmt->fetch()) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Ese correo electronico ya esta registrado.';
        header('Location: ../../admin/editar_perfil_admin.php');
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE usuario
        SET correo_electronico = :correo_electronico
        WHERE id_usuario = :id_usuario
    ");
    $stmt->execute([
        ':correo_electronico' => $correoElectronico,
        ':id_usuario' => $idAdmin
    ]);

    $stmt = $pdo->prepare("
        UPDATE administrador
        SET
            nombre = :nombre,
            apellido_paterno = :apellido_paterno,
            apellido_materno = :apellido_materno,
            puesto = :puesto,
            departamento = :departamento,
            foto_url = :foto_url
        WHERE id_admin = :id_admin
    ");
    $stmt->execute([
        ':nombre' => $nombre,
        ':apellido_paterno' => $apellidoPaterno,
        ':apellido_materno' => $apellidoMaterno !== '' ? $apellidoMaterno : null,
        ':puesto' => $puesto !== '' ? $puesto : null,
        ':departamento' => $departamento !== '' ? $departamento : null,
        ':foto_url' => $fotoUrl !== '' ? $fotoUrl : null,
        ':id_admin' => $idAdmin
    ]);

    $stmt = $pdo->prepare("
        INSERT INTO auditoria_admin (
            tipo_evento,
            descripcion,
            usuario_nombre,
            usuario_rol,
            id_usuario_relacionado
        ) VALUES (
            :tipo_evento,
            :descripcion,
            :usuario_nombre,
            :usuario_rol,
            :id_usuario_relacionado
        )
    ");
    $stmt->execute([
        ':tipo_evento' => 'Perfil actualizado',
        ':descripcion' => 'Administrador actualizo su perfil',
        ':usuario_nombre' => trim($nombre . ' ' . $apellidoPaterno),
        ':usuario_rol' => 'Admin',
        ':id_usuario_relacionado' => $idAdmin
    ]);

    $pdo->commit();

    unset($_SESSION['old']);
    $_SESSION['success'] = 'Tu perfil administrativo fue actualizado correctamente.';
    header('Location: ../../admin/perfil_admin.php');
    exit;
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error'] = $e->getMessage();
    header('Location: ../../admin/editar_perfil_admin.php');
    exit;
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error'] = 'No se pudo actualizar tu perfil administrativo.';
    header('Location: ../../admin/editar_perfil_admin.php');
    exit;
}
