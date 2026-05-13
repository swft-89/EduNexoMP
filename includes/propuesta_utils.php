<?php

function edunexo_normalize_estado_propuesta(?string $estado): string
{
    $estado = mb_strtolower(trim($estado ?? ''));
    return str_replace(
        ['á', 'é', 'í', 'ó', 'ú', 'Ã¡', 'Ã©', 'Ã­', 'Ã³', 'Ãº'],
        ['a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u'],
        $estado
    );
}

function edunexo_propuesta_editable(?string $estado): bool
{
    $estado = edunexo_normalize_estado_propuesta($estado);
    return $estado === 'en revision' || $estado === 'pendiente';
}

function edunexo_unlink_uploaded_proposal_file(?string $relativePath): void
{
    $relativePath = ltrim(trim($relativePath ?? ''), '/\\');

    if ($relativePath === '') {
        return;
    }

    $uploadsRoot = realpath(__DIR__ . '/../uploads/propuestas');
    $filePath = realpath(__DIR__ . '/../' . $relativePath);

    if (!$uploadsRoot || !$filePath) {
        return;
    }

    if (strpos($filePath, $uploadsRoot . DIRECTORY_SEPARATOR) !== 0) {
        return;
    }

    if (is_file($filePath)) {
        unlink($filePath);
    }
}
