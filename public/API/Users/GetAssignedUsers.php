<?php
session_start();
header('Content-Type: application/json');

// Verificar autenticación y permisos
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != "Admin") {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

if (!isset($_GET['materia_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de materia requerido']);
    exit();
}

require_once '../../Config/dbConection.php';

try {
    $materia_id = $_GET['materia_id'];
    
    // Obtener usuarios asignados a la materia en el ciclo activo
    $sql = "SELECT u.usuario_id as id, u.nombre, u.apellido_paterno as apellido, 
                   u.usuario as email, umc.fecha_asignacion, c.nombre as ciclo_nombre
            FROM usuario u
            INNER JOIN usuario_materia_ciclo umc ON u.usuario_id = umc.usuario_id
            INNER JOIN materia_ciclo mc ON umc.materia_ciclo_id = mc.materia_ciclo_id
            INNER JOIN ciclo c ON mc.ciclo_id = c.ciclo_id
            WHERE mc.materia_id = ? AND c.activo = true
            ORDER BY u.nombre, u.apellido_paterno";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$materia_id]);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($usuarios);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}
