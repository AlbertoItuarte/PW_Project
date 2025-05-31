<?php
// Configuración de errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar que la aplicación esté funcionando
if (!headers_sent()) {
    // Verificar si es un health check o acceso directo
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    
    // Si es un health check de Railway, responder OK
    if (strpos($userAgent, 'railway') !== false || strpos($userAgent, 'healthcheck') !== false) {
        echo "OK";
        exit();
    }
    
    // Verificar que los archivos necesarios existan
    if (file_exists('Pages/Login.php') && file_exists('Config/dbConection.php')) {
        // Todo está bien, redirigir
        header("Location: Pages/Login.php");
        exit();
    } else {
        // Debug: mostrar qué falta
        echo "<h1>🔍 Debug - Archivos faltantes</h1>";
        echo "<p>Verificando estructura...</p>";
        
        echo "<h3>Estado de archivos críticos:</h3>";
        echo "Pages/Login.php: " . (file_exists('Pages/Login.php') ? '✅' : '❌') . "<br>";
        echo "Config/dbConection.php: " . (file_exists('Config/dbConection.php') ? '✅' : '❌') . "<br>";
        
        echo "<h3>Contenido del directorio actual:</h3>";
        $files = scandir('.');
        foreach($files as $file) {
            if($file != '.' && $file != '..') {
                $type = is_dir($file) ? '📁' : '📄';
                echo "$type $file<br>";
            }
        }
        
        if (is_dir('Pages')) {
            echo "<h3>Contenido de Pages/:</h3>";
            $pageFiles = scandir('Pages');
            foreach($pageFiles as $file) {
                if($file != '.' && $file != '..') {
                    echo "📄 $file<br>";
                }
            }
        }
        
        echo "<br><a href='health.php'>🏥 Health Check</a>";
    }
} else {
    echo "Headers already sent";
}
?>