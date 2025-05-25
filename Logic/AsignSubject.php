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

// Preparar la consulta para asignar materias
$sql = "INSERT INTO materia_ciclo (materia_id, usuario_id, ciclo_id) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);

$ciclo_id = 10; // Cambiar esto si necesitas obtener el ciclo dinámicamente

foreach ($_POST['materias'] as $materia_id) {
    $materia_id = intval($materia_id);
    $stmt->bind_param("iii", $materia_id, $user_id, $ciclo_id);
    $stmt->execute();
}

// Redirigir al usuario con un mensaje de éxito
header("Location: ../Pages/PlanificarMateria.php?success=Materias asignadas correctamente");
exit();