<?php
session_start();
require_once '../../config/conexion.php';
require_once '../../includes/profile_photo.php';
require_once '../../includes/student_schema.php';

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'estudiante') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../estudiante/editar_perfil_estudiante.php');
    exit;
}

$idEstudiante = (int) $_SESSION['usuario_id'];

$nombre = trim($_POST['nombre'] ?? '');
$apellidoPaterno = trim($_POST['apellido_paterno'] ?? '');
$apellidoMaterno = trim($_POST['apellido_materno'] ?? '');
$carrera = trim($_POST['carrera'] ?? '');
$noControl = trim($_POST['no_control'] ?? '');
$semestre = (int) ($_POST['semestre'] ?? 0);
$intereses = trim($_POST['intereses'] ?? '');
$curp = strtoupper(trim($_POST['curp'] ?? ''));
$telefono = trim($_POST['telefono'] ?? '');
$fotoUrlActual = trim($_POST['foto_url_actual'] ?? '');
$fotoUrl = $fotoUrlActual;
$correoElectronico = trim($_POST['correo_electronico'] ?? '');

$pais = trim($_POST['pais'] ?? '');
$estado = trim($_POST['estado'] ?? '');
$ciudad = trim($_POST['ciudad'] ?? '');
$colonia = trim($_POST['colonia'] ?? '');
$codigoPostal = trim($_POST['codigo_postal'] ?? '');
$calle = trim($_POST['calle'] ?? '');
$numExterior = trim($_POST['num_exterior'] ?? '');

$_SESSION['old'] = $_POST;

if (
    $nombre === '' ||
    $apellidoPaterno === '' ||
    $carrera === '' ||
    $noControl === '' ||
    $correoElectronico === '' ||
    $semestre <= 0
) {
    $_SESSION['error'] = 'Completa los campos obligatorios.';
    header('Location: ../../estudiante/editar_perfil_estudiante.php');
    exit;
}

if (!filter_var($correoElectronico, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'El correo electrónico no es válido.';
    header('Location: ../../estudiante/editar_perfil_estudiante.php');
    exit;
}

try {
    edunexo_ensure_student_interests_column($pdo);

    $fotoSubida = edunexo_upload_profile_photo('foto_perfil', 'estudiante_' . $idEstudiante);

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
        ':id_usuario' => $idEstudiante
    ]);

    if ($stmt->fetch()) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Ese correo electrónico ya está registrado.';
        header('Location: ../../estudiante/editar_perfil_estudiante.php');
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT id_estudiante
        FROM estudiante
        WHERE no_control = :no_control
          AND id_estudiante <> :id_estudiante
        LIMIT 1
    ");
    $stmt->execute([
        ':no_control' => $noControl,
        ':id_estudiante' => $idEstudiante
    ]);

    if ($stmt->fetch()) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Ese número de control ya está registrado.';
        header('Location: ../../estudiante/editar_perfil_estudiante.php');
        exit;
    }

    if ($curp !== '') {
        $stmt = $pdo->prepare("
            SELECT id_estudiante
            FROM estudiante
            WHERE curp = :curp
              AND id_estudiante <> :id_estudiante
            LIMIT 1
        ");
        $stmt->execute([
            ':curp' => $curp,
            ':id_estudiante' => $idEstudiante
        ]);

        if ($stmt->fetch()) {
            $pdo->rollBack();
            $_SESSION['error'] = 'La CURP ya está registrada.';
            header('Location: ../../estudiante/editar_perfil_estudiante.php');
            exit;
        }
    }

    $stmt = $pdo->prepare("
        UPDATE usuario
        SET correo_electronico = :correo_electronico
        WHERE id_usuario = :id_usuario
    ");
    $stmt->execute([
        ':correo_electronico' => $correoElectronico,
        ':id_usuario' => $idEstudiante
    ]);

    $stmt = $pdo->prepare("
        SELECT id_direccion
        FROM estudiante
        WHERE id_estudiante = :id_estudiante
        LIMIT 1
    ");
    $stmt->execute([
        ':id_estudiante' => $idEstudiante
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
        UPDATE estudiante
        SET
            nombre = :nombre,
            apellido_paterno = :apellido_paterno,
            apellido_materno = :apellido_materno,
            carrera = :carrera,
            no_control = :no_control,
            semestre = :semestre,
            intereses = :intereses,
            curp = :curp,
            telefono = :telefono,
            foto_url = :foto_url,
            id_direccion = :id_direccion
        WHERE id_estudiante = :id_estudiante
    ");
    $stmt->execute([
        ':nombre' => $nombre,
        ':apellido_paterno' => $apellidoPaterno,
        ':apellido_materno' => $apellidoMaterno !== '' ? $apellidoMaterno : null,
        ':carrera' => $carrera,
        ':no_control' => $noControl,
        ':semestre' => $semestre,
        ':intereses' => $intereses !== '' ? $intereses : null,
        ':curp' => $curp !== '' ? $curp : null,
        ':telefono' => $telefono !== '' ? $telefono : null,
        ':foto_url' => $fotoUrl !== '' ? $fotoUrl : null,
        ':id_direccion' => $idDireccion ?: null,
        ':id_estudiante' => $idEstudiante
    ]);

    $pdo->commit();

    unset($_SESSION['old']);
    $_SESSION['success'] = 'Tu perfil fue actualizado correctamente.';
    header('Location: ../../estudiante/editar_perfil_estudiante.php');
    exit;
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error'] = $e->getMessage();
    header('Location: ../../estudiante/editar_perfil_estudiante.php');
    exit;
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error'] = 'No se pudo actualizar tu perfil.';
    header('Location: ../../estudiante/editar_perfil_estudiante.php');
    exit;
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error'] = 'No se pudo actualizar tu perfil.';
    header('Location: ../../estudiante/editar_perfil_estudiante.php');
    exit;
}
