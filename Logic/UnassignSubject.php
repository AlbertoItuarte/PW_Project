<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Pages/Login.php");
    exit();
}

require_once '../Config/dbConection.php';

// Verificar si se envió el ID de la materia
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../Pages/Home.php?error=No se especificó la materia");
    exit();
}

$materia_id = intval($_GET['id']);

// Desasignar la materia del profesor
$sql = "UPDATE materia_ciclo SET usuario_id = NULL WHERE materia_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$materia_id]);

// Redirigir al administrador con un mensaje de éxito
header("Location: ../Pages/Home.php?success=Materia desasignada correctamente");
exit();
?>