<?php
require_once '../Config/dbConection.php';
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit();
}

// Verificar si se proporcionó un ID de materia
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "ID de materia inválido"]);
    exit();
}

$materia_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Obtener información básica de la materia
$sql = "SELECT m.nombre AS materia, mc.horas_teoricas, mc.horas_practicas, mc.fecha_asignacion
        FROM materia m
        INNER JOIN materia_ciclo mc ON m.materia_id = mc.materia_id
        WHERE mc.materia_id = ? AND mc.usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $materia_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    http_response_code(404);
    echo json_encode(["error" => "Materia no encontrada"]);
    exit();
}

$materia = $result->fetch_assoc();

// Obtener todas las unidades y temas de la materia
$sql = "SELECT u.unidad_id, u.nombre AS unidad, u.numero_unidad, u.descripcion
        FROM unidad u
        WHERE u.materia_ciclo_id = (SELECT mc.materia_ciclo_id FROM materia_ciclo mc WHERE mc.materia_id = ? AND mc.usuario_id = ?)
        ORDER BY u.numero_unidad";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $materia_id, $user_id);
$stmt->execute();
$unidades_result = $stmt->get_result();

$unidades = [];
while ($unidad = $unidades_result->fetch_assoc()) {
    // Obtener temas de la unidad
    $sql = "SELECT t.tema_id, t.nombre AS tema, t.orden_tema, t.horas_estimadas
            FROM tema t
            WHERE t.unidad_id = ?
            ORDER BY t.orden_tema";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $unidad['unidad_id']);
    $stmt->execute();
    $temas_result = $stmt->get_result();

    $temas = [];
    while ($tema = $temas_result->fetch_assoc()) {
        $temas[] = $tema;
    }

    $unidad['temas'] = $temas;
    $unidades[] = $unidad;
}

// Calcular totales
$total_horas_teoricas = $materia['horas_teoricas'];
$total_horas_practicas = $materia['horas_practicas'];
$total_horas = $total_horas_teoricas + $total_horas_practicas;

$total_horas_temas = 0;
foreach ($unidades as $unidad) {
    foreach ($unidad['temas'] as $tema) {
        $total_horas_temas += $tema['horas_estimadas'];
    }
}

// Devolver los datos como JSON
header('Content-Type: application/json');
echo json_encode([
    "materia" => $materia,
    "unidades" => $unidades,
    "totales" => [
        "horas_teoricas" => $total_horas_teoricas,
        "horas_practicas" => $total_horas_practicas,
        "horas_totales" => $total_horas,
        "horas_temas" => $total_horas_temas
    ]
]);