<?php

if (!function_exists('edunexo_is_valid_email')) {
    function edunexo_is_valid_email(string $email): bool
    {
        return strlen($email) <= 120 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('edunexo_is_valid_person_name')) {
    function edunexo_is_valid_person_name(string $value): bool
    {
        return preg_match('/^[\p{L}ÁÉÍÓÚÜÑáéíóúüñ]+(?:[\s\'-][\p{L}ÁÉÍÓÚÜÑáéíóúüñ]+)*$/u', $value) === 1;
    }
}

if (!function_exists('edunexo_is_valid_public_name')) {
    function edunexo_is_valid_public_name(string $value): bool
    {
        return preg_match('/^[\p{L}\p{N}ÁÉÍÓÚÜÑáéíóúüñ&.,#()\'\- ]{2,150}$/u', $value) === 1;
    }
}

if (!function_exists('edunexo_is_valid_simple_text')) {
    function edunexo_is_valid_simple_text(string $value, int $max = 120): bool
    {
        return $value !== '' && mb_strlen($value) <= $max && preg_match('/^[\p{L}\p{N}ÁÉÍÓÚÜÑáéíóúüñ.,#()\/\'\- ]+$/u', $value) === 1;
    }
}

if (!function_exists('edunexo_is_valid_phone')) {
    function edunexo_is_valid_phone(string $value): bool
    {
        return preg_match('/^\+?[0-9][0-9\s().-]{7,18}$/', $value) === 1;
    }
}

if (!function_exists('edunexo_is_valid_curp')) {
    function edunexo_is_valid_curp(string $value): bool
    {
        return preg_match('/^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[A-Z0-9][0-9]$/', strtoupper($value)) === 1;
    }
}

if (!function_exists('edunexo_is_valid_rfc')) {
    function edunexo_is_valid_rfc(string $value): bool
    {
        return preg_match('/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/', strtoupper($value)) === 1;
    }
}

if (!function_exists('edunexo_is_valid_control_number')) {
    function edunexo_is_valid_control_number(string $value): bool
    {
        return preg_match('/^[A-Za-z0-9-]{4,20}$/', $value) === 1;
    }
}

if (!function_exists('edunexo_is_valid_postal_code')) {
    function edunexo_is_valid_postal_code(string $value): bool
    {
        return preg_match('/^[0-9]{5}$/', $value) === 1;
    }
}

if (!function_exists('edunexo_is_valid_password')) {
    function edunexo_is_valid_password(string $value): bool
    {
        return strlen($value) >= 8 && preg_match('/[A-Za-z]/', $value) === 1 && preg_match('/[0-9]/', $value) === 1;
    }
}

if (!function_exists('edunexo_add_error_if')) {
    function edunexo_add_error_if(bool $condition, array &$errors, string $message): void
    {
        if ($condition) {
            $errors[] = $message;
        }
    }
}

if (!function_exists('edunexo_throw_validation_errors')) {
    function edunexo_throw_validation_errors(array $errors): void
    {
        if (!empty($errors)) {
            throw new InvalidArgumentException(implode(' ', $errors));
        }
    }
}
