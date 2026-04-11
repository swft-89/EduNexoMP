<?php
$host = "localhost";
$port = "5432";
$dbname = "edunexo_mp";
$user = "postgres";
$password = "Avengers89";

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
    die("Error de conexión: " . $e->getMessage());
}
?>