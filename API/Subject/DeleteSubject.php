<?php
session_start();
require_once '../../Config/dbConection.php';

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../Pages/Login.php");
    exit();
}

// Verificar si se proporcionó un ID de materia
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../../Pages/Home.php?error=invalid_id");
    exit();
}

$materia_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Verificar que la materia pertenezca al usuario actual
$sql = "SELECT mc.materia_ciclo_id 
        FROM materia_ciclo mc
        WHERE mc.materia_id = ? AND mc.usuario_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$materia_id, $user_id]);
$result = $stmt->fetch();

if (!$result) {
    // La materia no existe o no pertenece al usuario
    header("Location: ../../Pages/Home.php?error=materia_no_encontrada");
    exit();
}

// Obtener el ID de la relación materia_ciclo
$materia_ciclo_id = $result['materia_ciclo_id'];

// Iniciar transacción para garantizar la integridad de los datos
$pdo->beginTransaction();

try {
    // Eliminar temas asociados a las unidades de la materia
    $sql = "DELETE FROM tema 
            WHERE tema_id IN (
                SELECT t.tema_id 
                FROM tema t
                INNER JOIN unidad u ON t.unidad_id = u.unidad_id
                WHERE u.materia_ciclo_id = ?
            )";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$materia_ciclo_id]);

    // Eliminar unidades asociadas a la materia
    $sql = "DELETE FROM unidad WHERE materia_ciclo_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$materia_ciclo_id]);

    // Eliminar la relación materia_ciclo
    $sql = "DELETE FROM materia_ciclo WHERE materia_ciclo_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$materia_ciclo_id]);

    // Eliminar la materia (si no está asociada a otros ciclos)
    $sql = "DELETE FROM materia WHERE materia_id = ? AND NOT EXISTS (
                SELECT 1 FROM materia_ciclo WHERE materia_id = ?
            )";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$materia_id, $materia_id]);

    // Confirmar la transacción
    $pdo->commit();

    // Redirigir con mensaje de éxito
    header("Location: ../../Pages/Home.php?success=materia_eliminada");
    exit();
} catch (Exception $e) {
    // Revertir en caso de error
    $pdo->rollback();
    header("Location: ../../Pages/Home.php?error=" . urlencode($e->getMessage()));
    exit();
}
?>