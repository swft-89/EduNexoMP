<?php
session_start();
require_once __DIR__ . '/../../config/conexion.php';

if (!isset($_SESSION['usuario_id'], $_SESSION['rol'])) {
    header('Location: ../../index.php');
    exit;
}

$idUsuario = (int) $_SESSION['usuario_id'];
$rol = $_SESSION['rol'];
$fecha = date('Y-m-d_H-i');
$nombreArchivo = 'reporte_' . $rol . '_' . $fecha . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");

function reporte_csv_row($out, array $row): void
{
    fputcsv($out, $row);
}

if ($rol === 'estudiante') {
    reporte_csv_row($out, ['Reporte de estudiante']);
    reporte_csv_row($out, ['Generado', date('d/m/Y H:i')]);
    reporte_csv_row($out, []);
    reporte_csv_row($out, ['Propuesta', 'Desafio', 'Organizacion', 'Categoria', 'Estado', 'Fecha de envio', 'Fecha de respuesta']);

    $stmt = $pdo->prepare("
        SELECT
            COALESCE(p.titulo_propuesta, d.titulo) AS titulo_propuesta,
            d.titulo AS desafio,
            o.nombre_empresa,
            c.nombre_categoria,
            p.estado,
            p.fecha_envio,
            p.fecha_respuesta
        FROM propuesta p
        INNER JOIN desafio d ON p.id_desafio = d.id_desafio
        INNER JOIN organizacion o ON d.id_organizacion = o.id_organizacion
        INNER JOIN categoria c ON d.id_categoria = c.id_categoria
        WHERE p.id_estudiante = :id
        ORDER BY p.fecha_envio DESC
    ");
    $stmt->execute([':id' => $idUsuario]);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        reporte_csv_row($out, [
            $row['titulo_propuesta'] ?? '',
            $row['desafio'] ?? '',
            $row['nombre_empresa'] ?? '',
            $row['nombre_categoria'] ?? '',
            $row['estado'] ?? '',
            !empty($row['fecha_envio']) ? date('d/m/Y', strtotime($row['fecha_envio'])) : '',
            !empty($row['fecha_respuesta']) ? date('d/m/Y', strtotime($row['fecha_respuesta'])) : 'Pendiente',
        ]);
    }
} elseif ($rol === 'organizacion') {
    reporte_csv_row($out, ['Reporte de organizacion']);
    reporte_csv_row($out, ['Generado', date('d/m/Y H:i')]);
    reporte_csv_row($out, []);
    reporte_csv_row($out, ['Desafio', 'Categoria', 'Estado', 'Fecha publicacion', 'Fecha limite', 'Propuestas recibidas', 'Propuestas aceptadas']);

    $stmt = $pdo->prepare("
        SELECT
            d.titulo,
            c.nombre_categoria,
            d.estado,
            d.fecha_publicacion,
            d.fecha_limite,
            COUNT(p.id_propuesta) AS propuestas_recibidas,
            COUNT(CASE WHEN LOWER(COALESCE(p.estado, '')) LIKE '%acept%' THEN 1 END) AS propuestas_aceptadas
        FROM desafio d
        INNER JOIN categoria c ON d.id_categoria = c.id_categoria
        LEFT JOIN propuesta p ON d.id_desafio = p.id_desafio
        WHERE d.id_organizacion = :id
        GROUP BY d.id_desafio, d.titulo, c.nombre_categoria, d.estado, d.fecha_publicacion, d.fecha_limite
        ORDER BY d.fecha_publicacion DESC
    ");
    $stmt->execute([':id' => $idUsuario]);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        reporte_csv_row($out, [
            $row['titulo'] ?? '',
            $row['nombre_categoria'] ?? '',
            $row['estado'] ?? '',
            !empty($row['fecha_publicacion']) ? date('d/m/Y', strtotime($row['fecha_publicacion'])) : '',
            !empty($row['fecha_limite']) ? date('d/m/Y', strtotime($row['fecha_limite'])) : 'Sin limite',
            (int) ($row['propuestas_recibidas'] ?? 0),
            (int) ($row['propuestas_aceptadas'] ?? 0),
        ]);
    }
} elseif ($rol === 'administrador') {
    reporte_csv_row($out, ['Reporte global']);
    reporte_csv_row($out, ['Generado', date('d/m/Y H:i')]);
    reporte_csv_row($out, []);
    reporte_csv_row($out, ['Indicador', 'Total']);

    $metricas = [
        'Total usuarios' => "SELECT COUNT(*) FROM usuario",
        'Estudiantes' => "SELECT COUNT(*) FROM usuario WHERE rol = 'estudiante'",
        'Organizaciones' => "SELECT COUNT(*) FROM usuario WHERE rol = 'organizacion'",
        'Administradores' => "SELECT COUNT(*) FROM usuario WHERE rol = 'administrador'",
        'Desafios' => "SELECT COUNT(*) FROM desafio",
        'Propuestas' => "SELECT COUNT(*) FROM propuesta",
        'Solicitudes admin pendientes' => "SELECT COUNT(*) FROM administrador WHERE estado_solicitud = 'pendiente'",
    ];

    foreach ($metricas as $label => $sql) {
        reporte_csv_row($out, [$label, (int) $pdo->query($sql)->fetchColumn()]);
    }
} else {
    reporte_csv_row($out, ['Rol no soportado']);
}

fclose($out);
exit;
