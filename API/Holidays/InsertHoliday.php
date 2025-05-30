<?php
require_once '../../Config/dbConection.php';

$ciclo_id = $_POST['ciclo_id'];
$dia = $_POST['dia'];
$causa = $_POST['causa'];

$sql = "INSERT INTO feriados (ciclo_id, dia, causa) VALUES (?, ?, ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$ciclo_id, $dia, $causa]);

header("Location: ../../Pages/ManageCycles.php");
exit();
?>