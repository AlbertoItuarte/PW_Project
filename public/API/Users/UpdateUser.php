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
    
    // Validar datos requeridos
    if (!isset($input['usuario_id']) || !isset($input['nombre']) || !isset($input['apellido']) || !isset($input['usuario'])) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit();
    }
    
    $usuario_id = $input['usuario_id'];
    $nombre = trim($input['nombre']);
    $apellido = trim($input['apellido']);
    $usuario = trim($input['usuario']);
    $tipo = isset($input['tipo']) ? $input['tipo'] : 'Usuario';
    $activo = isset($input['activo']) ? $input['activo'] : true;
    $nueva_password = isset($input['nueva_password']) ? $input['nueva_password'] : null;
    
    // Validaciones básicas
    if (empty($nombre) || empty($apellido) || empty($usuario)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
        exit();
    }
    
    if (!in_array($tipo, ['Usuario', 'Admin'])) {
        echo json_encode(['success' => false, 'message' => 'Tipo de usuario inválido']);
        exit();
    }
    
    // Verificar que el usuario existe
    $sql = "SELECT tipo, activo FROM usuario WHERE usuario_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuario_id]);
    $usuario_actual = $stmt->fetch();
    
    if (!$usuario_actual) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit();
    }
    
    // Protecciones especiales para el usuario actual
    if ($usuario_id == $_SESSION['user_id']) {
        // No permitir cambiar su propio tipo si es el único admin
        if ($usuario_actual['tipo'] === 'Admin' && $tipo !== 'Admin') {
            $sql = "SELECT COUNT(*) FROM usuario WHERE tipo = 'Admin' AND activo = true AND usuario_id != ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$usuario_id]);
            $otros_admins = $stmt->fetchColumn();
            
            if ($otros_admins == 0) {
                echo json_encode(['success' => false, 'message' => 'No puedes cambiar tu tipo de usuario siendo el único administrador']);
                exit();
            }
        }
        
        // No permitir desactivarse a sí mismo si es admin
        if ($usuario_actual['tipo'] === 'Admin' && !$activo) {
            echo json_encode(['success' => false, 'message' => 'No puedes desactivar tu propio usuario administrador']);
            exit();
        }
    }
    
    // Verificar que el nombre de usuario no esté en uso por otro usuario
    $sql = "SELECT COUNT(*) FROM usuario WHERE usuario = ? AND usuario_id != ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuario, $usuario_id]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'El nombre de usuario ya está en uso']);
        exit();
    }
    
    // Preparar la consulta de actualización
    if ($nueva_password) {
        // Validar contraseña
        if (strlen($nueva_password) < 6) {
            echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres']);
            exit();
        }
        
        $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
        $sql = "UPDATE usuario SET nombre = ?, apellido_paterno = ?, usuario = ?, tipo = ?, activo = ?, contrasena = ? WHERE usuario_id = ?";
        $params = [$nombre, $apellido, $usuario, $tipo, $activo, $password_hash, $usuario_id];
    } else {
        $sql = "UPDATE usuario SET nombre = ?, apellido_paterno = ?, usuario = ?, tipo = ?, activo = ? WHERE usuario_id = ?";
        $params = [$nombre, $apellido, $usuario, $tipo, $activo, $usuario_id];
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Si se actualizó el usuario actual, actualizar la sesión
    if ($usuario_id == $_SESSION['user_id']) {
        $_SESSION['user_type'] = $tipo;
    }
    
    echo json_encode(['success' => true, 'message' => 'Usuario actualizado correctamente']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
