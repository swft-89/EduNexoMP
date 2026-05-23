<?php
$localConfigPath = __DIR__ . '/conexion.local.php';
$localConfig = is_file($localConfigPath) ? require $localConfigPath : [];

$host = getenv('EDUNEXO_DB_HOST') ?: ($localConfig['host'] ?? 'localhost');
$port = getenv('EDUNEXO_DB_PORT') ?: ($localConfig['port'] ?? '5432');
$dbname = getenv('EDUNEXO_DB_NAME') ?: ($localConfig['dbname'] ?? 'edunexo_mp');
$user = getenv('EDUNEXO_DB_USER') ?: ($localConfig['user'] ?? 'postgres');
$password = getenv('EDUNEXO_DB_PASSWORD') ?: ($localConfig['password'] ?? '');

try {
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    $pdo->exec("SET NAMES 'UTF8'");
} catch (PDOException $e) {
    error_log('Error de conexion a la base de datos: ' . $e->getMessage());
    die('No se pudo conectar con la base de datos. Intenta nuevamente mas tarde.');
}
