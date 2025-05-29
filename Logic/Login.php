<?php
session_start();
require_once '../Config/dbConection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener datos del formulario
    $username = $conn->real_escape_string($_POST['nombre_usuario']);
    $password = $_POST['contrasena'];
    
    // Consulta para verificar las credenciales (incluyendo tipo_usuario y usuario)
    $sql = "SELECT usuario_id, nombre, usuario, contrasena, tipo FROM usuario WHERE usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        
        // Verificar contraseña con password_verify 
        if (password_verify($password, $row['contrasena'])) {
            // Contraseña correcta, iniciar sesión
            $_SESSION['user_id'] = $row['usuario_id'];
            $_SESSION['username'] = $row['usuario'];
            $_SESSION['name'] = $row['nombre'];
            $_SESSION['user_type'] = $row['tipo'];
            
            // Redirigir según el tipo de usuario
            if ($row['tipo'] == 'Admin') {
                header("Location: ../Pages/Home.php");
            } else {
                header("Location: ../Pages/HomeUser.php");
            }
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
?>