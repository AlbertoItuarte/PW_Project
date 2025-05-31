<?php
session_start();

// Establecer el tipo de contenido como JSON
header('Content-Type: application/json');

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuario no autenticado']);
    exit();
}

// Verificar si el usuario es administrador
if ($_SESSION['user_type'] != "Admin") {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit();
}

// Conectar a la base de datos
require_once '../../Config/dbConection.php';

try {
    // Consulta para obtener las materias del administrador con información de usuarios asignados
    $sql = "
        SELECT 
            m.materia_id,
            m.nombre AS materia,
            mc.horas_teoricas,
            mc.horas_practicas,
            mc.fecha_asignacion,
            CASE 
                WHEN COUNT(umc.usuario_id) = 0 THEN 'Sin asignar'
                ELSE STRING_AGG(u.nombre, ', ')
            END as usuarios_asignados,
            COUNT(umc.usuario_id) as total_usuarios
        FROM materia_ciclo mc
        INNER JOIN materia m ON mc.materia_id = m.materia_id
        LEFT JOIN usuario_materia_ciclo umc ON mc.materia_ciclo_id = umc.materia_ciclo_id
        LEFT JOIN usuario u ON umc.usuario_id = u.usuario_id
        WHERE mc.usuario_id = ?
        GROUP BY m.materia_id, m.nombre, mc.horas_teoricas, mc.horas_practicas, mc.fecha_asignacion
        ORDER BY mc.fecha_asignacion DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetchAll();
    
    $materias = [];
    
    foreach ($result as $row) {
        $materias[] = [
            'materia_id' => $row['materia_id'],
            'materia' => $row['materia'],
            'horas_teoricas' => $row['horas_teoricas'],
            'horas_practicas' => $row['horas_practicas'],
            'usuarios_asignados' => $row['usuarios_asignados'],
            'total_usuarios' => $row['total_usuarios'],
            'fecha_asignacion' => date('d/m/Y', strtotime($row['fecha_asignacion']))
        ];
    }
    
    // Devolver los datos en formato JSON
    echo json_encode($materias);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
}