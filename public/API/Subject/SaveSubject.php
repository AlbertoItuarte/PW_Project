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
    $nombreMateria = $_POST['nombreMateria'];
    $codigoMateria = $_POST['codigoMateria'];
    $descripcionMateria = $_POST['descripcionMateria'];
    $horasTeoricas = floatval($_POST['horasTeoricas']);
    $horasPracticas = isset($_POST['horasPracticas']) ? floatval($_POST['horasPracticas']) : 0;
    $ciclo = intval($_POST['ciclo']);
    
    // Iniciar transacción para garantizar la integridad de los datos
    $pdo->beginTransaction();
    
    try {
        // Insertar materia
        $sql = "INSERT INTO materia (nombre, codigo, descripcion) VALUES (?, ?, ?) RETURNING materia_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombreMateria, $codigoMateria, $descripcionMateria]);
        $result = $stmt->fetch();
        $materiaId = $result['materia_id'];

        // Insertar relación con el ciclo
        $usuario_id = $_SESSION['user_id'];
        $fechaHoy = date('Y-m-d H:i:s');

        $sql = "INSERT INTO materia_ciclo (materia_id, ciclo_id, usuario_id, horas_teoricas, horas_practicas, fecha_asignacion) VALUES (?, ?, ?, ?, ?, ?) RETURNING materia_ciclo_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$materiaId, $ciclo, $usuario_id, $horasTeoricas, $horasPracticas, $fechaHoy]);
        $result = $stmt->fetch();
        $materiaCicloId = $result['materia_ciclo_id'];

        // Insertar unidades y temas
        if (isset($_POST['unidades']) && is_array($_POST['unidades'])) {
            foreach ($_POST['unidades'] as $unidadNumero => $unidadData) {
                if (empty($unidadData['nombre'])) continue;

                // Insertar unidad
                $nombreUnidad = $unidadData['nombre'];
                $descripcionUnidad = isset($unidadData['descripcion']) ? $unidadData['descripcion'] : '';
                
                $sql = "INSERT INTO unidad (nombre, numero_unidad, descripcion, materia_ciclo_id, fecha_creacion) VALUES (?, ?, ?, ?, ?) RETURNING unidad_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nombreUnidad, $unidadNumero, $descripcionUnidad, $materiaCicloId, $fechaHoy]);
                $result = $stmt->fetch();
                $unidadId = $result['unidad_id'];

                // Insertar temas de esta unidad
                if (isset($unidadData['temas']) && is_array($unidadData['temas'])) {
                    foreach ($unidadData['temas'] as $temaNumero => $temaData) {
                        if (empty($temaData['nombre'])) continue;

                        // Insertar tema
                        $nombreTema = $temaData['nombre'];
                        $horasEstimadas = floatval($temaData['horas']);
                        $ordenTema = $temaNumero + 1; // +1 porque los índices empiezan en 0
                        
                        $sql = "INSERT INTO tema (nombre, orden_tema, horas_estimadas, unidad_id, fecha_creacion) VALUES (?, ?, ?, ?, ?)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$nombreTema, $ordenTema, $horasEstimadas, $unidadId, $fechaHoy]);
                    }
                }
            }
        }
        
        // Confirmar la transacción
        $pdo->commit();
        
        // Redireccionar con mensaje de éxito
        header("Location: ../../Pages/PlanSubject.php?success=1");
        exit();
        
    } catch (Exception $e) {
        // Revertir en caso de error
        $pdo->rollback();
        header("Location: ../../Pages/PlanSubject.php?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    // Si alguien intenta acceder directamente a este archivo
    header("Location: ../../Pages/PlanSubject.php");
    exit();
}
?>