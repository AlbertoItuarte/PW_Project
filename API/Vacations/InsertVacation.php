<?php
require_once '../../Config/dbConection.php';

$ciclo_id = $_POST['ciclo_id'];
$fecha_inicio = $_POST['fecha_inicio'];
$fecha_fin = $_POST['fecha_fin'];
$descripcion = $_POST['descripcion'];

$sql = "INSERT INTO vacaciones (ciclo_id, fecha_inicio, fecha_fin, descripcion) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("isss", $ciclo_id, $fecha_inicio, $fecha_fin, $descripcion);
$stmt->execute();

header("Location: ../../Pages/ManageCycles.php");
exit();
?>