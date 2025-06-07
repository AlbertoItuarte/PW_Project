<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

require_once '../Config/dbConection.php';

// Verificar si se recibieron los parámetros necesarios
if (!isset($_GET['materia_ciclo_id']) || !isset($_GET['usuario_materia_ciclo_id'])) {
    die("Error: No se especificaron los parámetros necesarios.");
}

$materia_ciclo_id = intval($_GET['materia_ciclo_id']);
$usuario_materia_ciclo_id = intval($_GET['usuario_materia_ciclo_id']);

// Función para formatear fecha de YYYY-MM-DD a DD/MM/YYYY
function formatDate($date) {
    if (empty($date) || $date === '0000-00-00') {
        return "Sin fecha";
    }
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    return $dateObj ? $dateObj->format('d/m/Y') : $date;
}

// Consulta para mostrar SOLO la planificación de esta asignación específica
$sql_planificacion = "SELECT DISTINCT m.nombre AS materia, u.nombre AS unidad, u.numero_unidad,
                             t.nombre AS tema, t.orden_tema, d.fecha_inicio, d.fecha_fin, t.horas_estimadas,
                             ge.fecha_evaluacion, u.unidad_id, t.tema_id
                      FROM unidad u
                      INNER JOIN materia_ciclo mc ON u.materia_ciclo_id = mc.materia_ciclo_id
                      INNER JOIN materia m ON mc.materia_id = m.materia_id
                      INNER JOIN tema t ON u.unidad_id = t.unidad_id
                      INNER JOIN distribucion d ON t.tema_id = d.tema_id AND d.usuario_materia_ciclo_id = ?
                      LEFT JOIN unidad_evaluacion ue ON u.unidad_id = ue.unidad_id
                      LEFT JOIN grupo_evaluacion ge ON ue.grupo_eval_id = ge.grupo_eval_id
                      WHERE u.materia_ciclo_id = ?
                      ORDER BY u.numero_unidad, t.orden_tema";

$stmt = $pdo->prepare($sql_planificacion);
$stmt->execute([$usuario_materia_ciclo_id, $materia_ciclo_id]);
$result = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../CSS/Global.css">
    <link rel="stylesheet" href="../CSS/ViewPlan.css">
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
    <?php if (count($result) > 0): ?>
        <?php
        $current_unidad = null;
        $current_materia = null;
        
        foreach ($result as $row):
            $materia = htmlspecialchars($row['materia']);
            $unidad = htmlspecialchars($row['unidad']);
            $fecha_evaluacion = formatDate($row['fecha_evaluacion']);
            $tema = htmlspecialchars($row['tema']);
            $fecha_inicio = formatDate($row['fecha_inicio']);
            $fecha_fin = formatDate($row['fecha_fin']);
            $horas_estimadas = $row['horas_estimadas'];

            // Mostrar la materia si cambia
            if ($materia !== $current_materia) {
                if ($current_materia !== null) {
                    echo "</tbody></table><br>";
                }
                echo "<h2>MATERIA: {$materia}</h2>";
                $current_materia = $materia;
                $current_unidad = null; // Reset unidad cuando cambia materia
            }

            // Mostrar la unidad si cambia
            if ($unidad !== $current_unidad) {
                if ($current_unidad !== null) {
                    echo "</tbody></table><br>";
                }
                echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
                echo "<thead>";
                echo "<tr style='background-color: #f2f2f2;'>";
                echo "<th colspan='4' style='padding: 10px; text-align: center; font-size: 18px;'>UNIDAD: {$unidad} - FECHA DE EVALUACIÓN: {$fecha_evaluacion}</th>";
                echo "</tr>";
                echo "<tr style='background-color: #e6e6e6;'>";
                echo "<th style='padding: 8px;'>Tema</th>";
                echo "<th style='padding: 8px;'>Fecha de Inicio</th>";
                echo "<th style='padding: 8px;'>Fecha de Fin</th>";
                echo "<th style='padding: 8px;'>Horas Estimadas</th>";
                echo "</tr>";
                echo "</thead>";
                echo "<tbody>";
                $current_unidad = $unidad;
            }

            // Mostrar el tema
            echo "<tr>";
            echo "<td style='padding: 8px;'>{$tema}</td>";
            echo "<td style='padding: 8px;'>{$fecha_inicio}</td>";
            echo "<td style='padding: 8px;'>{$fecha_fin}</td>";
            echo "<td style='padding: 8px;'>{$horas_estimadas} hrs</td>";
            echo "</tr>";
        endforeach;
        
        // Cerrar la última tabla
        if ($current_unidad !== null) {
            echo "</tbody></table>";
        }
        ?>
    <?php else: ?>
        <p>No hay planificación registrada para esta asignación específica.</p>
        <p><a href="PlanificarMateria.php?materia_ciclo_id=<?php echo $materia_ciclo_id; ?>&usuario_materia_ciclo_id=<?php echo $usuario_materia_ciclo_id; ?>">Crear planificación</a></p>
    <?php endif; ?>

</body>
</html>