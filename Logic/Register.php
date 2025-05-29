<?php
require_once '../Config/dbConection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = $conn->real_escape_string($_POST['nombre_usuario']);
    $password = $_POST['contrasena'];
    $confirm_password = $_POST['confirma_contrasena'];
    
    // Valores por defecto para los campos adicionales
    $nombre = $conn->real_escape_string($_POST['nombre'] ?? $username);
    $apellido_paterno = $conn->real_escape_string($_POST['apellido_paterno'] ?? '');

    if ($password !== $confirm_password) {
        header("Location: ../Pages/Register.php?error=password_mismatch");
        exit();
    }
    
    $sql = "SELECT usuario_id FROM usuario WHERE usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        header("Location: ../Pages/Register.php?error=user_exists");
        exit();
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insertar el usuario con tipo_usuario por defecto como 'Usuario'
    $sql = "INSERT INTO usuario (nombre, apellido_paterno, usuario, contrasena, tipo) 
            VALUES (?, ?, ?, ?, ?, 'Usuario')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss",$nombre, $apellido_paterno, $username, $hashed_password);
    
    if ($stmt->execute()) {
        header("Location: ../Pages/Login.php?success=registered");
        exit();
    } else {
        header("Location: ../Pages/Register.php?error=database");
        exit();
    }
}