<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != "Admin") {
    http_response_code(403);
    exit();
}

require_once '../../Config/dbConection.php';

$sql = "SELECT 
            mc.materia_ciclo_id,
            m.materia_id,
            m.nombre AS materia,
            mc.horas_teoricas,
            mc.horas_practicas,
            mc.fecha_asignacion,
            GROUP_CONCAT(u.nombre SEPARATOR ', ') AS usuarios_asignados,
            COUNT(umc.usuario_id) AS total_usuarios
        FROM materia_ciclo mc
        INNER JOIN materia m ON mc.materia_id = m.materia_id
        LEFT JOIN usuario_materia_ciclo umc ON mc.materia_ciclo_id = umc.materia_ciclo_id
        LEFT JOIN usuario u ON umc.usuario_id = u.usuario_id
        WHERE mc.usuario_id = ?
        GROUP BY mc.materia_ciclo_id, m.materia_id, m.nombre, mc.horas_teoricas, mc.horas_practicas, mc.fecha_asignacion
        ORDER BY mc.fecha_asignacion DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$materias = [];
while ($row = $result->fetch_assoc()) {
    $materias[] = [
        'materia_ciclo_id' => $row['materia_ciclo_id'],
        'materia_id' => $row['materia_id'],
        'materia' => $row['materia'],
        'horas_teoricas' => $row['horas_teoricas'],
        'horas_practicas' => $row['horas_practicas'],
        'fecha_asignacion' => $row['fecha_asignacion'],
        'usuarios_asignados' => $row['usuarios_asignados'] ?? 'Sin asignar',
        'total_usuarios' => $row['total_usuarios']
    ];
}

header('Content-Type: application/json');
echo json_encode($materias);
?>