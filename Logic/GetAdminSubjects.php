<?php
require_once '../Config/dbConection.php';
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Código de error no autorizado
    echo json_encode(["error" => "No autorizado"]);
    exit();
}

// Obtener el ID del usuario actual
$user_id = $_SESSION['user_id'];

try {
    // Consulta para obtener las materias creadas por el administrador
    $sql = "SELECT p.materia_id, p.nombre AS materia, mc.horas_teoricas, mc.horas_practicas, mc.fecha_asignacion
            FROM materia p
            INNER JOIN materia_ciclo mc ON p.materia_id = mc.materia_id
            WHERE mc.usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Convertir los resultados en un array
    $materias = [];
    while ($row = $result->fetch_assoc()) {
        $materias[] = $row;
    }

    // Devolver los resultados como JSON
    header('Content-Type: application/json');
    echo json_encode($materias);
} catch (Exception $e) {
    // Manejar errores y devolver un mensaje JSON
    http_response_code(500); // Código de error interno del servidor
    echo json_encode(["error" => "Error al obtener las materias", "details" => $e->getMessage()]);
}