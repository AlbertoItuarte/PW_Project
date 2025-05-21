<?php
session_start();
require_once '../Config/dbConection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener datos del formulario
    $username = $conn->real_escape_string($_POST['nombre_usuario']);
    $password = $_POST['password'];
    
    // Consulta para verificar las credenciales (usando prepared statements)
    $sql = "SELECT id, nombre_usuario, contrasena FROM usuario WHERE nombre_usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        
        // Verificar contraseña con password_verify 
        if (password_verify($password, $row['contrasena'])) {
            // Contraseña correcta, iniciar sesión
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['nombre_usuario'];
            
            // Redirigir a la página de inicio
            header("Location: ../Pages/Home.php");
            exit();
        } else {
            // Contraseña incorrecta
            header("Location: ../Pages/Login.php?error=invalid_password");
            exit();
        }
    } else {
        // Usuario no encontrado
        header("Location: ../Pages/Login.php?error=user_not_found");
        exit();
    }
}
