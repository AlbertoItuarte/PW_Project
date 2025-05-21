<?php
session_start();

// Incluir archivo de conexión
require_once '../Config/dbConection.php';

// Verificar si el formulario fue enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener datos del formulario
    $username = $conn->real_escape_string($_POST['user_name']);
    $password = $_POST['password'];
    
    // Consulta para verificar las credenciales
    $sql = "SELECT id, user_name, password FROM users WHERE user_name = '$username'";
    $result = $conn->query($sql);
    
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        // Verificar contraseña
        if (password_verify($password, $row['password'])) {
            // Contraseña correcta, iniciar sesión
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['user_name'];
            
            // Redirigir a la página de inicio
            header("Location: ../Pages/Home.php");
            exit();
        } else {
            // Contraseña incorrecta
            header("Location: ../Pages/Login.php?error=invalid");
            exit();
        }
    } else {
        // Usuario no encontrado
        header("Location: ../Pages/Login.php?error=invalid");
        exit();
    }
}
?>