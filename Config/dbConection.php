<?php
try {
    // Usar variables de entorno en producción, valores por defecto en desarrollo
    $host = $_ENV['DB_HOST'] ?? 'aws-0-us-east-2.pooler.supabase.com';
    $port = $_ENV['DB_PORT'] ?? '5432';
    $dbname = $_ENV['DB_NAME'] ?? 'postgres';
    $username = $_ENV['DB_USER'] ?? 'postgres.nvpssgymlrutawwdjntd';
    $password = $_ENV['DB_PASSWORD'] ?? 'Beto1702?12';
    
    // Crear la cadena DSN para PostgreSQL con SSL
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    
    // Crear la conexión PDO
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>