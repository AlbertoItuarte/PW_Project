<?php
session_start();
require_once '../Config/dbConection.php';

$nombre = $_POST['nombre'];
$fecha_inicio = $_POST['fecha_inicio'];
$fecha_fin = $_POST['fecha_fin'];
$usuario_id = $_SESSION['user_id'];

$sql = "INSERT INTO ciclo (nombre, fecha_inicio, fecha_fin, usuario_id) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssi", $nombre, $fecha_inicio, $fecha_fin, $usuario_id);
$stmt->execute();

header("Location: ../Pages/ManageCycles.php");
exit();
?>