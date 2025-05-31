<?php
try {
    // Cargar variables de entorno
    $host = getenv('DB_HOST') ?: $_ENV['DB_HOST'] ?? 'aws-0-us-east-2.pooler.supabase.com';
    $port = getenv('DB_PORT') ?: $_ENV['DB_PORT'] ?? '5432';
    $dbname = getenv('DB_NAME') ?: $_ENV['DB_NAME'] ?? 'postgres';
    $username = getenv('DB_USER') ?: $_ENV['DB_USER'] ?? 'postgres.nvpssgymlrutawwdjntd';
    $password = getenv('DB_PASSWORD') ?: $_ENV['DB_PASSWORD'] ?? 'Beto1702?12';
    
    // Crear la cadena DSN para PostgreSQL con SSL
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    
    // Crear la conexión PDO
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    if (getenv('APP_ENV') === 'development') {
        die("Error de conexión: " . $e->getMessage());
    } else {
        die("Error de conexión a la base de datos. Por favor contacte al administrador.");
    }
}
?>