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
    <title>Home - Profesor</title>
</head>
<body>
    <nav>
        <ul>
            <li><a href="HomeUser.php">Inicio</a></li>
            <li><a href="SelectSubject.php">Seleccionar Materias</a></li>
            <li><a href="../Logic/LogOut.php">Cerrar sesión</a></li>
        </ul>
    </nav>

    <h1>Materias Asignadas</h1>
    <div>
        <?php
        require_once '../Config/dbConection.php';

        // Obtener el ID del usuario desde la sesión
        $user_id = $_SESSION['user_id'];

        // Consultar las materias asignadas al profesor
        $sql = "SELECT m.nombre AS materia, ge.fecha_evaluacion, mc.materia_ciclo_id
                FROM materia m
                INNER JOIN materia_ciclo mc ON m.materia_id = mc.materia_id
                LEFT JOIN grupo_evaluacion ge ON mc.materia_ciclo_id = ge.materia_ciclo_id
                WHERE mc.usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "<table border='1'>
                    <tr>
                        <th>Materia</th>
                        <th>Fecha Evaluación</th>
                        <th>Acciones</th>
                    </tr>";
            while ($row = $result->fetch_assoc()) {
                $materia = htmlspecialchars($row['materia']);
                $fecha_evaluacion = $row['fecha_evaluacion'] ? htmlspecialchars($row['fecha_evaluacion']) : "Sin fecha";
                $materia_ciclo_id = $row['materia_ciclo_id'];

                echo "<tr>
                        <td>{$materia}</td>
                        <td>{$fecha_evaluacion}</td>
                        <td>
                            <a href='EditarEvaluacion.php?materia_ciclo_id={$materia_ciclo_id}'>Editar Fecha</a> |
                            <a href='PlanificarDia.php?materia_ciclo_id={$materia_ciclo_id}'>Planificar Día</a>
                        </td>
                      </tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No tienes materias asignadas.</p>";
        }
        ?>
    </div>
</body>
</html>