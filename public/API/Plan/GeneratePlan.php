<?php
session_start(); // Inicia la sesión PHP para poder usar variables de sesión

// Verifica si el usuario está logueado
if (!isset($_SESSION['user_id'])) { // Si no hay usuario en sesión
    header("Location: ../Pages/Login.php"); // Redirige al login
    exit(); // Detiene la ejecución
}

require_once '../../Config/dbConection.php';

// Validar que se haya enviado el ID de la materia
if (!isset($_POST['materia_ciclo_id']) || empty($_POST['materia_ciclo_id'])) { // Si no se envió el ID de la materia
    $_SESSION['error_message'] = "Error: No se proporcionó el ID de la materia."; // Guarda mensaje de error en sesión
    header("Location: ../Pages/PlanificarMateria.php"); // Redirige a la página de planificación
    exit();
}

// Validar que se haya enviado el usuario_materia_ciclo_id
if (!isset($_POST['usuario_materia_ciclo_id']) || empty($_POST['usuario_materia_ciclo_id'])) { // Si no se envió el ID de la asignación
    $_SESSION['error_message'] = "Error: No se proporcionó el ID de la asignación."; // Guarda mensaje de error en sesión
    header("Location: ../Pages/PlanificarMateria.php"); // Redirige a la página de planificación
    exit();
}

// Validar que se hayan seleccionado días
if (!isset($_POST['dias']) || empty($_POST['dias'])) { // Si no se seleccionaron días
    $_SESSION['error_message'] = "Error: No se seleccionaron días.";
    header("Location: ../Pages/PlanificarMateria.php");
    exit();
}

// Validar que se hayan proporcionado horas
if (!isset($_POST['horas']) || empty($_POST['horas'])) { // Si no se proporcionaron horas
    $_SESSION['error_message'] = "Error: No se proporcionaron horas.";
    header("Location: ../Pages/PlanificarMateria.php");
    exit();
}

// Recoge los datos del formulario
$materia_ciclo_id = intval($_POST['materia_ciclo_id']); // Convierte el ID de la materia a entero
$usuario_materia_ciclo_id = intval($_POST['usuario_materia_ciclo_id']); // NUEVO CAMPO
$dias = $_POST['dias']; // Días seleccionados por el usuario
$horas = $_POST['horas']; // Horas disponibles por día
$evaluaciones_input = isset($_POST['evaluaciones']) ? $_POST['evaluaciones'] : []; // Fechas de evaluaciones (si existen)

// Normaliza los días (quita espacios)
$dias_normalizados = array_map(function($dia) {
    return trim($dia); // Elimina espacios en blanco de cada día
}, $dias);

// Limpia y filtra las horas (solo mayores a 0)
$horas_limpias = [];
foreach ($horas as $dia => $hora) {
    $dia_limpio = trim($dia); // Elimina espacios en el nombre del día
    $hora_numerica = intval($hora); // Convierte la hora a entero
    if ($hora_numerica > 0) { // Solo toma en cuenta horas mayores a 0
        $horas_limpias[$dia_limpio] = $hora_numerica;
    }
}

// Procesa las fechas de evaluación (solo las no vacías)
$fechas_evaluacion = [];
foreach ($evaluaciones_input as $unidad_id => $fecha) {
    if (!empty($fecha)) { // Si la fecha no está vacía
        $fechas_evaluacion[] = $fecha; // Agrega la fecha al arreglo
    }
}

// Elimina evaluaciones anteriores de la materia
$sql_delete_eval = "DELETE FROM grupo_evaluacion WHERE materia_ciclo_id = ?";
$stmt_delete_eval = $pdo->prepare($sql_delete_eval);
$stmt_delete_eval->execute([$materia_ciclo_id]);

// Elimina relaciones anteriores en unidad_evaluacion
$sql_delete_unidad_eval = "DELETE FROM unidad_evaluacion 
                          WHERE grupo_eval_id IN (
                              SELECT grupo_eval_id FROM grupo_evaluacion 
                              WHERE materia_ciclo_id = ?
                          )";
$stmt_delete_unidad_eval = $pdo->prepare($sql_delete_unidad_eval);
$stmt_delete_unidad_eval->execute([$materia_ciclo_id]);

// Inserta nuevas evaluaciones y las relaciona con unidades
foreach ($evaluaciones_input as $unidad_id => $fecha) {
    if (!empty($fecha)) {
        // Obtiene el nombre de la unidad para el nombre de la evaluación
        $sql_unidad_nombre = "SELECT nombre FROM unidad WHERE unidad_id = ?";
        $stmt_unidad_nombre = $pdo->prepare($sql_unidad_nombre);
        $stmt_unidad_nombre->execute([$unidad_id]);
        $result_unidad_nombre = $stmt_unidad_nombre->fetch();
        
        if ($result_unidad_nombre) {
            // Si se encontró el nombre de la unidad, lo usa en el nombre de la evaluación
            $nombre_evaluacion = "Evaluación - " . $result_unidad_nombre['nombre'] . " (" . date('d/m/Y', strtotime($fecha)) . ")";
        } else {
            // Si no, solo pone la fecha
            $nombre_evaluacion = "Evaluación " . date('d/m/Y', strtotime($fecha));
        }
        
        // Inserta la evaluación y obtiene su ID
        $sql_insert_eval = "INSERT INTO grupo_evaluacion (nombre, materia_ciclo_id, fecha_evaluacion) VALUES (?, ?, ?) RETURNING grupo_eval_id";
        $stmt_insert_eval = $pdo->prepare($sql_insert_eval);
        $stmt_insert_eval->execute([$nombre_evaluacion, $materia_ciclo_id, $fecha]);
        $result = $stmt_insert_eval->fetch();
        $grupo_eval_id = $result['grupo_eval_id'];
        
        // Relaciona la unidad con la evaluación
        $sql_insert_unidad_eval = "INSERT INTO unidad_evaluacion (unidad_id, grupo_eval_id, porcentaje) VALUES (?, ?, 100.00)";
        $stmt_insert_unidad_eval = $pdo->prepare($sql_insert_unidad_eval);
        $stmt_insert_unidad_eval->execute([$unidad_id, $grupo_eval_id]);
    }
}

// Obtiene las fechas de inicio y fin del ciclo/semestre
$sql = "SELECT fecha_inicio, fecha_fin FROM ciclo
        WHERE ciclo_id = (SELECT ciclo_id FROM materia_ciclo WHERE materia_ciclo_id = ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$materia_ciclo_id]);
$result = $stmt->fetch();

if (!$result) { // Si no se encontraron fechas para el ciclo
    $_SESSION['error_message'] = "Error: No se encontraron fechas para el ciclo.";
    header("Location: ../Pages/PlanificarMateria.php");
    exit();
}

$ciclo = $result; // Guarda las fechas del ciclo
$fecha_inicio = new DateTime($ciclo['fecha_inicio']); // Fecha de inicio del ciclo
$fecha_fin = new DateTime($ciclo['fecha_fin']); // Fecha de fin del ciclo

// Obtiene los días feriados del ciclo
$sql = "SELECT dia FROM feriados WHERE ciclo_id = (SELECT ciclo_id FROM materia_ciclo WHERE materia_ciclo_id = ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$materia_ciclo_id]);
$result = $stmt->fetchAll();
$feriados = [];
foreach ($result as $row) {
    $feriados[] = $row['dia']; // Guarda cada día feriado en el arreglo
}

// Obtiene los temas de la materia y sus unidades
$sql = "SELECT t.tema_id, t.nombre AS tema, t.horas_estimadas, u.nombre AS unidad
        FROM tema t
        INNER JOIN unidad u ON t.unidad_id = u.unidad_id
        WHERE u.materia_ciclo_id = ?
        ORDER BY u.numero_unidad, t.orden_tema";
$stmt = $pdo->prepare($sql);
$stmt->execute([$materia_ciclo_id]);
$temas = $stmt->fetchAll();

// Valida que existan temas
if (empty($temas)) {
    $_SESSION['error_message'] = "Error: No se encontraron temas para la materia seleccionada.";
    header("Location: ../Pages/PlanificarMateria.php");
    exit();
}

// Traducción de días de la semana (inglés a español)
$day_translation = [
    'Monday' => 'Lunes',
    'Tuesday' => 'Martes',
    'Wednesday' => 'Miércoles',
    'Thursday' => 'Jueves',
    'Friday' => 'Viernes',
    'Saturday' => 'Sábado',
    'Sunday' => 'Domingo'
];

// Elimina distribuciones anteriores de ESTA ASIGNACIÓN ESPECÍFICA
$sql_delete = "DELETE FROM distribucion 
               WHERE tema_id IN (
                   SELECT t.tema_id FROM tema t 
                   INNER JOIN unidad u ON t.unidad_id = u.unidad_id 
                   WHERE u.materia_ciclo_id = ?
               ) AND usuario_materia_ciclo_id = ?";
$stmt_delete = $pdo->prepare($sql_delete);
$stmt_delete->execute([$materia_ciclo_id, $usuario_materia_ciclo_id]);

// Prepara array para los temas a insertar en la BD
$temas_para_bd = [];

// Inicializa variables para el plan
$plan = [];
$current_date = clone $fecha_inicio; // Fecha actual para iterar el ciclo
$tema_index = 0; // Índice del tema actual

// Prepara los temas con campos adicionales para el cálculo
foreach ($temas as &$tema) {
    $tema['horas_restantes'] = $tema['horas_estimadas']; // Horas que faltan por asignar
    $tema['fecha_inicio'] = null; // Fecha de inicio del tema
    $tema['fecha_fin'] = null; // Fecha de fin del tema
}

// Algoritmo principal: distribuye los temas en los días seleccionados
while ($current_date <= $fecha_fin && $tema_index < count($temas)) {
    $day_of_week = $current_date->format('l'); // Día de la semana en inglés
    $formatted_date = $current_date->format('Y-m-d'); // Fecha en formato Y-m-d

    // Salta feriados y fechas de evaluación
    if (in_array($formatted_date, $feriados) || in_array($formatted_date, $fechas_evaluacion)) {
        $current_date->modify('+1 day');
        continue;
    }

    // Traduce el día de la semana
    $day_of_week_es = $day_translation[$day_of_week] ?? $day_of_week;

    // Si el día está seleccionado por el usuario
    if (in_array($day_of_week_es, $dias_normalizados)) {
        // Obtiene las horas disponibles para ese día
        $horas_disponibles_dia = isset($horas_limpias[$day_of_week_es]) ? $horas_limpias[$day_of_week_es] : 0;

        if ($horas_disponibles_dia > 0) {
            $horas_restantes_dia = $horas_disponibles_dia;

            // Asigna horas a los temas pendientes
            while ($horas_restantes_dia > 0 && $tema_index < count($temas)) {
                $tema_actual = &$temas[$tema_index];

                if ($tema_actual['horas_restantes'] <= 0) {
                    $tema_index++;
                    continue;
                }

                // Calcula cuántas horas asignar
                $horas_a_asignar = min($tema_actual['horas_restantes'], $horas_restantes_dia);

                if ($horas_a_asignar > 0) {
                    // Si es la primera vez, establece la fecha de inicio
                    if ($tema_actual['fecha_inicio'] === null) {
                        $tema_actual['fecha_inicio'] = $formatted_date;
                    }
                    
                    // Actualiza la fecha de fin
                    $tema_actual['fecha_fin'] = $formatted_date;

                    // Resta las horas asignadas
                    $tema_actual['horas_restantes'] -= $horas_a_asignar;
                    $horas_restantes_dia -= $horas_a_asignar;

                    // Si el tema se completó, agrégalo al array para la BD
                    if ($tema_actual['horas_restantes'] <= 0) {
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

    $current_date->modify('+1 day'); // Avanza al siguiente día
}

// Inserta los temas distribuidos en la base de datos CON usuario_materia_ciclo_id
$temas_insertados = 0;
foreach ($temas_para_bd as $tema_bd) {
    $sql_insert = "INSERT INTO distribucion (tema_id, horas_asignadas, tipo_clase, fecha_inicio, fecha_fin, usuario_materia_ciclo_id)
                   VALUES (?, ?, 'Teorica', ?, ?, ?)";
    $stmt_insert = $pdo->prepare($sql_insert);
    
    if ($stmt_insert->execute([
        $tema_bd['tema_id'], 
        $tema_bd['horas_estimadas'], 
        $tema_bd['fecha_inicio'], 
        $tema_bd['fecha_fin'],
        $usuario_materia_ciclo_id  // AGREGAR ESTE CAMPO
    ])) {
        $temas_insertados++;
    } else {
        $_SESSION['error_message'] = "Error al insertar tema en la base de datos.";
        header("Location: ../Pages/PlanificarMateria.php");
        exit();
    }
}

// Construye el plan final para mostrarlo o usarlo después
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

// Si no se pudo generar el plan, muestra error
if (empty($plan)) {
    $_SESSION['error_message'] = "Error: No se pudo generar el plan. Revise los parámetros de entrada.";
    header("Location: ../Pages/PlanificarMateria.php");
    exit();
}

// Guarda el plan generado y mensaje de éxito en la sesión y redirige
$_SESSION['success_message'] = "Plan generado exitosamente. Se procesaron " . count($plan) . " temas y " . count($fechas_evaluacion) . " fechas de evaluación.";
$_SESSION['plan_generado'] = $plan;

header("Location: ../../Pages/PlanificarMateria.php");
exit();