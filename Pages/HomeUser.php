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
    <link rel="stylesheet" href="../CSS/HomeUser.css">

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

        // Consultar las materias asignadas al usuario desde la tabla usuario_materia_ciclo
        $sql = "SELECT m.nombre AS materia, ge.fecha_evaluacion, mc.materia_ciclo_id
                FROM usuario_materia_ciclo umc
                INNER JOIN materia_ciclo mc ON umc.materia_ciclo_id = mc.materia_ciclo_id
                INNER JOIN materia m ON mc.materia_id = m.materia_id
                LEFT JOIN grupo_evaluacion ge ON mc.materia_ciclo_id = ge.materia_ciclo_id
                WHERE umc.usuario_id = ?";
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

                // Verificar si la materia tiene planificación
                $sql_planificacion = "SELECT COUNT(*) AS total FROM distribucion WHERE tema_id IN (
                                        SELECT t.tema_id
                                        FROM tema t
                                        INNER JOIN unidad u ON t.unidad_id = u.unidad_id
                                        WHERE u.materia_ciclo_id = ?
                                    )";
                $stmt_planificacion = $conn->prepare($sql_planificacion);
                $stmt_planificacion->bind_param("i", $materia_ciclo_id);
                $stmt_planificacion->execute();
                $result_planificacion = $stmt_planificacion->get_result();
                $row_planificacion = $result_planificacion->fetch_assoc();
                $tiene_planificacion = $row_planificacion['total'] > 0;

                echo "<tr>
                        <td>{$materia}</td>
                        <td>{$fecha_evaluacion}</td>
                        <td>";
                if ($tiene_planificacion) {
                    echo "<a href='ViewPlan.php?materia_ciclo_id={$materia_ciclo_id}'>Ver Planificación</a>";
                } else {
                    echo "<a href='PlanificarMateria.php?materia_ciclo_id={$materia_ciclo_id}'>Planificar</a>";
                }
                echo "</td>
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