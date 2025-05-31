<?php
// Configurar headers para evitar caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Verificar si es un health check
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

// Si es un health check directo o de Railway
if (isset($_GET['health']) || 
    strpos($requestUri, 'health') !== false || 
    strpos($userAgent, 'railway') !== false ||
    strpos($userAgent, 'healthcheck') !== false) {
    
    // Responder OK y terminar
    http_response_code(200);
    echo "OK";
    exit();
}

// Respuesta básica para navegadores
http_response_code(200);
echo "<!DOCTYPE html>
<html>
<head>
    <title>SmartSchedule</title>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta http-equiv='refresh' content='3;url=Pages/Login.php'>
</head>
<body style='font-family: Arial, sans-serif; text-align: center; margin-top: 100px; background-color: #eaf7f7;'>
    <h1 style='color: #043333;'>🚀 SmartSchedule</h1>
    <p style='color: #2e6e6e;'>Aplicación funcionando correctamente</p>
    <p style='color: #2e6e6e;'>Serás redirigido al login en 3 segundos...</p>
    <p><a href='Pages/Login.php' style='color: #2e6e6e; font-weight: bold;'>Ir ahora al Login</a></p>
    <script>
        setTimeout(function() {
            window.location.href = 'Pages/Login.php';
        }, 3000);
    </script>
</body>
</html>";
?>