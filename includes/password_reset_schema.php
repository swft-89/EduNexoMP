<?php
if (!function_exists('edunexo_ensure_password_reset_table')) {
    function edunexo_ensure_password_reset_table(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS password_reset (
                id_reset SERIAL PRIMARY KEY,
                id_usuario INTEGER NOT NULL REFERENCES usuario(id_usuario) ON DELETE CASCADE,
                codigo_hash VARCHAR(255) NOT NULL,
                fecha_expiracion TIMESTAMP NOT NULL,
                usado BOOLEAN NOT NULL DEFAULT FALSE,
                fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $pdo->exec("
            CREATE INDEX IF NOT EXISTS idx_password_reset_usuario
            ON password_reset (id_usuario, usado, fecha_expiracion)
        ");
    }
}
