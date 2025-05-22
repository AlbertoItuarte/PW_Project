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
                <li><a href="./PlanSubject.php">Seleccionar materia</a></li>
                <li><a href="../Logic/LogOut.php">Cerrar sesión</a></li>
            </ul>
        </nav>
    </div>
    <div>
        <h1>Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
        <p>Has iniciado sesión correctamente.</p>
    </div>

   <div>
    <h2>Tus materias</h2>
    <?php
    // Consulta de materias
        require_once '../Config/dbConection.php';
        
        $user_id = $_SESSION['user_id'];
        
        // Consulta para obtener las materias del usuario actual
        $sql = "SELECT p.id, p.materia, p.horas_teoricas, p.horas_practicas, pu.fecha_evaluacion 
                FROM programa p 
                INNER JOIN plan_usuario pu ON p.id = pu.programa_id 
                WHERE pu.usuario_id = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo '<table>
                    <thead>
                        <tr>
                            <th>Materia</th>
                            <th>Horas Teóricas</th>
                            <th>Horas Prácticas</th>
                            <th>Fecha de Creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            while ($row = $result->fetch_assoc()) {
                echo '<tr>
                        <td>' . htmlspecialchars($row['materia']) . '</td>
                        <td>' . $row['horas_teoricas'] . '</td>
                        <td>' . $row['horas_practicas'] . '</td>
                        <td>' . $row['fecha_evaluacion'] . '</td>
                        <td class="actions">
                            <a href="ViewSubject.php?id=' . $row['id'] . '">Ver</a>
                            <a href="DeleteSubject.php?id=' . $row['id'] . '">Eliminar</a>
                            <a href="EditSubject.php?id=' . $row['id'] . '">Editar</a>
                        </td>
                      </tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<div class="no-materias">
                    <p>No tienes materias registradas aún.</p>
                    <p>Haz clic en "Crear nueva materia" para empezar a organizar tu plan de estudios.</p>
                  </div>';
        }
    ?>
   </div>
        
</body>
</html>