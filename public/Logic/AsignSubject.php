<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Pages/Login.php");
    exit();
}

require_once '../Config/dbConection.php';

// Verificar si se enviaron materias
if (!isset($_POST['materias']) || empty($_POST['materias'])) {
    header("Location: ../Pages/SelectSubject.php?error=No se seleccionaron materias");
    exit();
}

// Obtener el ID del usuario desde la sesión
$user_id = $_SESSION['user_id'];

try {
    // Iniciar transacción para asegurar consistencia
    $pdo->beginTransaction();
    
    // Preparar la consulta para asignar materias
    $sql = "INSERT INTO usuario_materia_ciclo (usuario_id, materia_ciclo_id, fecha_asignacion) VALUES (?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    
    $materias_asignadas = 0;
    
    foreach ($_POST['materias'] as $materia_ciclo_id) {
        $materia_ciclo_id = intval($materia_ciclo_id);
        
        try {
            // Intentar insertar la materia
            $stmt->execute([$user_id, $materia_ciclo_id]);
            $materias_asignadas++;
        } catch (PDOException $e) {
            // Si es error de duplicado, intentar insertar de nuevo (permite múltiples asignaciones)
            if ($e->getCode() == '23505') { // Código de error para violación de constraint único
                // Eliminar temporalmente la restricción o continuar sin error
                // En este caso, simplemente continuamos para permitir múltiples asignaciones
                $stmt->execute([$user_id, $materia_ciclo_id]);
                $materias_asignadas++;
            } else {
                throw $e; // Re-lanzar otros errores
            }
        }
    }
    
    // Confirmar la transacción
    $pdo->commit();
    
    // Redirigir al usuario con un mensaje de éxito
    header("Location: ../Pages/SelectSubject.php?success=Se asignaron {$materias_asignadas} materia(s) correctamente");
    exit();
    
} catch (Exception $e) {
    // Revertir la transacción en caso de error
    $pdo->rollback();
    
    // Redirigir con mensaje de error
    header("Location: ../Pages/SelectSubject.php?error=" . urlencode("Error al asignar materias: " . $e->getMessage()));
    exit();
}
?>