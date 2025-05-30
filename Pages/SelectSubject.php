<?php
session_start();
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
    <link rel="stylesheet" href="../CSS/Global.css">
    <link rel="stylesheet" href="../CSS/SelectSubject.css">
    <title>Seleccionar Materias</title>
</head>
<body>
    <h1>Seleccionar Materias</h1>
    <form action="../Logic/AsignSubject.php" method="POST">
        <div class="materias-container">
        <?php
        require_once '../Config/dbConection.php';

        // Obtener el ID del usuario desde la sesión
        $user_id = $_SESSION['user_id'];

        // Consultar todas las materias que no están asignadas al usuario actual
        $sql = "SELECT mc.materia_ciclo_id, m.nombre AS materia
                FROM materia_ciclo mc
                INNER JOIN materia m ON mc.materia_id = m.materia_id
                WHERE mc.materia_ciclo_id NOT IN (
                    SELECT umc.materia_ciclo_id
                    FROM usuario_materia_ciclo umc
                    WHERE umc.usuario_id = ?
                )";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<div class='materia-item'>
                        <label>{$row['materia']}</label>
                        <input type='checkbox' name='materias[]' value='{$row['materia_ciclo_id']}'>
                      </div>";
            }
        } else {
            echo "<p>No hay materias disponibles para selección.</p>";
        }
        ?>
        </div>
        <button type="submit">Cargar Materias</button>
    </form>
</body>
</html>