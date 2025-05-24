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
        </ul>
    </nav>

    <h1>Materias Asignadas</h1>
    <div>
        <?php
        session_start();
        require_once '../Config/dbConection.php';

        // Obtener el ID del usuario desde la sesión
        $user_id = $_SESSION['user_id'];

        // Consultar las materias asignadas al profesor
        $sql = "SELECT p.materia, pu.fecha_evaluacion 
                FROM programa_usuario pu
                INNER JOIN programa p ON pu.programa_id = p.id
                WHERE pu.usuario_id = ?";
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
                echo "<tr>
                        <td>{$row['materia']}</td>
                        <td>{$row['fecha_evaluacion']}</td>
                        <td>
                            <a href='EditarEvaluacion.php?materia={$row['materia']}'>Editar Fecha</a> |
                            <a href='PlanificarDia.php?materia={$row['materia']}'>Planificar Día</a>
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