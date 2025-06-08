<?php
require_once '../../Config/dbConection.php';

// Validar que se recibieron los datos necesarios
if (!isset($_POST['ciclo_id']) || !isset($_POST['dia']) || !isset($_POST['causa'])) {
    header("Location: ../../Pages/NewHoliday.php?error=Datos incompletos");
    exit();
}

$ciclo_id = intval($_POST['ciclo_id']);
$dia = $_POST['dia'];
$causa = trim($_POST['causa']);

// Validar que los datos no estén vacíos
if (empty($ciclo_id) || empty($dia) || empty($causa)) {
    header("Location: ../../Pages/NewHoliday.php?error=Todos los campos son obligatorios");
    exit();
}

try {
    // Verificar si ya existe un feriado en esa fecha para ese ciclo
    $sql_check = "SELECT COUNT(*) FROM feriados WHERE ciclo_id = ? AND dia = ?";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$ciclo_id, $dia]);
    $existe = $stmt_check->fetchColumn();

    if ($existe > 0) {
        header("Location: ../../Pages/NewHoliday.php?error=Ya existe un feriado registrado para esa fecha en este ciclo");
        exit();
    }

    // Si no existe, insertar el nuevo feriado
    $sql = "INSERT INTO feriados (ciclo_id, dia, causa) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$ciclo_id, $dia, $causa])) {
        header("Location: ../../Pages/ManageCycles.php?success=Feriado agregado correctamente");
    } else {
        header("Location: ../../Pages/NewHoliday.php?error=Error al insertar el feriado");
    }
    
} catch (PDOException $e) {
    error_log("Error en InsertHoliday.php: " . $e->getMessage());
    header("Location: ../../Pages/NewHoliday.php?error=Error de base de datos");
} catch (Exception $e) {
    error_log("Error general en InsertHoliday.php: " . $e->getMessage());
    header("Location: ../../Pages/NewHoliday.php?error=Error interno del servidor");
}

exit();
