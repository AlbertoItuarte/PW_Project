<?php
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

// Verificar si el usuario es administrador
if ($_SESSION['user_type'] !== 'Admin') {
    header("Location: HomeUser.php");
    exit();
}

// Conectar a la base de datos
require_once '../Config/dbConection.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Ciclos Escolares</title>
    <link rel="stylesheet" href="../CSS/Global.css">
</head>
<body>
    <h1>Gestionar Ciclos Escolares</h1>
    <form action="../Logic/InsertCycle.php" method="POST">
        <h2>Agregar Ciclo Escolar</h2>
        <label for="nombre">Nombre del Ciclo:</label>
        <input type="text" id="nombre" name="nombre" required>
        <label for="fecha_inicio">Fecha de Inicio:</label>
        <input type="date" id="fecha_inicio" name="fecha_inicio" required>
        <label for="fecha_fin">Fecha de Fin:</label>
        <input type="date" id="fecha_fin" name="fecha_fin" required>
        <button type="submit">Agregar Ciclo</button>
    </form>

    <form action="../Logic/InsertHoliday.php" method="POST">
        <h2>Agregar Día Feriado</h2>
        <label for="ciclo_id">Ciclo:</label>
        <select id="ciclo_id" name="ciclo_id" required>
            <?php
            $sql = "SELECT ciclo_id, nombre FROM ciclo";
            $result = $conn->query($sql);
            while ($row = $result->fetch_assoc()) {
                echo "<option value='{$row['ciclo_id']}'>{$row['nombre']}</option>";
            }
            ?>
        </select>
        <label for="dia">Fecha:</label>
        <input type="date" id="dia" name="dia" required>
        <label for="causa">Causa:</label>
        <input type="text" id="causa" name="causa" required>
        <button type="submit">Agregar Feriado</button>
    </form>

    <form action="../Logic/InsertVacation.php" method="POST">
        <h2>Agregar Vacaciones</h2>
        <label for="ciclo_id">Ciclo:</label>
        <select id="ciclo_id" name="ciclo_id" required>
            <?php
            $sql = "SELECT ciclo_id, nombre FROM ciclo";
            $result = $conn->query($sql);
            while ($row = $result->fetch_assoc()) {
                echo "<option value='{$row['ciclo_id']}'>{$row['nombre']}</option>";
            }
            ?>
        </select>
        <label for="fecha_inicio">Fecha de Inicio:</label>
        <input type="date" id="fecha_inicio" name="fecha_inicio" required>
        <label for="fecha_fin">Fecha de Fin:</label>
        <input type="date" id="fecha_fin" name="fecha_fin" required>
        <label for="descripcion">Descripción:</label>
        <input type="text" id="descripcion" name="descripcion" required>
        <button type="submit">Agregar Vacaciones</button>
    </form>
</body>
</html>