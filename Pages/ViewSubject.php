<?php
session_start();
require_once '../Config/dbConection.php';

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

// Verificar si se proporcionó un ID de materia
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: Home.php");
    exit();
}

$materia_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Obtener información básica de la materia
$sql = "SELECT p.*, pu.fecha_evaluacion, pu.id as plan_id 
        FROM programa p 
        INNER JOIN plan_usuario pu ON p.id = pu.programa_id 
        WHERE p.id = ? AND pu.usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $materia_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Verificar si la materia existe y pertenece al usuario
if ($result->num_rows == 0) {
    header("Location: Home.php?error=materia_no_encontrada");
    exit();
}

$materia = $result->fetch_assoc();
$plan_id = $materia['plan_id'];

// Obtener todas las unidades de la materia
$sql = "SELECT * FROM unidad WHERE programa_id = ? ORDER BY id";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $materia_id);
$stmt->execute();
$unidades_result = $stmt->get_result();

// Preparar un array para almacenar todas las unidades con sus temas
$unidades = [];
while ($unidad = $unidades_result->fetch_assoc()) {
    // Consultar temas de esta unidad
    $sql = "SELECT t.*, tu.horas_estimadas 
            FROM tema t 
            LEFT JOIN tema_usuario tu ON t.id = tu.tema_id AND tu.plan_id = ? 
            WHERE t.unidad_id = ? 
            ORDER BY t.id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $plan_id, $unidad['id']);
    $stmt->execute();
    $temas_result = $stmt->get_result();
    
    // Agregar temas a la unidad
    $temas = [];
    while ($tema = $temas_result->fetch_assoc()) {
        $temas[] = $tema;
    }
    
    $unidad['temas'] = $temas;
    $unidades[] = $unidad;
}

// Calcular total de horas
$total_horas_teoricas = $materia['horas_teoricas'];
$total_horas_practicas = $materia['horas_practicas'];
$total_horas = $total_horas_teoricas + $total_horas_practicas;

// Calcular total de horas estimadas para temas
$total_horas_temas = 0;
foreach ($unidades as $unidad) {
    foreach ($unidad['temas'] as $tema) {
        $total_horas_temas += $tema['horas_estimadas'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($materia['materia']); ?> - Detalles</title>
    <link rel="stylesheet" href="../CSS/Global.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .info-section {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .unidad {
            background-color: #f1f1f1;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
            border-left: 4px solid #2196F3;
        }
        .unidad h3 {
            margin-top: 0;
            color: #2196F3;
        }
        .tema {
            background-color: white;
            padding: 10px 15px;
            margin: 10px 0;
            border-radius: 4px;
            border-left: 3px solid #FF9800;
        }
        .actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
        }
        .btn-secondary {
            background-color: #607D8B;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo htmlspecialchars($materia['materia']); ?></h1>
        </div>
        
        <div class="info-section">
            <h2>Información General</h2>
            <table>
                <tr>
                    <th>Horas teóricas</th>
                    <td><?php echo $total_horas_teoricas; ?> horas</td>
                </tr>
                <tr>
                    <th>Horas prácticas</th>
                    <td><?php echo $total_horas_practicas; ?> horas</td>
                </tr>
                <tr>
                    <th>Total de horas del curso</th>
                    <td><?php echo $total_horas; ?> horas</td>
                </tr>
                <tr>
                    <th>Fecha de creación</th>
                    <td><?php echo $materia['fecha_evaluacion']; ?></td>
                </tr>
                <tr>
                    <th>Total de horas asignadas a temas</th>
                    <td><?php echo $total_horas_temas; ?> horas</td>
                </tr>
            </table>
        </div>
        
        <h2>Programa de Estudio</h2>
        
        <?php if (count($unidades) > 0): ?>
            <?php foreach ($unidades as $index => $unidad): ?>
                <div class="unidad">
                    <h3>Unidad <?php echo $index + 1; ?>: <?php echo htmlspecialchars($unidad['nombre']); ?></h3>
                    
                    <?php if (count($unidad['temas']) > 0): ?>
                        <?php 
                        $horas_unidad = 0;
                        foreach ($unidad['temas'] as $tema) {
                            $horas_unidad += $tema['horas_estimadas'];
                        }
                        ?>
                        <p><strong>Total de horas de la unidad:</strong> <?php echo $horas_unidad; ?> horas</p>
                        
                        <?php foreach ($unidad['temas'] as $temaIndex => $tema): ?>
                            <div class="tema">
                                <h4><?php echo $temaIndex + 1; ?>. <?php echo htmlspecialchars($tema['nombre']); ?></h4>
                                <p><strong>Horas estimadas:</strong> <?php echo $tema['horas_estimadas']; ?> horas</p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Esta unidad no tiene temas registrados.</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Esta materia no tiene unidades registradas.</p>
        <?php endif; ?>
        
        <div class="actions">
            <a href="EditSubject.php?id=<?php echo $materia_id; ?>" class="btn">Editar materia</a>
            <a href="Home.php" class="btn btn-secondary">Volver al inicio</a>
        </div>
    </div>
</body>
</html>