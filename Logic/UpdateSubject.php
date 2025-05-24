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
    $materiaId = intval($_POST['materia_id']);
    $materiaCicloId = intval($_POST['materia_ciclo_id']);
    $materiaNombre = $conn->real_escape_string($_POST['materiaNombre']);
    $horasTeoricas = intval($_POST['horasTeoricas']);
    $horasPracticas = intval($_POST['horasPracticas']);
    $userId = $_SESSION['user_id'];

    // Verificar que la materia pertenezca al usuario actual
    $sql = "SELECT mc.materia_ciclo_id 
            FROM materia_ciclo mc
            WHERE mc.materia_ciclo_id = ? AND mc.usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $materiaCicloId, $userId);
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
        // 1. Actualizar materia
        $sql = "UPDATE materia SET nombre = ? WHERE materia_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $materiaNombre, $materiaId);
        $stmt->execute();

        // 2. Actualizar relación materia_ciclo
        $sql = "UPDATE materia_ciclo SET horas_teoricas = ?, horas_practicas = ? WHERE materia_ciclo_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $horasTeoricas, $horasPracticas, $materiaCicloId);
        $stmt->execute();

        // 3. Procesar eliminaciones
        // Eliminar temas
        if (isset($_POST['temas_eliminar']) && is_array($_POST['temas_eliminar'])) {
            foreach ($_POST['temas_eliminar'] as $temaId) {
                $temaId = intval($temaId);

                // Eliminar tema
                $sql = "DELETE FROM tema WHERE tema_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $temaId);
                $stmt->execute();
            }
        }

        // Eliminar unidades (y sus temas)
        if (isset($_POST['unidades_eliminar']) && is_array($_POST['unidades_eliminar'])) {
            foreach ($_POST['unidades_eliminar'] as $unidadId) {
                $unidadId = intval($unidadId);

                // Eliminar todos los temas de la unidad
                $sql = "DELETE FROM tema WHERE unidad_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $unidadId);
                $stmt->execute();

                // Eliminar la unidad
                $sql = "DELETE FROM unidad WHERE unidad_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $unidadId);
                $stmt->execute();
            }
        }

        // 4. Procesar unidades y temas
        if (isset($_POST['unidades']) && is_array($_POST['unidades'])) {
            foreach ($_POST['unidades'] as $unidadData) {
                if (empty($unidadData['nombre'])) continue;

                // Validar que numero_unidad sea mayor que 0
                if (!isset($unidadData['numero_unidad']) || intval($unidadData['numero_unidad']) <= 0) {
                    $conn->rollback();
                    header("Location: ../Pages/EditSubject.php?id={$materiaId}&error=" . urlencode("El número de unidad debe ser mayor que 0."));
                    exit();
                }

                $numeroUnidad = intval($unidadData['numero_unidad']);

                // Verificar si el número de unidad ya existe para el mismo materia_ciclo_id
                $sql = "SELECT unidad_id FROM unidad WHERE materia_ciclo_id = ? AND numero_unidad = ? AND unidad_id != ?";
                $stmt = $conn->prepare($sql);
                $unidadId = isset($unidadData['id']) ? intval($unidadData['id']) : 0; // Si es nueva unidad, unidad_id será 0
                $stmt->bind_param("iii", $materiaCicloId, $numeroUnidad, $unidadId);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    // Si ya existe una unidad con el mismo número, lanzar un error
                    $conn->rollback();
                    header("Location: ../Pages/EditSubject.php?id={$materiaId}&error=" . urlencode("El número de unidad {$numeroUnidad} ya existe."));
                    exit();
                }

                // Determinar si es una nueva unidad o una existente
                if (isset($unidadData['new']) && $unidadData['new'] === 'true') {
                    // Insertar nueva unidad
                    $nombreUnidad = $conn->real_escape_string($unidadData['nombre']);
                    $sql = "INSERT INTO unidad (materia_ciclo_id, nombre, numero_unidad) VALUES (?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("isi", $materiaCicloId, $nombreUnidad, $numeroUnidad);
                    $stmt->execute();

                    $unidadId = $conn->insert_id;
                } else {
                    // Actualizar unidad existente
                    $unidadId = intval($unidadData['id']);
                    $nombreUnidad = $conn->real_escape_string($unidadData['nombre']);
                    $sql = "UPDATE unidad SET nombre = ?, numero_unidad = ? WHERE unidad_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sii", $nombreUnidad, $numeroUnidad, $unidadId);
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
                            $horasEstimadas = floatval($temaData['horas']);
                            $sql = "INSERT INTO tema (unidad_id, nombre, horas_estimadas) VALUES (?, ?, ?)";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("isd", $unidadId, $nombreTema, $horasEstimadas);
                            $stmt->execute();
                        } else {
                            // Actualizar tema existente
                            $temaId = intval($temaData['id']);
                            $nombreTema = $conn->real_escape_string($temaData['nombre']);
                            $horasEstimadas = floatval($temaData['horas']);
                            $sql = "UPDATE tema SET nombre = ?, horas_estimadas = ? WHERE tema_id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("sdi", $nombreTema, $horasEstimadas, $temaId);
                            $stmt->execute();
                        }
                    }
                }
            }
        }

        // Confirmar la transacción
        $conn->commit();

        // Redireccionar con mensaje de éxito
        header("Location: ../Pages/EditSubject.php?id={$materiaId}&success=1");
        exit();
    } catch (Exception $e) {
        // Revertir en caso de error
        $conn->rollback();
        header("Location: ../Pages/EditSubject.php?id={$materiaId}&error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    // Si alguien intenta acceder directamente a este archivo
    header("Location: ../Pages/Home.php");
    exit();
}