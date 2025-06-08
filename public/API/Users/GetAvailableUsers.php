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
    
    // Obtener TODOS los usuarios disponibles (sin filtrar por asignaciones)
    $sql = "SELECT u.usuario_id as id, u.nombre, u.apellido_paterno as apellido, u.usuario as email 
            FROM usuario u 
            WHERE u.tipo = 'Usuario'
            AND u.activo = true
            ORDER BY u.nombre, u.apellido_paterno";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($usuarios);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}
