<?php
// Configuración básica
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Si es health check, responder directamente
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
if (strpos($userAgent, 'railway') !== false || strpos($userAgent, 'healthcheck') !== false) {
    echo "OK";
    exit();
}

// Verificar archivos críticos y redirigir
if (file_exists('Pages/Login.php')) {
    header("Location: Pages/Login.php");
    exit();
} else {
    // Debug básico
    echo "<h1>❌ Error: Archivos no encontrados</h1>";
    echo "<p>Pages/Login.php: " . (file_exists('Pages/Login.php') ? '✅' : '❌') . "</p>";
    echo "<p><a href='test.php'>Ver diagnóstico completo</a></p>";
}
?>