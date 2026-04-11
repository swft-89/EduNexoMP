<?php
session_start();
require_once '../config/conexion.php';

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'organizacion') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../editar_perfil_organizacion.php');
    exit;
}

$idOrganizacion = (int) $_SESSION['usuario_id'];

$nombreEmpresa = trim($_POST['nombre_empresa'] ?? '');
$rfc = trim($_POST['rfc'] ?? '');
$sector = trim($_POST['sector'] ?? '');
$representante = trim($_POST['representante'] ?? '');
$telefonoContacto = trim($_POST['telefono_contacto'] ?? '');
$correoElectronico = trim($_POST['correo_electronico'] ?? '');
$fotoUrl = trim($_POST['foto_url'] ?? '');

$pais = trim($_POST['pais'] ?? '');
$estado = trim($_POST['estado'] ?? '');
$ciudad = trim($_POST['ciudad'] ?? '');
$colonia = trim($_POST['colonia'] ?? '');
$codigoPostal = trim($_POST['codigo_postal'] ?? '');
$calle = trim($_POST['calle'] ?? '');
$numExterior = trim($_POST['num_exterior'] ?? '');

$_SESSION['old'] = $_POST;

if ($nombreEmpresa === '' || $correoElectronico === '') {
    $_SESSION['error'] = 'Completa los campos obligatorios.';
    header('Location: ../editar_perfil_organizacion.php');
    exit;
}

if (!filter_var($correoElectronico, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'El correo electrónico no es válido.';
    header('Location: ../editar_perfil_organizacion.php');
    exit;
}

if ($fotoUrl !== '' && !filter_var($fotoUrl, FILTER_VALIDATE_URL)) {
    $_SESSION['error'] = 'La URL de la imagen no es válida.';
    header('Location: ../editar_perfil_organizacion.php');
    exit;
}

try {
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
        ':id_usuario' => $idOrganizacion
    ]);

    if ($stmt->fetch()) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Ese correo electrónico ya está registrado.';
        header('Location: ../editar_perfil_organizacion.php');
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT id_direccion
        FROM organizacion
        WHERE id_organizacion = :id_organizacion
        LIMIT 1
    ");
    $stmt->execute([
        ':id_organizacion' => $idOrganizacion
    ]);
    $idDireccion = $stmt->fetchColumn();

    $hayDatosDireccion = (
        $pais !== '' ||
        $estado !== '' ||
        $ciudad !== '' ||
        $colonia !== '' ||
        $codigoPostal !== '' ||
        $calle !== '' ||
        $numExterior !== ''
    );

    if ($idDireccion) {
        $stmt = $pdo->prepare("
            UPDATE direccion
            SET
                pais = :pais,
                estado = :estado,
                ciudad = :ciudad,
                colonia = :colonia,
                codigo_postal = :codigo_postal,
                calle = :calle,
                num_exterior = :num_exterior
            WHERE id_direccion = :id_direccion
        ");
        $stmt->execute([
            ':pais' => $pais !== '' ? $pais : null,
            ':estado' => $estado !== '' ? $estado : null,
            ':ciudad' => $ciudad !== '' ? $ciudad : null,
            ':colonia' => $colonia !== '' ? $colonia : null,
            ':codigo_postal' => $codigoPostal !== '' ? $codigoPostal : null,
            ':calle' => $calle !== '' ? $calle : null,
            ':num_exterior' => $numExterior !== '' ? $numExterior : null,
            ':id_direccion' => $idDireccion
        ]);
    } elseif ($hayDatosDireccion) {
        $stmt = $pdo->prepare("
            INSERT INTO direccion (
                pais,
                estado,
                ciudad,
                colonia,
                codigo_postal,
                calle,
                num_exterior
            ) VALUES (
                :pais,
                :estado,
                :ciudad,
                :colonia,
                :codigo_postal,
                :calle,
                :num_exterior
            )
            RETURNING id_direccion
        ");
        $stmt->execute([
            ':pais' => $pais !== '' ? $pais : null,
            ':estado' => $estado !== '' ? $estado : null,
            ':ciudad' => $ciudad !== '' ? $ciudad : null,
            ':colonia' => $colonia !== '' ? $colonia : null,
            ':codigo_postal' => $codigoPostal !== '' ? $codigoPostal : null,
            ':calle' => $calle !== '' ? $calle : null,
            ':num_exterior' => $numExterior !== '' ? $numExterior : null
        ]);
        $idDireccion = $stmt->fetchColumn();
    }

    $stmt = $pdo->prepare("
        UPDATE usuario
        SET correo_electronico = :correo_electronico
        WHERE id_usuario = :id_usuario
    ");
    $stmt->execute([
        ':correo_electronico' => $correoElectronico,
        ':id_usuario' => $idOrganizacion
    ]);

    $stmt = $pdo->prepare("
        UPDATE organizacion
        SET
            nombre_empresa = :nombre_empresa,
            rfc = :rfc,
            sector = :sector,
            representante = :representante,
            telefono_contacto = :telefono_contacto,
            foto_url = :foto_url,
            id_direccion = :id_direccion
        WHERE id_organizacion = :id_organizacion
    ");
    $stmt->execute([
        ':nombre_empresa' => $nombreEmpresa,
        ':rfc' => $rfc !== '' ? $rfc : null,
        ':sector' => $sector !== '' ? $sector : null,
        ':representante' => $representante !== '' ? $representante : null,
        ':telefono_contacto' => $telefonoContacto !== '' ? $telefonoContacto : null,
        ':foto_url' => $fotoUrl !== '' ? $fotoUrl : null,
        ':id_direccion' => $idDireccion ?: null,
        ':id_organizacion' => $idOrganizacion
    ]);

    $pdo->commit();

    unset($_SESSION['old']);
    $_SESSION['success'] = 'El perfil de la organización fue actualizado correctamente.';
    header('Location: ../editar_perfil_organizacion.php');
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error'] = 'No se pudo actualizar el perfil de la organización.';
    header('Location: ../editar_perfil_organizacion.php');
    exit;
}