<?php
    session_start();
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] == "Admin") {
        header("Location: Home.php");
        exit();
    }elseif (isset($_SESSION["user_id"]) && isset($_SESSION["user_type"]) && $_SESSION["user_type"] == "Usuario") {
        header("Location: HomeUser.php");
        exit();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicia sesión</title>
    <link rel="stylesheet" href="../CSS/auth.css">
    <link rel="stylesheet" href="../CSS/Global.css">
</head>
<body>
    <div>
        <form action="../Logic/Login.php" method="post">
            <h1>SmartSchedule</h1>
            <input type="text" name="nombre_usuario" placeholder="Ingresa tu usuario" required>
            <input type="password" name="contrasena" placeholder="Ingresa tu contraseña" required>
            <input type="submit" value="Login">
        </form>
    </div>
</body>
</html>