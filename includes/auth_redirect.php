<?php
if (!function_exists('edunexo_base_url')) {
    function edunexo_base_url(): string
    {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $markers = ['/estudiante/', '/organizacion/', '/admin/', '/superadmin/', '/procesos/'];

        foreach ($markers as $marker) {
            $pos = strpos($scriptName, $marker);

            if ($pos !== false) {
                return rtrim(substr($scriptName, 0, $pos), '/');
            }
        }

        return rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    }
}

if (!function_exists('edunexo_url')) {
    function edunexo_url(string $path): string
    {
        $base = edunexo_base_url();
        return ($base === '' ? '' : $base) . '/' . ltrim($path, '/');
    }
}

if (!function_exists('edunexo_redirect')) {
    function edunexo_redirect(string $path): void
    {
        header('Location: ' . edunexo_url($path));
        exit;
    }
}
