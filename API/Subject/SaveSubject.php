<?php
session_start();
require_once '../../Config/dbConection.php';

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../Pages/Login.php");
    exit();
}

// Verificar si se enviaron los datos del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener datos de la materia
    $nombreMateria = $conn->real_escape_string($_POST['nombreMateria']);
    $codigoMateria = $conn->real_escape_string($_POST['codigoMateria']);
    $descripcionMateria = $conn->real_escape_string($_POST['descripcionMateria']);
    $horasTeoricas = floatval($_POST['horasTeoricas']);
    $horasPracticas = isset($_POST['horasPracticas']) ? floatval($_POST['horasPracticas']) : 0;
    $ciclo = intval($_POST['ciclo']);
    
    // Iniciar transacción para garantizar la integridad de los datos
    $conn->begin_transaction();
    
    try {
        // 1. Insertar materia
        $sql = "INSERT INTO materia (nombre, codigo, descripcion) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $nombreMateria, $codigoMateria, $descripcionMateria);
        $stmt->execute();
        
        $materiaId = $conn->insert_id;

        // 2. Insertar relación con el ciclo
        $usuario_id = $_SESSION['user_id'];
        $fechaHoy = date('Y-m-d H:i:s');

        $sql = "INSERT INTO materia_ciclo (materia_id, ciclo_id, horas_teoricas, horas_practicas, fecha_asignacion) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiidds", $materiaId, $ciclo,  $horasTeoricas, $horasPracticas, $fechaHoy);
        $stmt->execute();

        $materiaCicloId = $conn->insert_id;

        // 3. Insertar relación usuario-materia-ciclo
        $sql = "INSERT INTO usuario_materia_ciclo (usuario_id, materia_ciclo_id, fecha_asignacion) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $usuario_id, $materiaCicloId, $fechaHoy);
        $stmt->execute();

        // 4. Insertar unidades y temas
        if (isset($_POST['unidades']) && is_array($_POST['unidades'])) {
            foreach ($_POST['unidades'] as $unidadNumero => $unidadData) {
                if (empty($unidadData['nombre'])) continue;

                // Insertar unidad
                $nombreUnidad = $conn->real_escape_string($unidadData['nombre']);
                $descripcionUnidad = isset($unidadData['descripcion']) ? $conn->real_escape_string($unidadData['descripcion']) : '';
                
                $sql = "INSERT INTO unidad (nombre, numero_unidad, descripcion, materia_ciclo_id, fecha_creacion) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sisis", $nombreUnidad, $unidadNumero, $descripcionUnidad, $materiaCicloId, $fechaHoy);
                $stmt->execute();
                $unidadId = $conn->insert_id;

                // Insertar temas de esta unidad
                if (isset($unidadData['temas']) && is_array($unidadData['temas'])) {
                    foreach ($unidadData['temas'] as $temaNumero => $temaData) {
                        if (empty($temaData['nombre'])) continue;

                        // Insertar tema
                        $nombreTema = $conn->real_escape_string($temaData['nombre']);
                        $horasEstimadas = floatval($temaData['horas']);
                        $ordenTema = $temaNumero + 1; // +1 porque los índices empiezan en 0
                        
                        $sql = "INSERT INTO tema (nombre, orden_tema, horas_estimadas, unidad_id, fecha_creacion) VALUES (?, ?, ?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("sidis", $nombreTema, $ordenTema, $horasEstimadas, $unidadId, $fechaHoy);
                        $stmt->execute();
                    }
                }
            }
        }
        
        // Confirmar la transacción
        $conn->commit();
        
        // Redireccionar con mensaje de éxito
        header("Location: ../../Pages/PlanSubject.php?success=1");
        exit();
        
    } catch (Exception $e) {
        // Revertir en caso de error
        $conn->rollback();
        header("Location: ../../Pages/PlanSubject.php?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    // Si alguien intenta acceder directamente a este archivo
    header("Location: ../../Pages/PlanSubject.php");
    exit();
}
?>