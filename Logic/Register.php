<?php
require_once '../Config/dbConection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        header("Location: ../Pages/Register.php?error=password_mismatch");
        exit();
    }
    
    $sql = "SELECT id FROM users WHERE user_name = '$username' OR email = '$email'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        header("Location: ../Pages/Register.php?error=user_exists");
        exit();
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (user_name, email, password) VALUES ('$username', '$email', '$hashed_password')";
    if ($conn->query($sql) === TRUE) {
        header("Location: ../Pages/Login.php?success=registered");
        exit();
    } else {
        header("Location: ../Pages/Register.php?error=database");
        exit();
    }
}
