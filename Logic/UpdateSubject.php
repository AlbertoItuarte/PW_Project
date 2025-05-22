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
    $programaId = intval($_POST['programa_id']);
    $planId = intval($_POST['plan_id']);
    $materiaNombre = $conn->real_escape_string($_POST['materiaNombre']);
    $horasTeoricas = intval($_POST['horasTeoricas']);
    $horasPracticas = intval($_POST['horasPracticas']);
    $userId = $_SESSION['user_id'];
    
    // Verificar que la materia pertenezca al usuario actual
    $sql = "SELECT p.id FROM programa p
            INNER JOIN plan_usuario pu ON p.id = pu.programa_id
            WHERE p.id = ? AND pu.usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $programaId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // La materia no existe o no pertenece al usuario
        header("Location: ../Pages/Home.php");
        exit();
    }
    
    // Iniciar transacción para garantizar la integridad de los datos
    $conn->begin_transaction();
    
    try {
        // 1. Actualizar programa
        $sql = "UPDATE programa SET materia = ?, horas_teoricas = ?, horas_practicas = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siii", $materiaNombre, $horasTeoricas, $horasPracticas, $programaId);
        $stmt->execute();
        
        // 2. Procesar eliminaciones
        // Eliminar temas
        if (isset($_POST['temas_eliminar']) && is_array($_POST['temas_eliminar'])) {
            foreach ($_POST['temas_eliminar'] as $temaId) {
                $temaId = intval($temaId);
                
                // Eliminar relación tema_usuario
                $sql = "DELETE FROM tema_usuario WHERE tema_id = ? AND plan_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $temaId, $planId);
                $stmt->execute();
                
                // Eliminar tema
                $sql = "DELETE FROM tema WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $temaId);
                $stmt->execute();
            }
        }
        
        // Eliminar unidades (y sus temas)
        if (isset($_POST['unidades_eliminar']) && is_array($_POST['unidades_eliminar'])) {
            foreach ($_POST['unidades_eliminar'] as $unidadId) {
                $unidadId = intval($unidadId);
                
                // Obtener temas de la unidad
                $sql = "SELECT id FROM tema WHERE unidad_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $unidadId);
                $stmt->execute();
                $temasResult = $stmt->get_result();
                
                while ($tema = $temasResult->fetch_assoc()) {
                    $temaId = $tema['id'];
                    
                    // Eliminar relación tema_usuario
                    $sql = "DELETE FROM tema_usuario WHERE tema_id = ? AND plan_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $temaId, $planId);
                    $stmt->execute();
                }
                
                // Eliminar todos los temas de la unidad
                $sql = "DELETE FROM tema WHERE unidad_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $unidadId);
                $stmt->execute();
                
                // Eliminar la unidad
                $sql = "DELETE FROM unidad WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $unidadId);
                $stmt->execute();
            }
        }
        
        // 3. Procesar unidades y temas
        if (isset($_POST['unidades']) && is_array($_POST['unidades'])) {
            foreach ($_POST['unidades'] as $unidadData) {
                if (empty($unidadData['nombre'])) continue;
                
                // Determinar si es una nueva unidad o una existente
                if (isset($unidadData['new']) && $unidadData['new'] === 'true') {
                    // Insertar nueva unidad
                    $nombreUnidad = $conn->real_escape_string($unidadData['nombre']);
                    $sql = "INSERT INTO unidad (programa_id, nombre) VALUES (?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("is", $programaId, $nombreUnidad);
                    $stmt->execute();
                    
                    $unidadId = $conn->insert_id;
                } else {
                    // Actualizar unidad existente
                    $unidadId = intval($unidadData['id']);
                    $nombreUnidad = $conn->real_escape_string($unidadData['nombre']);
                    $sql = "UPDATE unidad SET nombre = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("si", $nombreUnidad, $unidadId);
                    $stmt->execute();
                }
                
                // Procesar temas de la unidad
                if (isset($unidadData['temas']) && is_array($unidadData['temas'])) {
                    foreach ($unidadData['temas'] as $temaData) {
                        if (empty($temaData['nombre'])) continue;
                        
                        // Determinar si es un nuevo tema o uno existente
                        if (isset($temaData['new']) && $temaData['new'] === 'true') {
                            // Insertar nuevo tema
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
                        } else {
                            // Actualizar tema existente
                            $temaId = intval($temaData['id']);
                            $nombreTema = $conn->real_escape_string($temaData['nombre']);
                            $sql = "UPDATE tema SET nombre = ? WHERE id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("si", $nombreTema, $temaId);
                            $stmt->execute();
                            
                            // Actualizar relación tema-usuario
                            $horasEstimadas = floatval($temaData['horas']);
                            $sql = "UPDATE tema_usuario SET horas_estimadas = ? WHERE plan_id = ? AND tema_id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("dii", $horasEstimadas, $planId, $temaId);
                            $stmt->execute();
                        }
                    }
                }
            }
        }
        
        // Confirmar la transacción
        $conn->commit();
        
        // Redireccionar con mensaje de éxito
        header("Location: ../Pages/EditSubject.php?id={$programaId}&success=1");
        exit();
        
    } catch (Exception $e) {
        // Revertir en caso de error
        $conn->rollback();
        header("Location: ../Pages/EditSubject.php?id={$programaId}&error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    // Si alguien intenta acceder directamente a este archivo
    header("Location: ../Pages/Home.php");
    exit();
}