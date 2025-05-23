<?php
session_start();
require_once '../Config/dbConection.php';

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Pages/Login.php");
    exit();
}

// Verificar si se proporcionó un ID de materia
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../Pages/Home.php?error=invalid_id");
    exit();
}

$materia_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Verificar que la materia pertenezca al usuario actual
$sql = "SELECT p.id 
        FROM programa p 
        INNER JOIN plan_usuario pu ON p.id = pu.programa_id 
        WHERE p.id = ? AND pu.usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $materia_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // La materia no existe o no pertenece al usuario
    header("Location: ../Pages/Home.php?error=materia_no_encontrada");
    exit();
}

// Iniciar transacción para garantizar la integridad de los datos
$conn->begin_transaction();

try {
    // Eliminar relaciones de temas con el plan de usuario
    $sql = "DELETE tu 
            FROM tema_usuario tu
            INNER JOIN tema t ON tu.tema_id = t.id
            INNER JOIN unidad u ON t.unidad_id = u.id
            WHERE u.programa_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $materia_id);
    $stmt->execute();

    // Eliminar temas
    $sql = "DELETE t 
            FROM tema t
            INNER JOIN unidad u ON t.unidad_id = u.id
            WHERE u.programa_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $materia_id);
    $stmt->execute();

    // Eliminar unidades
    $sql = "DELETE FROM unidad WHERE programa_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $materia_id);
    $stmt->execute();

    // Eliminar relación del plan de usuario
    $sql = "DELETE FROM plan_usuario WHERE programa_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $materia_id);
    $stmt->execute();

    // Eliminar la materia
    $sql = "DELETE FROM programa WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $materia_id);
    $stmt->execute();

    // Confirmar la transacción
    $conn->commit();

    // Redirigir con mensaje de éxito
    header("Location: ../Pages/Home.php?success=materia_eliminada");
    exit();
} catch (Exception $e) {
    // Revertir en caso de error
    $conn->rollback();
    header("Location: ../Pages/Home.php?error=" . urlencode($e->getMessage()));
    exit();
}
?>