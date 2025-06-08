<?php
// Habilitar reporte de errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../Config/dbConection.php';
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(["error" => "No autorizado"]);
    exit();
}

// Verificar si el usuario es administrador
if ($_SESSION['user_type'] !== 'Admin') {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(["error" => "Acceso denegado"]);
    exit();
}

try {
    // Verificar que la conexión PDO existe
    if (!isset($pdo)) {
        throw new Exception("Conexión a la base de datos no disponible");
    }
    
    // Consulta para obtener todos los usuarios ordenados por nombre, apellido y tipo
    $sql = "SELECT usuario_id, nombre, apellido_paterno, tipo 
            FROM usuario
            ORDER BY nombre ASC, apellido_paterno ASC, tipo ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Devolver los datos como JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $usuarios,
        'count' => count($usuarios)
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Error de conexión a la base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>
