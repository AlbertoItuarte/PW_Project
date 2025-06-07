<?php
session_start();
if (!isset($_SESSION["user_id"]) )
{
    header("Location: Login.php");
    exit();
} elseif (isset($_SESSION["user_id"]) && isset($_SESSION["user_type"]) && $_SESSION["user_type"] == "Usuario") {
    header("Location: HomeUser.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="../CSS/Global.css">
    <link rel="stylesheet" href="../CSS/Register.css">
</head>
<body>
    <div>
        <form action="../Logic/Register.php" id="formulario" method="post">
            <h2>SmartSchedule</h2>
            <input type="text" name="nombre_usuario" placeholder="Nombre de usuario" required>
            <input type="text" name="nombre" placeholder="Nombre" required>
            <input type="text" name="apellido_paterno" placeholder="Apellido paterno" required>
            <input type="password" name="contrasena" placeholder="Ingresa tu contraseña" required>
            <input type="password" name="confirma_contrasena" placeholder="Confirma contraseña" required>
            <input type="submit" value="Regístrate">
        </form>
    </div>
</body>
</html>