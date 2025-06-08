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
    
    // Obtener el usuario_materia_ciclo_id específico
    $sql = "SELECT usuario_materia_ciclo_id FROM usuario_materia_ciclo WHERE usuario_id = ? AND materia_ciclo_id = ? LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuario_id, $materia_ciclo_id]);
    $usuario_materia_ciclo = $stmt->fetch();
    
    if (!$usuario_materia_ciclo) {
        echo json_encode(['success' => false, 'message' => 'La asignación no existe']);
        exit();
    }
    
    $usuario_materia_ciclo_id = $usuario_materia_ciclo['usuario_materia_ciclo_id'];
    
    // Iniciar transacción
    $pdo->beginTransaction();
    
    try {
        // 1. Eliminar todas las distribuciones asociadas
        $sql = "DELETE FROM distribucion WHERE usuario_materia_ciclo_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuario_materia_ciclo_id]);
        
        // 2. Eliminar la asignación usuario_materia_ciclo
        $sql = "DELETE FROM usuario_materia_ciclo WHERE usuario_materia_ciclo_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuario_materia_ciclo_id]);
        
        // Confirmar transacción
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'Usuario desasignado correctamente. Se eliminaron las distribuciones asociadas.']);
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $pdo->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
