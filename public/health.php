<?php
// Test básico de PHP
echo "STATUS: OK<br>";
echo "TIME: " . date('Y-m-d H:i:s') . "<br>";
echo "PHP: " . phpversion() . "<br>";
echo "PORT: " . ($_ENV['PORT'] ?? 'Not set') . "<br>";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "<br>";

// Listar archivos
echo "<h3>Files in current directory:</h3>";
$files = scandir('.');
foreach($files as $file) {
    if($file != '.' && $file != '..') {
        echo "- $file<br>";
    }
}
?>