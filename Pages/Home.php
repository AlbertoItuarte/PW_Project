<?php
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio</title>
    <link rel="stylesheet" href="../CSS/Global.css">
    <link rel="stylesheet" href="../CSS/Home.css">
</head>
<body>
    <div>
        <nav>
            <ul>
                <li><a href="Home.php">Inicio</a></li>
               <li><a href="./PlanSubject.php">Crear materia</a></li>
                <li><a href="../Logic/LogOut.php">Cerrar sesión</a></li>
            </ul>
        </nav>
    </div>
    <div>
        <h1>Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
        <p>Has iniciado sesión correctamente.</p>
    </div>
</body>
</html>