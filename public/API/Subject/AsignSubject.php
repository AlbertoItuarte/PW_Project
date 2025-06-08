<?php
session_start();
header('Content-Type: application/json');

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

require_once '../../Config/dbConection.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['usuario_id']) || !isset($input['materias']) || !is_array($input['materias'])) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos o incorrectos']);
        exit();
    }
    
    $usuario_id = $input['usuario_id'];
    $materias = $input['materias'];
    
    if (empty($materias)) {
        echo json_encode(['success' => false, 'message' => 'No se seleccionaron materias']);
        exit();
    }
    
    $pdo->beginTransaction();
    
    $asignaciones_exitosas = 0;
    $asignaciones_fallidas = 0;
    
    foreach ($materias as $materia_ciclo_id) {
        // Verificar que la asignación no exista ya
        $sql = "SELECT COUNT(*) FROM usuario_materia_ciclo WHERE usuario_id = ? AND materia_ciclo_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuario_id, $materia_ciclo_id]);
        
        if ($stmt->fetchColumn() == 0) {
            // Asignar la materia al usuario
            $sql = "INSERT INTO usuario_materia_ciclo (usuario_id, materia_ciclo_id) VALUES (?, ?)";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute([$usuario_id, $materia_ciclo_id])) {
                $asignaciones_exitosas++;
            } else {
                $asignaciones_fallidas++;
            }
        } else {
            // Ya está asignada
            $asignaciones_fallidas++;
        }
    }
    
    $pdo->commit();
    
    if ($asignaciones_exitosas > 0) {
        $mensaje = "Se asignaron {$asignaciones_exitosas} materia(s) correctamente.";
        if ($asignaciones_fallidas > 0) {
            $mensaje .= " {$asignaciones_fallidas} materia(s) ya estaban asignadas.";
        }
        echo json_encode(['success' => true, 'message' => $mensaje]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo asignar ninguna materia. Posiblemente ya estaban asignadas.']);
    }
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>
