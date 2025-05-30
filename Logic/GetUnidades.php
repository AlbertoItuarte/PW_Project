<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

require_once '../Config/dbConection.php';

if (!isset($_POST['materia_ciclo_id']) || empty($_POST['materia_ciclo_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de materia no proporcionado']);
    exit();
}

$materia_ciclo_id = intval($_POST['materia_ciclo_id']);

// Obtener las unidades para la materia seleccionada
$sql = "SELECT unidad_id, nombre, numero_unidad FROM unidad WHERE materia_ciclo_id = ? ORDER BY numero_unidad";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $materia_ciclo_id);
$stmt->execute();
$result = $stmt->get_result();
$unidades = $result->fetch_all(MYSQLI_ASSOC);

header('Content-Type: application/json');
echo json_encode($unidades);
?>