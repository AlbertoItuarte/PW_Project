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
    
    if (!isset($input['usuario_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de usuario requerido']);
        exit();
    }
    
    $usuario_id = $input['usuario_id'];
    
    // Verificar que el usuario existe
    $sql = "SELECT tipo FROM usuario WHERE usuario_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit();
    }
    
    // No permitir eliminar al propio usuario admin
    if ($usuario_id == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'No puedes eliminar tu propio usuario']);
        exit();
    }
    
    // Verificar si es administrador
    if ($usuario['tipo'] === 'Admin') {
        // Contar cuántos administradores hay en total
        $sql = "SELECT COUNT(*) FROM usuario WHERE tipo = 'Admin' AND activo = true";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $totalAdmins = $stmt->fetchColumn();
        
        // Si solo hay un administrador, no permitir eliminarlo
        if ($totalAdmins <= 1) {
            echo json_encode(['success' => false, 'message' => 'No se puede eliminar el último administrador del sistema']);
            exit();
        }
        
        // Si hay más de un admin pero es el actual, no permitir
        if ($usuario_id == $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'No puedes eliminar tu propio usuario administrador']);
            exit();
        }
        
        // Advertencia adicional para eliminar admin
        echo json_encode(['success' => false, 'message' => 'No se permite eliminar usuarios administradores por seguridad del sistema']);
        exit();
    }
    
    // Solo permitir eliminar usuarios normales
    if ($usuario['tipo'] !== 'Usuario') {
        echo json_encode(['success' => false, 'message' => 'Solo se pueden eliminar usuarios de tipo "Usuario"']);
        exit();
    }
    
    // Iniciar transacción para manejar las dependencias
    $pdo->beginTransaction();
    
    try {
        // 1. Eliminar distribuciones asociadas
        $sql = "DELETE FROM distribucion 
                WHERE usuario_materia_ciclo_id IN (
                    SELECT usuario_materia_ciclo_id 
                    FROM usuario_materia_ciclo 
                    WHERE usuario_id = ?
                )";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuario_id]);
        
        // 2. Eliminar asignaciones de usuario_materia_ciclo
        $sql = "DELETE FROM usuario_materia_ciclo WHERE usuario_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuario_id]);
        
        // 3. Eliminar el usuario (solo usuarios normales llegan hasta aquí)
        $sql = "DELETE FROM usuario WHERE usuario_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuario_id]);
        
        // Confirmar transacción
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'Usuario eliminado correctamente']);
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $pdo->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
