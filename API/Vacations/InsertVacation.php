<?php
require_once '../../Config/dbConection.php';

$ciclo_id = $_POST['ciclo_id'];
$fecha_inicio = $_POST['fecha_inicio'];
$fecha_fin = $_POST['fecha_fin'];
$descripcion = $_POST['descripcion'];

$sql = "INSERT INTO vacaciones (ciclo_id, fecha_inicio, fecha_fin, descripcion) VALUES (?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$ciclo_id, $fecha_inicio, $fecha_fin, $descripcion]);

header("Location: ../../Pages/ManageCycles.php");
exit();
?>