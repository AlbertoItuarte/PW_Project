<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

require_once '../Config/dbConection.php';

// Verificar si se recibió el parámetro materia_ciclo_id
if (!isset($_GET['materia_ciclo_id'])) {
    die("Error: No se especificó una materia.");
}

$materia_ciclo_id = intval($_GET['materia_ciclo_id']);

// Consultar los detalles de la planificación
$sql_planificacion = "SELECT m.nombre AS materia, u.nombre AS unidad, ge.fecha_evaluacion, 
                             t.nombre AS tema, d.fecha_inicio, d.fecha_fin
                      FROM distribucion d
                      INNER JOIN tema t ON d.tema_id = t.tema_id
                      INNER JOIN unidad u ON t.unidad_id = u.unidad_id
                      INNER JOIN materia_ciclo mc ON u.materia_ciclo_id = mc.materia_ciclo_id
                      INNER JOIN materia m ON mc.materia_id = m.materia_id
                      LEFT JOIN grupo_evaluacion ge ON mc.materia_ciclo_id = ge.materia_ciclo_id
                      WHERE u.materia_ciclo_id = ?
                      ORDER BY u.nombre, d.fecha_inicio";
$stmt = $conn->prepare($sql_planificacion);
$stmt->bind_param("i", $materia_ciclo_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planificación de Materia</title>
</head>
<body>
    <nav>
        <ul>
            <li><a href="HomeUser.php">Inicio</a></li>
            <li><a href="../Logic/LogOut.php">Cerrar sesión</a></li>
        </ul>
    </nav>

    <h1>Planificación de la Materia</h1>

    <?php if ($result->num_rows > 0): ?>
        <?php
        $current_unidad = null;
        $current_materia = null;

        while ($row = $result->fetch_assoc()):
            $materia = htmlspecialchars($row['materia']);
            $unidad = htmlspecialchars($row['unidad']);
            $fecha_evaluacion = $row['fecha_evaluacion'] ? htmlspecialchars($row['fecha_evaluacion']) : "Sin fecha";
            $tema = htmlspecialchars($row['tema']);
            $fecha_inicio = htmlspecialchars($row['fecha_inicio']);
            $fecha_fin = htmlspecialchars($row['fecha_fin']);

            // Mostrar la materia si cambia
            if ($materia !== $current_materia) {
                echo "<h2>MATERIA: {$materia}</h2>";
                $current_materia = $materia;
            }

            // Mostrar la unidad si cambia
            if ($unidad !== $current_unidad) {
                echo "<h3>- Unidad: {$unidad} / Fecha de Evaluación: {$fecha_evaluacion}</h3>";
                $current_unidad = $unidad;
            }

            // Mostrar el tema
            echo "<p>-- Tema: {$tema} / Fecha Inicio: {$fecha_inicio} / Fecha Fin: {$fecha_fin}</p>";
        endwhile;
        ?>
    <?php else: ?>
        <p>No hay planificación registrada para esta materia.</p>
    <?php endif; ?>

</body>
</html>