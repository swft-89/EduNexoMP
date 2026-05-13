<?php

function edunexo_ensure_help_tables(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ayuda_sugerencia (
            id_sugerencia SERIAL PRIMARY KEY,
            tipo VARCHAR(30) NOT NULL,
            titulo VARCHAR(150) NOT NULL,
            descripcion TEXT NULL,
            categoria_habilidad VARCHAR(100) NULL,
            estado VARCHAR(20) NOT NULL DEFAULT 'pendiente',
            respuesta_admin TEXT NULL,
            id_usuario INTEGER NULL REFERENCES usuario(id_usuario) ON DELETE SET NULL,
            revisado_por INTEGER NULL REFERENCES usuario(id_usuario) ON DELETE SET NULL,
            fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            fecha_revision TIMESTAMP NULL
        )
    ");
}
