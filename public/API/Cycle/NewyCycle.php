<?php
session_start();
require_once '../../Config/dbConection.php';

$nombre = $_POST['nombre'];
$fecha_inicio = $_POST['fecha_inicio'];
$fecha_fin = $_POST['fecha_fin'];
$usuario_id = $_SESSION['user_id'];

$sql = "INSERT INTO ciclo (nombre, fecha_inicio, fecha_fin, usuario_id) VALUES (?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$nombre, $fecha_inicio, $fecha_fin, $usuario_id]);

header("Location: ../../Pages/CreateCycle.php");
exit();