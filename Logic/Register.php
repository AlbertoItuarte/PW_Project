<?php
require_once '../Config/dbConection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = $conn->real_escape_string($_POST['nombre_usuario']);
    $email = $conn->real_escape_string($_POST['correo']);
    $password = $_POST['contrasena'];
    $confirm_password = $_POST['confirma_contrasena'];
    // Removed echo statement to prevent exposing sensitive information.
    
    // Valores por defecto para los campos adicionales
    $nombre = $conn->real_escape_string($_POST['nombre'] ?? $username);
    $apellido_paterno = $conn->real_escape_string($_POST['apellido_paterno'] ?? '');

    if ($password !== $confirm_password) {
        header("Location: ../Pages/Register.php?error=password_mismatch");
        exit();
    }
    
    $sql = "SELECT id FROM usuario WHERE nombre_usuario = ? OR correo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        header("Location: ../Pages/Register.php?error=user_exists");
        exit();
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO usuario (nombre_usuario, nombre, apellido_paterno, correo, contrasena) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $username, $nombre, $apellido_paterno, $email, $hashed_password);
    
    if ($stmt->execute()) {
        header("Location: ../Pages/Login.php?success=registered");
        exit();
    } else {
        header("Location: ../Pages/Register.php?error=database");
        exit();
    }
}