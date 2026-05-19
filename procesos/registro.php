<?php
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/student_schema.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/carreras.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

function limpiar($valor)
{
    return trim($valor ?? '');
}

$rol = limpiar($_POST['rol'] ?? '');
$correo = limpiar($_POST['correo'] ?? '');
$contrasena = limpiar($_POST['contrasena'] ?? '');

$rolesValidos = ['estudiante', 'organizacion', 'administrador'];

if (!in_array($rol, $rolesValidos, true)) {
    $_SESSION['error'] = "Rol no válido.";
    $_SESSION['auth_modal'] = 'register';
    header('Location: ../index.php');
    exit;
}

if ($correo === '' || $contrasena === '') {
    $_SESSION['error'] = "Correo y contraseña obligatorios.";
    $_SESSION['auth_modal'] = 'register';
    header('Location: ../index.php');
    exit;
}

$validationErrors = [];
edunexo_add_error_if(!edunexo_is_valid_email($correo), $validationErrors, 'El correo electronico no es valido.');
edunexo_add_error_if(!edunexo_is_valid_password($contrasena), $validationErrors, 'La contrasena debe tener al menos 8 caracteres e incluir letras y numeros.');

if (!empty($validationErrors)) {
    $_SESSION['error'] = implode(' ', $validationErrors);
    $_SESSION['auth_modal'] = 'register';
    header('Location: ../index.php');
    exit;
}

try {
    if ($rol === 'estudiante') {
        edunexo_ensure_student_interests_column($pdo);
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT id_usuario
        FROM usuario
        WHERE correo_electronico = :correo
        LIMIT 1
    ");
    $stmt->execute([
        ':correo' => $correo
    ]);

    if ($stmt->fetch()) {
        throw new Exception("El correo ya está registrado.");
    }

    $hashContrasena = password_hash($contrasena, PASSWORD_DEFAULT);
    $estadoUsuario = ($rol === 'administrador') ? 'inactivo' : 'activo';

    $stmt = $pdo->prepare("
        INSERT INTO usuario (
            correo_electronico,
            hash_contrasena,
            rol,
            estado
        ) VALUES (
            :correo,
            :hash_contrasena,
            :rol,
            :estado
        )
        RETURNING id_usuario
    ");
    $stmt->execute([
        ':correo' => $correo,
        ':hash_contrasena' => $hashContrasena,
        ':rol' => $rol,
        ':estado' => $estadoUsuario
    ]);

    $idUsuario = (int) $stmt->fetchColumn();

    if ($idUsuario <= 0) {
        throw new Exception("No se pudo crear el usuario.");
    }

    $nombreMostrar = $correo;
    $rolMostrar = '';
    $tipoEvento = 'Nuevo usuario';
    $descripcionAuditoria = 'Se registró una nueva cuenta en la plataforma';

    if ($rol === 'estudiante') {
        $nombre = limpiar($_POST['est_nombre'] ?? '');
        $apellidoPaterno = limpiar($_POST['est_apellido_paterno'] ?? '');
        $apellidoMaterno = limpiar($_POST['est_apellido_materno'] ?? '');
        $carreraSeleccionada = limpiar($_POST['est_carrera'] ?? '');
        $carreraOtra = limpiar($_POST['est_carrera_otra'] ?? '');
        $carrera = $carreraSeleccionada === edunexo_carrera_otra_value() ? $carreraOtra : $carreraSeleccionada;
        $noControl = limpiar($_POST['est_no_control'] ?? '');
        $semestre = (int) limpiar($_POST['est_semestre'] ?? '');
        $intereses = limpiar($_POST['est_intereses'] ?? '');
        $curp = strtoupper(limpiar($_POST['est_curp'] ?? ''));
        $telefono = limpiar($_POST['est_telefono'] ?? '');

        $pais = limpiar($_POST['est_pais'] ?? '');
        $estado = limpiar($_POST['est_estado'] ?? '');
        $ciudad = limpiar($_POST['est_ciudad'] ?? '');
        $colonia = limpiar($_POST['est_colonia'] ?? '');
        $codigoPostal = limpiar($_POST['est_codigo_postal'] ?? '');
        $calle = limpiar($_POST['est_calle'] ?? '');
        $numExterior = limpiar($_POST['est_num_exterior'] ?? '');

        if ($nombre === '' || $apellidoPaterno === '' || $carrera === '' || $noControl === '' || $semestre <= 0) {
            throw new Exception("Completa correctamente los campos obligatorios del estudiante.");
        }

        $validationErrors = [];
        edunexo_add_error_if(!edunexo_is_valid_person_name($nombre), $validationErrors, 'El nombre solo debe contener letras y espacios.');
        edunexo_add_error_if(!edunexo_is_valid_person_name($apellidoPaterno), $validationErrors, 'El apellido paterno solo debe contener letras y espacios.');
        edunexo_add_error_if($apellidoMaterno !== '' && !edunexo_is_valid_person_name($apellidoMaterno), $validationErrors, 'El apellido materno solo debe contener letras y espacios.');
        edunexo_add_error_if(
            $carreraSeleccionada !== edunexo_carrera_otra_value() && !edunexo_carrera_estudiante_valida($carreraSeleccionada),
            $validationErrors,
            'Selecciona una carrera valida.'
        );
        edunexo_add_error_if(
            $carreraSeleccionada === edunexo_carrera_otra_value() && !edunexo_is_valid_simple_text($carreraOtra, 120),
            $validationErrors,
            'Escribe una carrera valida.'
        );
        edunexo_add_error_if(!edunexo_is_valid_control_number($noControl), $validationErrors, 'El numero de control solo puede contener letras, numeros o guion, de 4 a 20 caracteres.');
        edunexo_add_error_if($semestre < 1 || $semestre > 12, $validationErrors, 'El semestre debe estar entre 1 y 12.');
        edunexo_add_error_if($curp !== '' && !edunexo_is_valid_curp($curp), $validationErrors, 'La CURP no tiene un formato valido.');
        edunexo_add_error_if($telefono !== '' && !edunexo_is_valid_phone($telefono), $validationErrors, 'El telefono solo debe contener numeros y puede incluir espacios, parentesis, guion o +.');
        edunexo_add_error_if($codigoPostal !== '' && !edunexo_is_valid_postal_code($codigoPostal), $validationErrors, 'El codigo postal debe tener 5 digitos.');
        edunexo_throw_validation_errors($validationErrors);

        $stmt = $pdo->prepare("
            SELECT id_estudiante
            FROM estudiante
            WHERE no_control = :no_control
            LIMIT 1
        ");
        $stmt->execute([':no_control' => $noControl]);

        if ($stmt->fetch()) {
            throw new Exception('Ese numero de control ya esta registrado.');
        }

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

        $stmt = $pdo->prepare("
            INSERT INTO estudiante (
                id_estudiante,
                nombre,
                apellido_paterno,
                apellido_materno,
                carrera,
                no_control,
                semestre,
                intereses,
                curp,
                telefono,
                id_direccion
            ) VALUES (
                :id_estudiante,
                :nombre,
                :apellido_paterno,
                :apellido_materno,
                :carrera,
                :no_control,
                :semestre,
                :intereses,
                :curp,
                :telefono,
                :id_direccion
            )
        ");
        $stmt->execute([
            ':id_estudiante' => $idUsuario,
            ':nombre' => $nombre,
            ':apellido_paterno' => $apellidoPaterno,
            ':apellido_materno' => $apellidoMaterno !== '' ? $apellidoMaterno : null,
            ':carrera' => $carrera,
            ':no_control' => $noControl,
            ':semestre' => $semestre,
            ':intereses' => $intereses !== '' ? $intereses : null,
            ':curp' => $curp !== '' ? $curp : null,
            ':telefono' => $telefono !== '' ? $telefono : null,
            ':id_direccion' => $idDireccion ?: null
        ]);

        $nombreMostrar = trim($nombre . ' ' . $apellidoPaterno . ' ' . $apellidoMaterno);
        $rolMostrar = 'Est.';
        $tipoEvento = 'Nuevo usuario';
        $descripcionAuditoria = 'Nuevo estudiante registrado en la plataforma';
    }

    if ($rol === 'organizacion') {
        $nombreEmpresa = limpiar($_POST['org_nombre_empresa'] ?? '');
        $rfc = strtoupper(limpiar($_POST['org_rfc'] ?? ''));
        $sector = limpiar($_POST['org_sector'] ?? '');
        $representante = limpiar($_POST['org_representante'] ?? '');
        $telefonoContacto = limpiar($_POST['org_telefono_contacto'] ?? '');

        $pais = limpiar($_POST['org_pais'] ?? '');
        $estado = limpiar($_POST['org_estado'] ?? '');
        $ciudad = limpiar($_POST['org_ciudad'] ?? '');
        $colonia = limpiar($_POST['org_colonia'] ?? '');
        $codigoPostal = limpiar($_POST['org_codigo_postal'] ?? '');
        $calle = limpiar($_POST['org_calle'] ?? '');
        $numExterior = limpiar($_POST['org_num_exterior'] ?? '');

        if ($nombreEmpresa === '') {
            throw new Exception("El nombre de la empresa es obligatorio.");
        }

        $validationErrors = [];
        edunexo_add_error_if(!edunexo_is_valid_public_name($nombreEmpresa), $validationErrors, 'El nombre de la empresa contiene caracteres no validos.');
        edunexo_add_error_if($rfc !== '' && !edunexo_is_valid_rfc($rfc), $validationErrors, 'El RFC no tiene un formato valido.');
        edunexo_add_error_if($sector !== '' && !edunexo_is_valid_simple_text($sector, 100), $validationErrors, 'El sector contiene caracteres no validos.');
        edunexo_add_error_if($representante !== '' && !edunexo_is_valid_person_name($representante), $validationErrors, 'El representante solo debe contener letras y espacios.');
        edunexo_add_error_if($telefonoContacto !== '' && !edunexo_is_valid_phone($telefonoContacto), $validationErrors, 'El telefono de contacto no tiene un formato valido.');
        edunexo_add_error_if($codigoPostal !== '' && !edunexo_is_valid_postal_code($codigoPostal), $validationErrors, 'El codigo postal debe tener 5 digitos.');
        edunexo_throw_validation_errors($validationErrors);

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

        $stmt = $pdo->prepare("
            INSERT INTO organizacion (
                id_organizacion,
                nombre_empresa,
                rfc,
                sector,
                representante,
                telefono_contacto,
                id_direccion
            ) VALUES (
                :id_organizacion,
                :nombre_empresa,
                :rfc,
                :sector,
                :representante,
                :telefono_contacto,
                :id_direccion
            )
        ");
        $stmt->execute([
            ':id_organizacion' => $idUsuario,
            ':nombre_empresa' => $nombreEmpresa,
            ':rfc' => $rfc !== '' ? $rfc : null,
            ':sector' => $sector !== '' ? $sector : null,
            ':representante' => $representante !== '' ? $representante : null,
            ':telefono_contacto' => $telefonoContacto !== '' ? $telefonoContacto : null,
            ':id_direccion' => $idDireccion ?: null
        ]);

        $nombreMostrar = $nombreEmpresa;
        $rolMostrar = 'Org.';
        $tipoEvento = 'Nuevo usuario';
        $descripcionAuditoria = 'Nueva organización registrada en la plataforma';
    }

    if ($rol === 'administrador') {
        $nombre = limpiar($_POST['adm_nombre'] ?? '');
        $apellidoPaterno = limpiar($_POST['adm_apellido_paterno'] ?? '');
        $apellidoMaterno = limpiar($_POST['adm_apellido_materno'] ?? '');
        $puesto = limpiar($_POST['adm_puesto'] ?? '');
        $departamento = limpiar($_POST['adm_departamento'] ?? '');

        if ($nombre === '' || $apellidoPaterno === '') {
            throw new Exception("Completa correctamente los campos obligatorios del administrador.");
        }

        $validationErrors = [];
        edunexo_add_error_if(!edunexo_is_valid_person_name($nombre), $validationErrors, 'El nombre solo debe contener letras y espacios.');
        edunexo_add_error_if(!edunexo_is_valid_person_name($apellidoPaterno), $validationErrors, 'El apellido paterno solo debe contener letras y espacios.');
        edunexo_add_error_if($apellidoMaterno !== '' && !edunexo_is_valid_person_name($apellidoMaterno), $validationErrors, 'El apellido materno solo debe contener letras y espacios.');
        edunexo_add_error_if($puesto !== '' && !edunexo_is_valid_simple_text($puesto, 100), $validationErrors, 'El puesto contiene caracteres no validos.');
        edunexo_add_error_if($departamento !== '' && !edunexo_is_valid_simple_text($departamento, 100), $validationErrors, 'El departamento contiene caracteres no validos.');
        edunexo_throw_validation_errors($validationErrors);

        $stmt = $pdo->prepare("
            INSERT INTO administrador (
                id_admin,
                nombre,
                apellido_paterno,
                apellido_materno,
                puesto,
                departamento,
                tipo_admin,
                estado_solicitud,
                autorizado_por,
                fecha_autorizacion
            ) VALUES (
                :id_admin,
                :nombre,
                :apellido_paterno,
                :apellido_materno,
                :puesto,
                :departamento,
                'admin',
                'pendiente',
                NULL,
                NULL
            )
        ");
        $stmt->execute([
            ':id_admin' => $idUsuario,
            ':nombre' => $nombre,
            ':apellido_paterno' => $apellidoPaterno,
            ':apellido_materno' => $apellidoMaterno !== '' ? $apellidoMaterno : null,
            ':puesto' => $puesto !== '' ? $puesto : null,
            ':departamento' => $departamento !== '' ? $departamento : null
        ]);

        $nombreMostrar = trim($nombre . ' ' . $apellidoPaterno . ' ' . $apellidoMaterno);
        $rolMostrar = 'Admin';
        $tipoEvento = 'Solicitud admin';
        $descripcionAuditoria = 'Se registró una nueva solicitud de administrador';
    }

    $stmtAudit = $pdo->prepare("
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
    $stmtAudit->execute([
        ':tipo_evento' => $tipoEvento,
        ':descripcion' => $descripcionAuditoria,
        ':usuario_nombre' => $nombreMostrar !== '' ? $nombreMostrar : $correo,
        ':usuario_rol' => $rolMostrar,
        ':id_usuario_relacionado' => $idUsuario
    ]);

    $pdo->commit();

    if ($rol === 'administrador') {
        $_SESSION['success'] = "Tu solicitud de administrador fue enviada correctamente. Quedará activa cuando un superadministrador la apruebe.";
    } else {
        $_SESSION['success'] = "Cuenta creada correctamente. Ahora puedes iniciar sesión.";
    }

    header('Location: ../index.php');
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error'] = $e->getMessage();
    $_SESSION['auth_modal'] = 'register';
    header('Location: ../index.php');
    exit;
}
