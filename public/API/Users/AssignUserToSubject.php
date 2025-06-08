<?php
session_start();
header('Content-Type: application/json');

// Verificar autenticación y permisos
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != "Admin") {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

require_once '../../Config/dbConection.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['usuario_id']) || !isset($input['materia_id'])) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit();
    }
    
    $usuario_id = $input['usuario_id'];
    $materia_id = $input['materia_id'];
    
    // Verificar que el usuario existe y es de tipo Usuario
    $sql = "SELECT COUNT(*) FROM usuario WHERE usuario_id = ? AND tipo = 'Usuario' AND activo = true";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuario_id]);
    if ($stmt->fetchColumn() == 0) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit();
    }
    
    // Obtener el materia_ciclo_id del ciclo activo para esta materia
    $sql = "SELECT mc.materia_ciclo_id 
            FROM materia_ciclo mc 
            INNER JOIN ciclo c ON mc.ciclo_id = c.ciclo_id 
            WHERE mc.materia_id = ? AND c.activo = true";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$materia_id]);
    $materia_ciclo = $stmt->fetch();
    
    if (!$materia_ciclo) {
        echo json_encode(['success' => false, 'message' => 'No hay un ciclo activo para esta materia']);
        exit();
    }
    
    $materia_ciclo_id = $materia_ciclo['materia_ciclo_id'];
    
    // Verificar que el usuario no está ya asignado
    // $sql = "SELECT COUNT(*) FROM usuario_materia_ciclo WHERE usuario_id = ? AND materia_ciclo_id = ?";
    // $stmt = $pdo->prepare($sql);
    // $stmt->execute([$usuario_id, $materia_ciclo_id]);
    // if ($stmt->fetchColumn() > 0) {
    //     echo json_encode(['success' => false, 'message' => 'El usuario ya está asignado a esta materia']);
    //     exit();
    // }
    
    // Asignar usuario a la materia
    $sql = "INSERT INTO usuario_materia_ciclo (usuario_id, materia_ciclo_id, fecha_asignacion) VALUES (?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuario_id, $materia_ciclo_id]);
    
    echo json_encode(['success' => true, 'message' => 'Usuario asignado correctamente']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
