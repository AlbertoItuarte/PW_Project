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
            <input type="text" name="nombre_usuario" placeholder="Ingresa tu usuario" required>
            <input type="password" name="contrasena" placeholder="Ingresa tu contraseña" required>
            <input type="submit" value="Login">
            <p>No tienes cuenta? <a href="Register.php">Regístrate aquí</a></p>
        </form>
    </div>
</body>
</html>