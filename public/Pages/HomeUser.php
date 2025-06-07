<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}
if(isset($_POST["usuario_id"]) && $_SESSION["user_type"] != "Admin") {
    header("Location: Home.php");
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
            <li><a href="SelectSubject.php">Materias</a></li>
            <li><a href="../Logic/LogOut.php">Cerrar sesión</a></li>
        </ul>
    </nav>

    <h1>Materias Asignadas</h1>
    <div>
        <?php
        require_once '../Config/dbConection.php';

        // Obtener el ID del usuario desde la sesión
        $user_id = $_SESSION['user_id'];

        // Consultar TODAS las materias asignadas al usuario incluyendo usuario_materia_ciclo_id
        $sql = "SELECT m.nombre AS materia, mc.materia_ciclo_id, umc.fecha_asignacion, umc.usuario_materia_ciclo_id,
                       ROW_NUMBER() OVER (PARTITION BY m.materia_id ORDER BY umc.fecha_asignacion) as numero_asignacion
                FROM usuario_materia_ciclo umc
                INNER JOIN materia_ciclo mc ON umc.materia_ciclo_id = mc.materia_ciclo_id
                INNER JOIN materia m ON mc.materia_id = m.materia_id
                WHERE umc.usuario_id = ?
                ORDER BY m.nombre ASC, umc.fecha_asignacion ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);

        if ($stmt->rowCount() > 0) {
            echo "<table border='1'>
                    <tr>
                        <th>Materia</th>
                        <th>Asignación #</th>
                        <th>Acciones</th>
                    </tr>";
            
            while ($row = $stmt->fetch()) {
                $materia = htmlspecialchars($row['materia']);
                $materia_ciclo_id = $row['materia_ciclo_id'];
                $usuario_materia_ciclo_id = $row['usuario_materia_ciclo_id'];
                $numero_asignacion = $row['numero_asignacion'];

                // Verificar si ESTA ASIGNACIÓN ESPECÍFICA tiene planificación
                // usando usuario_materia_ciclo_id en la tabla distribucion
                $sql_planificacion = "SELECT COUNT(*) AS total FROM distribucion d
                                     INNER JOIN tema t ON d.tema_id = t.tema_id
                                     INNER JOIN unidad u ON t.unidad_id = u.unidad_id
                                     WHERE u.materia_ciclo_id = ? AND d.usuario_materia_ciclo_id = ?";
                $stmt_planificacion = $pdo->prepare($sql_planificacion);
                $stmt_planificacion->execute([$materia_ciclo_id, $usuario_materia_ciclo_id]);
                $row_planificacion = $stmt_planificacion->fetch();
                $tiene_planificacion = $row_planificacion['total'] > 0;

                echo "<tr>
                        <td>{$materia}</td>
                        <td>Grupo #{$numero_asignacion}</td>
                        <td>";
                if ($tiene_planificacion) {
                    echo "<a href='ViewPlan.php?materia_ciclo_id={$materia_ciclo_id}&usuario_materia_ciclo_id={$usuario_materia_ciclo_id}'>Ver Planificación</a>";
                } else {
                    echo "<a href='PlanificarMateria.php?materia_ciclo_id={$materia_ciclo_id}&usuario_materia_ciclo_id={$usuario_materia_ciclo_id}'>Planificar</a>";
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

    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        
        tr:hover {
            background-color: #e8f4f8;
        }
        
        a {
            color: #007bff;
            text-decoration: none;
            padding: 5px 10px;
            background-color: #e7f3ff;
            border-radius: 3px;
            display: inline-block;
        }
        
        a:hover {
            background-color: #007bff;
            color: white;
        }
    </style>
</body>
</html>