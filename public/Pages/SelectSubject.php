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
    
    <?php
    // Mostrar mensajes de éxito o error
    if (isset($_GET['success'])) {
        echo "<div class='alert alert-success'>Materias asignadas correctamente.</div>";
    }
    if (isset($_GET['error'])) {
        echo "<div class='alert alert-error'>Error: " . htmlspecialchars($_GET['error']) . "</div>";
    }
    ?>
    
    <form action="../Logic/AsignSubject.php" method="POST">
        <div class="materias-container">
        <?php
        require_once '../Config/dbConection.php';

        // Obtener el ID del usuario desde la sesión
        $user_id = $_SESSION['user_id'];

        // Consultar todas las materias disponibles
        $sql = "SELECT mc.materia_ciclo_id, m.nombre AS materia, m.codigo,
                       (SELECT COUNT(*) FROM usuario_materia_ciclo umc 
                        WHERE umc.materia_ciclo_id = mc.materia_ciclo_id 
                        AND umc.usuario_id = ?) AS veces_asignada
                FROM materia_ciclo mc
                INNER JOIN materia m ON mc.materia_id = m.materia_id
                ORDER BY m.nombre ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);

        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch()) {
                $veces_texto = $row['veces_asignada'] > 0 ? "Asignada {$row['veces_asignada']} vez(es)" : "Disponible";
                $estado_class = $row['veces_asignada'] > 0 ? 'asignada' : 'disponible';
                
                echo "<div class='materia-item {$estado_class}'>
                        <label>
                            <span class='materia-nombre'>{$row['materia']}</span>
                            <span class='materia-codigo'>({$row['codigo']})</span>
                            <span class='materia-estado'>{$veces_texto}</span>
                        </label>
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

    <!-- Mostrar todas las asignaciones actuales -->
    <div class="asignaciones-actuales">
        <h2>Mis Asignaciones Actuales</h2>
        <?php
        $sql_asignaciones = "SELECT m.nombre AS materia, m.codigo, umc.fecha_asignacion,
                            ROW_NUMBER() OVER (PARTITION BY m.materia_id ORDER BY umc.fecha_asignacion) as numero_asignacion
                            FROM usuario_materia_ciclo umc
                            INNER JOIN materia_ciclo mc ON umc.materia_ciclo_id = mc.materia_ciclo_id
                            INNER JOIN materia m ON mc.materia_id = m.materia_id
                            WHERE umc.usuario_id = ?
                            ORDER BY m.nombre ASC, umc.fecha_asignacion ASC";
        $stmt_asignaciones = $pdo->prepare($sql_asignaciones);
        $stmt_asignaciones->execute([$user_id]);

        if ($stmt_asignaciones->rowCount() > 0) {
            echo "<table class='asignaciones-table'>
                    <thead>
                        <tr>
                            <th>Materia</th>
                            <th>Código</th>
                            <th>Asignación #</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>";
            while ($asignacion = $stmt_asignaciones->fetch()) {
                echo "<tr>
                        <td>{$asignacion['materia']}</td>
                        <td>{$asignacion['codigo']}</td>
                        <td>#{$asignacion['numero_asignacion']}</td>
                        <td>" . date('d/m/Y H:i', strtotime($asignacion['fecha_asignacion'])) . "</td>
                      </tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<p>No tienes materias asignadas actualmente.</p>";
        }
        ?>
    </div>

    <style>
        .alert {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .materia-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .materia-item.asignada {
            background-color: #f8f9fa;
            border-left: 4px solid #ffc107;
        }
        
        .materia-item.disponible {
            background-color: #fff;
            border-left: 4px solid #007bff;
        }
        
        .materia-nombre {
            font-weight: bold;
        }
        
        .materia-codigo {
            color: #666;
            font-size: 0.9em;
        }
        
        .materia-estado {
            font-size: 0.8em;
            padding: 2px 6px;
            border-radius: 3px;
            margin-left: 10px;
        }
        
        .asignada .materia-estado {
            background-color: #ffc107;
            color: #212529;
        }
        
        .disponible .materia-estado {
            background-color: #007bff;
            color: white;
        }
        
        .asignaciones-actuales {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        
        .asignaciones-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .asignaciones-table th,
        .asignaciones-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .asignaciones-table th {
            background-color: #e9ecef;
            font-weight: bold;
        }
    </style>
</body>
</html>