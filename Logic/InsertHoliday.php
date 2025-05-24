<?php
require_once '../Config/dbConection.php';

$ciclo_id = $_POST['ciclo_id'];
$dia = $_POST['dia'];
$causa = $_POST['causa'];

$sql = "INSERT INTO feriados (ciclo_id, dia, causa) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $ciclo_id, $dia, $causa);
$stmt->execute();

header("Location: ../Pages/ManageCycles.php");
exit();
?>