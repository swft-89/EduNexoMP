<?php

if (!function_exists('edunexo_carreras_estudiante')) {
    function edunexo_carreras_estudiante(): array
    {
        return [
            'Ingenieria en Sistemas Computacionales',
            'Ingenieria en Tecnologias de la Informacion y Comunicaciones',
            'Ingenieria Industrial',
            'Ingenieria Electromecanica',
            'Ingenieria Electronica',
            'Ingenieria Mecanica',
            'Ingenieria Mecatronica',
            'Ingenieria en Gestion Empresarial',
            'Contador Publico',
            'Licenciatura en Administracion',
        ];
    }
}

if (!function_exists('edunexo_carrera_otra_value')) {
    function edunexo_carrera_otra_value(): string
    {
        return 'Otra';
    }
}

if (!function_exists('edunexo_carrera_estudiante_valida')) {
    function edunexo_carrera_estudiante_valida(string $carrera): bool
    {
        return in_array($carrera, edunexo_carreras_estudiante(), true);
    }
}
