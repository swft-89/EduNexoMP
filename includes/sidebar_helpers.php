<?php

if (!function_exists('edunexo_sidebar_url')) {
    function edunexo_sidebar_url(string $path): string
    {
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

if (!function_exists('edunexo_sidebar_current_path')) {
    function edunexo_sidebar_current_path(): string
    {
        $root = realpath(__DIR__ . '/..');
        $script = realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: '';

        if ($root && $script && strpos($script, $root) === 0) {
            return trim(str_replace(DIRECTORY_SEPARATOR, '/', substr($script, strlen($root))), '/');
        }

        return '';
    }
}

if (!function_exists('edunexo_sidebar_is_active')) {
    function edunexo_sidebar_is_active(array $paths): bool
    {
        $current = edunexo_sidebar_current_path();

        foreach ($paths as $path) {
            if ($current === trim($path, '/')) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('edunexo_sidebar_active')) {
    function edunexo_sidebar_active(array $paths): string
    {
        return edunexo_sidebar_is_active($paths) ? ' class="active"' : '';
    }
}
