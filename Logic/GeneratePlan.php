<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Pages/Login.php");
    exit();
}

require_once '../Config/dbConection.php';

// Validar los datos enviados
if (!isset($_POST['materia_ciclo_id']) || empty($_POST['materia_ciclo_id'])) {
    $_SESSION['error_message'] = "Error: No se proporcionó el ID de la materia.";
    header("Location: ../Pages/PlanificarMateria.php");
    exit();
}

if (!isset($_POST['dias']) || empty($_POST['dias'])) {
    $_SESSION['error_message'] = "Error: No se seleccionaron días.";
    header("Location: ../Pages/PlanificarMateria.php");
    exit();
}

if (!isset($_POST['horas']) || empty($_POST['horas'])) {
    $_SESSION['error_message'] = "Error: No se proporcionaron horas.";
    header("Location: ../Pages/PlanificarMateria.php");
    exit();
}

$materia_ciclo_id = intval($_POST['materia_ciclo_id']);
$dias = $_POST['dias'];
$horas = $_POST['horas'];
$evaluaciones_input = isset($_POST['evaluaciones']) ? $_POST['evaluaciones'] : [];

// Normalizar y limpiar datos de entrada
$dias_normalizados = array_map(function($dia) {
    return trim($dia);
}, $dias);

$horas_limpias = [];
foreach ($horas as $dia => $hora) {
    $dia_limpio = trim($dia);
    $hora_numerica = intval($hora);
    if ($hora_numerica > 0) {
        $horas_limpias[$dia_limpio] = $hora_numerica;
    }
}

// Procesar fechas de evaluación
$fechas_evaluacion = [];
foreach ($evaluaciones_input as $unidad_id => $fecha) {
    if (!empty($fecha)) {
        $fechas_evaluacion[] = $fecha;
    }
}

// Limpiar evaluaciones anteriores para esta materia
$sql_delete_eval = "DELETE FROM grupo_evaluacion WHERE materia_ciclo_id = ?";
$stmt_delete_eval = $conn->prepare($sql_delete_eval);
$stmt_delete_eval->bind_param("i", $materia_ciclo_id);
$stmt_delete_eval->execute();

// También limpiar las relaciones en unidad_evaluacion
$sql_delete_unidad_eval = "DELETE ue FROM unidad_evaluacion ue 
                          INNER JOIN grupo_evaluacion ge ON ue.grupo_eval_id = ge.grupo_eval_id 
                          WHERE ge.materia_ciclo_id = ?";
$stmt_delete_unidad_eval = $conn->prepare($sql_delete_unidad_eval);
$stmt_delete_unidad_eval->bind_param("i", $materia_ciclo_id);
$stmt_delete_unidad_eval->execute();

// Insertar nuevas evaluaciones con nombre descriptivo y relación con unidades
foreach ($evaluaciones_input as $unidad_id => $fecha) {
    if (!empty($fecha)) {
        // Obtener el nombre de la unidad para crear un nombre descriptivo
        $sql_unidad_nombre = "SELECT nombre FROM unidad WHERE unidad_id = ?";
        $stmt_unidad_nombre = $conn->prepare($sql_unidad_nombre);
        $stmt_unidad_nombre->bind_param("i", $unidad_id);
        $stmt_unidad_nombre->execute();
        $result_unidad_nombre = $stmt_unidad_nombre->get_result();
        
        if ($result_unidad_nombre->num_rows > 0) {
            $unidad_data = $result_unidad_nombre->fetch_assoc();
            $nombre_evaluacion = "Evaluación - " . $unidad_data['nombre'] . " (" . date('d/m/Y', strtotime($fecha)) . ")";
        } else {
            $nombre_evaluacion = "Evaluación " . date('d/m/Y', strtotime($fecha));
        }
        
        // Insertar en grupo_evaluacion
        $sql_insert_eval = "INSERT INTO grupo_evaluacion (nombre, materia_ciclo_id, fecha_evaluacion) VALUES (?, ?, ?)";
        $stmt_insert_eval = $conn->prepare($sql_insert_eval);
        $stmt_insert_eval->bind_param("sis", $nombre_evaluacion, $materia_ciclo_id, $fecha);
        $stmt_insert_eval->execute();
        
        // Obtener el ID del grupo de evaluación recién insertado
        $grupo_eval_id = $conn->insert_id;
        
        // Insertar en unidad_evaluacion para relacionar la unidad específica con la evaluación
        $sql_insert_unidad_eval = "INSERT INTO unidad_evaluacion (unidad_id, grupo_eval_id, porcentaje) VALUES (?, ?, 100.00)";
        $stmt_insert_unidad_eval = $conn->prepare($sql_insert_unidad_eval);
        $stmt_insert_unidad_eval->bind_param("ii", $unidad_id, $grupo_eval_id);
        $stmt_insert_unidad_eval->execute();
    }
}

// Obtener las fechas de inicio y fin del semestre
$sql = "SELECT fecha_inicio, fecha_fin FROM ciclo
        WHERE ciclo_id = (SELECT ciclo_id FROM materia_ciclo WHERE materia_ciclo_id = ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $materia_ciclo_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Error: No se encontraron fechas para el ciclo.";
    header("Location: ../Pages/PlanificarMateria.php");
    exit();
}

$ciclo = $result->fetch_assoc();
$fecha_inicio = new DateTime($ciclo['fecha_inicio']);
$fecha_fin = new DateTime($ciclo['fecha_fin']);

// Obtener los días feriados
$sql = "SELECT dia FROM feriados WHERE ciclo_id = (SELECT ciclo_id FROM materia_ciclo WHERE materia_ciclo_id = ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $materia_ciclo_id);
$stmt->execute();
$result = $stmt->get_result();
$feriados = [];
while ($row = $result->fetch_assoc()) {
    $feriados[] = $row['dia'];
}

// Obtener los temas de la materia
$sql = "SELECT t.tema_id, t.nombre AS tema, t.horas_estimadas, u.nombre AS unidad
        FROM tema t
        INNER JOIN unidad u ON t.unidad_id = u.unidad_id
        WHERE u.materia_ciclo_id = ?
        ORDER BY u.numero_unidad, t.orden_tema";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $materia_ciclo_id);
$stmt->execute();
$result = $stmt->get_result();
$temas = $result->fetch_all(MYSQLI_ASSOC);

// Validar si se encontraron temas
if (empty($temas)) {
    $_SESSION['error_message'] = "Error: No se encontraron temas para la materia seleccionada.";
    header("Location: ../Pages/PlanificarMateria.php");
    exit();
}

// Mapa de traducción de días de la semana
$day_translation = [
    'Monday' => 'Lunes',
    'Tuesday' => 'Martes',
    'Wednesday' => 'Miércoles',
    'Thursday' => 'Jueves',
    'Friday' => 'Viernes',
    'Saturday' => 'Sábado',
    'Sunday' => 'Domingo'
];

// Limpiar distribuciones anteriores para esta materia
$sql_delete = "DELETE d FROM distribucion d 
               INNER JOIN tema t ON d.tema_id = t.tema_id 
               INNER JOIN unidad u ON t.unidad_id = u.unidad_id 
               WHERE u.materia_ciclo_id = ?";
$stmt_delete = $conn->prepare($sql_delete);
$stmt_delete->bind_param("i", $materia_ciclo_id);
$stmt_delete->execute();

// Array para almacenar los temas a insertar en la BD
$temas_para_bd = [];

// Calcular la distribución
$plan = [];
$current_date = clone $fecha_inicio;
$tema_index = 0;

// Preparar temas con información adicional
foreach ($temas as &$tema) {
    $tema['horas_restantes'] = $tema['horas_estimadas'];
    $tema['fecha_inicio'] = null;
    $tema['fecha_fin'] = null;
}

while ($current_date <= $fecha_fin && $tema_index < count($temas)) {
    $day_of_week = $current_date->format('l');
    $formatted_date = $current_date->format('Y-m-d');

    // Excluir días feriados y de evaluación (ahora usando las fechas del formulario)
    if (in_array($formatted_date, $feriados) || in_array($formatted_date, $fechas_evaluacion)) {
        $current_date->modify('+1 day');
        continue;
    }

    // Traducir el día de la semana
    $day_of_week_es = $day_translation[$day_of_week] ?? $day_of_week;

    // Verificar si el día está en los días seleccionados
    if (in_array($day_of_week_es, $dias_normalizados)) {
        // Obtener horas disponibles para este día
        $horas_disponibles_dia = isset($horas_limpias[$day_of_week_es]) ? $horas_limpias[$day_of_week_es] : 0;

        if ($horas_disponibles_dia > 0) {
            $horas_restantes_dia = $horas_disponibles_dia;

            // Asignar horas a los temas pendientes
            while ($horas_restantes_dia > 0 && $tema_index < count($temas)) {
                $tema_actual = &$temas[$tema_index];

                if ($tema_actual['horas_restantes'] <= 0) {
                    $tema_index++;
                    continue;
                }

                // Calcular horas a asignar
                $horas_a_asignar = min($tema_actual['horas_restantes'], $horas_restantes_dia);

                if ($horas_a_asignar > 0) {
                    // Establecer fecha de inicio si es la primera vez
                    if ($tema_actual['fecha_inicio'] === null) {
                        $tema_actual['fecha_inicio'] = $formatted_date;
                    }
                    
                    // Actualizar fecha de fin
                    $tema_actual['fecha_fin'] = $formatted_date;

                    // Reducir horas restantes
                    $tema_actual['horas_restantes'] -= $horas_a_asignar;
                    $horas_restantes_dia -= $horas_a_asignar;

                    // Si el tema se completó, agregarlo al array para insertar en BD
                    if ($tema_actual['horas_restantes'] <= 0) {
                        // Agregar tema completado al array para BD
                        $temas_para_bd[] = [
                            'tema_id' => $tema_actual['tema_id'],
                            'fecha_inicio' => $tema_actual['fecha_inicio'],
                            'fecha_fin' => $tema_actual['fecha_fin'],
                            'horas_estimadas' => $tema_actual['horas_estimadas']
                        ];
                        
                        $tema_index++;
                    }
                }
            }
        }
    }

    $current_date->modify('+1 day');
}

// Insertar los temas completados en la base de datos
$temas_insertados = 0;
foreach ($temas_para_bd as $tema_bd) {
    $sql_insert = "INSERT INTO distribucion (tema_id, horas_asignadas, tipo_clase, fecha_inicio, fecha_fin)
                   VALUES (?, ?, 'Teorica', ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("idss", 
        $tema_bd['tema_id'], 
        $tema_bd['horas_estimadas'], 
        $tema_bd['fecha_inicio'], 
        $tema_bd['fecha_fin']
    );
    
    if ($stmt_insert->execute()) {
        $temas_insertados++;
    } else {
        $_SESSION['error_message'] = "Error al insertar tema en la base de datos: " . $stmt_insert->error;
        header("Location: ../Pages/PlanificarMateria.php");
        exit();
    }
}

// Crear el plan final sin repeticiones - solo fechas de inicio y fin por tema
foreach ($temas as $tema) {
    if ($tema['fecha_inicio'] !== null && $tema['fecha_fin'] !== null) {
        $plan[] = [
            'unidad' => $tema['unidad'],
            'tema' => $tema['tema'],
            'fecha_inicio' => $tema['fecha_inicio'],
            'fecha_fin' => $tema['fecha_fin'],
            'horas_estimadas' => $tema['horas_estimadas'],
            'tema_id' => $tema['tema_id']
        ];
    }
}

// Verificar si se generó algún plan
if (empty($plan)) {
    $_SESSION['error_message'] = "Error: No se pudo generar el plan. Revise los parámetros de entrada.";
    header("Location: ../Pages/PlanificarMateria.php");
    exit();
}

// Éxito: guardar el plan en la sesión y redirigir
$_SESSION['success_message'] = "Plan generado exitosamente. Se procesaron " . count($plan) . " temas y " . count($fechas_evaluacion) . " fechas de evaluación.";
$_SESSION['plan_generado'] = $plan;

header("Location: ../Pages/PlanificarMateria.php");
exit();
?>