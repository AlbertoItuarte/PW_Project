<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Pages/Login.php");
    exit();
}

require_once '../Config/dbConection.php';

// Obtener datos del formulario
$materia_ciclo_id = intval($_POST['materia_ciclo_id']);
$dias = $_POST['dias'];
$horas = $_POST['horas'];

// Obtener los temas de la materia
$sql = "SELECT t.tema_id, t.nombre AS tema, t.horas_estimadas, u.nombre AS unidad
        FROM tema t
        INNER JOIN unidad u ON t.unidad_id = u.unidad_id
        WHERE u.materia_ciclo_id = ?
        ORDER BY u.numero_unidad, t.orden_tema";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $materia_ciclo_id);
$stmt->execute();
$result = $stmt->get_result();
$temas = $result->fetch_all(MYSQLI_ASSOC);

// Calcular la distribución
$plan = [];
foreach ($temas as $tema) {
    $horas_restantes = $tema['horas_estimadas'];
    foreach ($dias as $dia) {
        if ($horas_restantes <= 0) break;
        $horas_disponibles = $horas[$dia];
        $horas_asignadas = min($horas_restantes, $horas_disponibles);
        $plan[] = [
            'unidad' => $tema['unidad'],
            'tema' => $tema['tema'],
            'dia' => $dia,
            'horas_asignadas' => $horas_asignadas
        ];
        $horas_restantes -= $horas_asignadas;
    }
}

// Mostrar el plan
header('Content-Type: application/json');
echo json_encode($plan);