<?php
$host = "127.0.0.1";      
$user = "root";          
$password = "";          
$database = "sistema_dosificacion"; 

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
echo "Conexión exitosa a la base de datos";
$conn->set_charset("utf8");
