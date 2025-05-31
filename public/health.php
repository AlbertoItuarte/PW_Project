<?php
// Verificar si es un health check
if (isset($_GET['health']) || strpos($_SERVER['REQUEST_URI'] ?? '', 'health') !== false) {
    echo "OK";
    exit();
}

// Respuesta básica para verificar que funciona
echo "<!DOCTYPE html>
<html>
<head>
    <title>SmartSchedule</title>
    <meta http-equiv='refresh' content='2;url=Pages/Login.php'>
</head>
<body>
    <h1>🚀 SmartSchedule</h1>
    <p>Iniciando aplicación...</p>
    <p>Serás redirigido en 2 segundos...</p>
    <p><a href='Pages/Login.php'>Ir ahora</a></p>
    <script>
        setTimeout(function() {
            window.location.href = 'Pages/Login.php';
        }, 2000);
    </script>
</body>
</html>";
?>