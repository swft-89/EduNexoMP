<?php
if (!function_exists('edunexo_asset_url')) {
    function edunexo_asset_url(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        $root = realpath(__DIR__ . '/..');
        $scriptDir = realpath(dirname($_SERVER['SCRIPT_FILENAME'] ?? '')) ?: $root;
        $relativeDir = $root && strpos($scriptDir, $root) === 0
            ? trim(substr($scriptDir, strlen($root)), DIRECTORY_SEPARATOR)
            : '';
        $depth = $relativeDir === '' ? 0 : substr_count($relativeDir, DIRECTORY_SEPARATOR) + 1;

        return str_repeat('../', $depth) . ltrim($path, '/');
    }
}

if (!function_exists('edunexo_upload_profile_photo')) {
    function edunexo_upload_profile_photo(string $fieldName, string $prefix): ?string
    {
        if (empty($_FILES[$fieldName]['name'])) {
            return null;
        }

        $archivo = $_FILES[$fieldName];

        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No se pudo subir la imagen de perfil.');
        }

        if ($archivo['size'] > 2 * 1024 * 1024) {
            throw new RuntimeException('La imagen de perfil no puede superar los 2 MB.');
        }

        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $permitidas = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($extension, $permitidas, true)) {
            throw new RuntimeException('La imagen de perfil debe ser JPG, PNG o WEBP.');
        }

        $info = getimagesize($archivo['tmp_name']);

        if ($info === false) {
            throw new RuntimeException('El archivo seleccionado no es una imagen válida.');
        }

        $destinoDir = __DIR__ . '/../uploads/perfiles';

        if (!is_dir($destinoDir)) {
            mkdir($destinoDir, 0777, true);
        }

        $nombreSeguro = preg_replace('/[^a-z0-9_-]/i', '_', $prefix);
        $nombreArchivo = $nombreSeguro . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $destino = $destinoDir . '/' . $nombreArchivo;

        if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
            throw new RuntimeException('No se pudo guardar la imagen de perfil.');
        }

        return 'uploads/perfiles/' . $nombreArchivo;
    }
}
