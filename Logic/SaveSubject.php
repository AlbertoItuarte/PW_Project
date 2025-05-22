<?php
session_start();
require_once '../Config/dbConection.php';

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Pages/Login.php");
    exit();
}

// Verificar si se enviaron los datos del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener datos de la materia
    $materiaNombre = $conn->real_escape_string($_POST['materiaNombre']);
    $horasTeoricas = intval($_POST['horasTeoricas']);
    $horasPracticas = intval($_POST['horasPracticas']);
    
    // Iniciar transacción para garantizar la integridad de los datos
    $conn->begin_transaction();
    
    try {
        // 1. Insertar programa
        $sql = "INSERT INTO programa (materia, horas_teoricas, horas_practicas) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $materiaNombre, $horasTeoricas, $horasPracticas);
        $stmt->execute();
        
        $programaId = $conn->insert_id;
        
        // 2. Insertar plan de usuario
        $userId = $_SESSION['user_id'];
        $fechaHoy = date('Y-m-d');
        
        $sql = "INSERT INTO plan_usuario (usuario_id, programa_id, fecha_evaluacion) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $userId, $programaId, $fechaHoy);
        $stmt->execute();
        
        $planId = $conn->insert_id;
        
        // 3. Insertar unidades y temas
        if (isset($_POST['unidades']) && is_array($_POST['unidades'])) {
            foreach ($_POST['unidades'] as $unidadData) {
                if (empty($unidadData['nombre'])) continue;
                
                // Insertar unidad
                $nombreUnidad = $conn->real_escape_string($unidadData['nombre']);
                $sql = "INSERT INTO unidad (programa_id, nombre) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("is", $programaId, $nombreUnidad);
                $stmt->execute();
                
                $unidadId = $conn->insert_id;
                
                // Insertar temas
                if (isset($unidadData['temas']) && is_array($unidadData['temas'])) {
                    foreach ($unidadData['temas'] as $temaData) {
                        if (empty($temaData['nombre'])) continue;
                        
                        // Insertar tema
                        $nombreTema = $conn->real_escape_string($temaData['nombre']);
                        $sql = "INSERT INTO tema (unidad_id, nombre) VALUES (?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("is", $unidadId, $nombreTema);
                        $stmt->execute();
                        
                        $temaId = $conn->insert_id;
                        
                        // Insertar relación tema-usuario
                        $horasEstimadas = floatval($temaData['horas']);
                        $sql = "INSERT INTO tema_usuario (plan_id, tema_id, horas_estimadas) VALUES (?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("iid", $planId, $temaId, $horasEstimadas);
                        $stmt->execute();
                    }
                }
            }
        }
        
        // Confirmar la transacción
        $conn->commit();
        
        // Redireccionar con mensaje de éxito
        header("Location: ../Pages/PlanSubject.php?success=1");
        exit();
        
    } catch (Exception $e) {
        // Revertir en caso de error
        $conn->rollback();
        header("Location: ../Pages/PlanSubject.php?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    // Si alguien intenta acceder directamente a este archivo
    header("Location: ../Pages/PlanSubject.php");
    exit();
}
