<?php
session_start();

// Solo admins pueden acceder
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != "Admin") {
    header("Location: Login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Ciclo</title>
    <link rel="stylesheet" href="../CSS/Global.css">
    <link rel="stylesheet" href="../CSS/EditSubject.css">
    <link rel="stylesheet" href="../CSS/CreateCycle.css">
</head>
<body>
    <nav>
        <ul>
            <li><a href="Home.php">Inicio</a></li>
            <li><a href="PlanSubject.php">Crear materia</a></li>
            <li><a href="CreateCycle.php" id="btn-create-ciclo">Crear ciclo</a></li>
            <li><a href="../Logic/LogOut.php">Cerrar sesión</a></li>
        </ul>
    </nav>
    <form class="form-ciclo" method="post" action="../API/Cycle/CreateCycle.php">
        <h2>Crear Ciclo</h2>
        <?php if (!empty($error)): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <label for="nombre_ciclo">Nombre del ciclo:</label>
        <input type="text" id="nombre_ciclo" name="nombre" required maxlength="100">

        <label for="fecha_inicio">Fecha de inicio:</label>
        <input type="date" id="fecha_inicio" name="fecha_inicio" required>

        <label for="fecha_fin">Fecha de fin:</label>
        <input type="date" id="fecha_fin" name="fecha_fin" required>

        <button type="submit">Crear ciclo</button>
    </form>
</body>
</html>