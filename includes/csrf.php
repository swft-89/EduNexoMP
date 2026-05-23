<?php
if (!function_exists('edunexo_csrf_token')) {
    function edunexo_csrf_token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('edunexo_csrf_input')) {
    function edunexo_csrf_input(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(edunexo_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('edunexo_csrf_is_valid')) {
    function edunexo_csrf_is_valid(?string $token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        return is_string($token)
            && isset($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('edunexo_require_csrf')) {
    function edunexo_require_csrf(string $redirectPath, string $message = 'La sesion expiro. Intenta nuevamente.'): void
    {
        if (edunexo_csrf_is_valid($_POST['csrf_token'] ?? null)) {
            return;
        }

        $_SESSION['error'] = $message;
        header('Location: ' . $redirectPath);
        exit;
    }
}
