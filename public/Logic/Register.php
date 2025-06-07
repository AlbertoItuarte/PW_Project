<?php
require_once '../Config/dbConection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Debug: Ver qué datos están llegando
    error_log("POST data: " . print_r($_POST, true));

    $username = trim($_POST['nombre_usuario'] ?? '');
    $password = trim($_POST['contrasena'] ?? '');
    $confirm_password = trim($_POST['confirma_contrasena'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido_paterno = trim($_POST['apellido_paterno'] ?? '');

    // Debug: Ver los valores después de procesarlos
    error_log("Processed values - username: '$username', nombre: '$nombre', apellido_paterno: '$apellido_paterno'");

    // Verificar que todos los campos requeridos estén presentes y no vacíos
    if (empty($username) || empty($password) || empty($confirm_password) || empty($nombre) || empty($apellido_paterno)) {
        error_log("Empty fields detected");
        header("Location: ../Pages/Register.php?error=empty_fields");
        exit();
    }

    if ($password !== $confirm_password) {
        error_log("Password mismatch");
        header("Location: ../Pages/Register.php?error=password_mismatch");
        exit();
    }
    
    $sql = "SELECT usuario_id FROM usuario WHERE usuario = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username]);

    if ($stmt->rowCount() > 0) {
        error_log("User already exists");
        header("Location: ../Pages/Register.php?error=user_exists");
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Debug: Ver los valores que se van a insertar
    error_log("Values to insert: nombre='$nombre', apellido_paterno='$apellido_paterno', username='$username'");

    // Insertar el usuario con tipo_usuario por defecto como 'Usuario'
    $sql = "INSERT INTO usuario (nombre, apellido_paterno, usuario, contrasena, tipo) 
            VALUES (?, ?, ?, ?, 'Usuario')";
    $stmt = $pdo->prepare($sql);

    try {
        if ($stmt->execute([$nombre, $apellido_paterno, $username, $hashed_password])) {
            error_log("User registered successfully");
            header("Location: ../Pages/Login.php?success=registered");
            exit();
        } else {
            error_log("Database insertion failed");
            header("Location: ../Pages/Register.php?error=database");
            exit();
        }
    } catch (Exception $e) {
        error_log("Exception during insertion: " . $e->getMessage());
        header("Location: ../Pages/Register.php?error=database");
        exit();
    }
}
?>