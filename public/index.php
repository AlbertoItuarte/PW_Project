<?php
// Debug mode
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>✅ PHP está funcionando</h1>";
echo "<p>Hora: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>Directorio actual: " . __DIR__ . "</p>";

// Verificar si existe la carpeta Pages
if (is_dir('Pages')) {
    echo "<p>✅ Carpeta Pages existe</p>";
    if (file_exists('Pages/Login.php')) {
        echo "<p>✅ Login.php existe</p>";
        echo "<p><a href='Pages/Login.php'>Ir a Login</a></p>";
    } else {
        echo "<p>❌ Login.php NO existe</p>";
    }
} else {
    echo "<p>❌ Carpeta Pages NO existe</p>";
    echo "<p>Archivos disponibles:</p><ul>";
    $files = scandir('.');
    foreach($files as $file) {
        if($file != '.' && $file != '..') {
            echo "<li>$file</li>";
        }
    }
    echo "</ul>";
}

// Comentar la redirección por ahora
// header("Location: Pages/Login.php");
// exit();
?>