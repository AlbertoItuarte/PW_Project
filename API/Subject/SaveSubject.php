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
    $horasPracticas = floatval($_POST['horasPracticas']);
    
    // Iniciar transacción para garantizar la integridad de los datos
    $conn->begin_transaction();
    
    try {
        // 1. Insertar programa
        $sql = "INSERT INTO materia (nombre, codigo, descripcion) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $nombreMateria, $codigoMateria, $descripcionMateria);
        $stmt->execute();
        
        $materiaId = $conn->insert_id;

        // 2. Insertar relación con el ciclo
        $ciclo = 7;
        $usuario_id = $_SESSION['user_id'];
        $fechaHoy = date('Y-m-d');
        $horas_totales = $horasTeoricas + $horasPracticas;

        $sql = "INSERT INTO materia_ciclo (usuario_id, materia_id, ciclo_id, horas_teoricas, horas_practicas, fecha_asignacion) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiidds", $usuario_id, $materiaId, $ciclo, $horasTeoricas, $horasPracticas, $fechaHoy);
        $stmt->execute();

        $materiaCiclo = $conn->insert_id;

        // 3. Insertar unidades y temas
        if (isset($_POST['unidades']) && is_array($_POST['unidades'])) {
            $numero_unidad = 1;
            foreach ($_POST['unidades'] as $unidadData) {
                if (empty($unidadData['nombre'])) continue;

                // Insertar unidad
                $nombreUnidad = $conn->real_escape_string($unidadData['nombre']);
                $descripcionUnidad = isset($unidadData['descripcion']) ? $conn->real_escape_string($unidadData['descripcion']) : '';
                $sql = "INSERT INTO unidad (nombre, numero_unidad, descripcion, materia_ciclo_id, fecha_creacion) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sisis", $nombreUnidad, $numero_unidad, $descripcionUnidad, $materiaCiclo, $fechaHoy);
                $stmt->execute();
                $unidadId = $conn->insert_id;

                // Insertar temas
                if (isset($unidadData['temas']) && is_array($unidadData['temas'])) {
                    $numero_tema = 1;
                    foreach ($unidadData['temas'] as $temaData) {
                        if (empty($temaData['nombre'])) continue;

                        // Insertar tema
                        $nombreTema = $conn->real_escape_string($temaData['nombre']);
                        $horasEstimadas = floatval($temaData['horas']);
                        $sql = "INSERT INTO tema (nombre, orden_tema, horas_estimadas, unidad_id, fecha_creacion) VALUES (?, ?, ?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("sidis", $nombreTema, $numero_tema, $horasEstimadas, $unidadId, $fechaHoy);
                        $stmt->execute();
                        $temaId = $conn->insert_id;

                        $numero_tema++;
                    }
                }

                $numero_unidad++;
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
