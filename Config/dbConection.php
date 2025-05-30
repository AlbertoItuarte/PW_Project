<?php
try {
    // Configuración de Supabase
    $host = 'aws-0-us-east-2.pooler.supabase.com';
    $port = '5432';
    $dbname = 'postgres';
    $username = 'postgres.nvpssgymlrutawwdjntd';
    $password = 'Beto1702?12';  // Tu contraseña real
    
    // Crear la cadena DSN para PostgreSQL
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    
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