<?php

function edunexo_ensure_student_interests_column(PDO $pdo): void
{
    $pdo->exec("ALTER TABLE estudiante ADD COLUMN IF NOT EXISTS intereses TEXT");
}
