<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Pages/Login.php");
    exit();
}

require_once '../Config/dbConection.php';

// Depuración: registrar los datos enviados
file_put_contents('debug_generate_plan.log', "=== NUEVO PROCESAMIENTO ===\n", FILE_APPEND);
file_put_contents('debug_generate_plan.log', print_r($_POST, true), FILE_APPEND);

// Validar los datos enviados
if (!isset($_POST['materia_ciclo_id']) || empty($_POST['materia_ciclo_id'])) {
    die("Error: No se proporcionó el ID de la materia.");
}

if (!isset($_POST['dias']) || empty($_POST['dias'])) {
    die("Error: No se seleccionaron días.");
}

if (!isset($_POST['horas']) || empty($_POST['horas'])) {
    die("Error: No se proporcionaron horas.");
}

$materia_ciclo_id = intval($_POST['materia_ciclo_id']);
$dias = $_POST['dias'];
$horas = $_POST['horas'];

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

file_put_contents('debug_generate_plan.log', "Días normalizados: " . print_r($dias_normalizados, true), FILE_APPEND);
file_put_contents('debug_generate_plan.log', "Horas limpias: " . print_r($horas_limpias, true), FILE_APPEND);

// Obtener las fechas de inicio y fin del semestre
$sql = "SELECT fecha_inicio, fecha_fin FROM ciclo
        WHERE ciclo_id = (SELECT ciclo_id FROM materia_ciclo WHERE materia_ciclo_id = ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $materia_ciclo_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Error: No se encontraron fechas para el ciclo.");
}

$ciclo = $result->fetch_assoc();
$fecha_inicio = new DateTime($ciclo['fecha_inicio']);
$fecha_fin = new DateTime($ciclo['fecha_fin']);

file_put_contents('debug_generate_plan.log', "Fecha inicio: " . $fecha_inicio->format('Y-m-d') . "\n", FILE_APPEND);
file_put_contents('debug_generate_plan.log', "Fecha fin: " . $fecha_fin->format('Y-m-d') . "\n", FILE_APPEND);

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

// Obtener las fechas de evaluación
$sql = "SELECT fecha_evaluacion FROM grupo_evaluacion WHERE materia_ciclo_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $materia_ciclo_id);
$stmt->execute();
$result = $stmt->get_result();
$evaluaciones = [];
while ($row = $result->fetch_assoc()) {
    $evaluaciones[] = $row['fecha_evaluacion'];
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
    die("Error: No se encontraron temas para la materia seleccionada.");
}

file_put_contents('debug_generate_plan.log', "Temas encontrados: " . count($temas) . "\n", FILE_APPEND);
file_put_contents('debug_generate_plan.log', "Temas: " . print_r($temas, true), FILE_APPEND);

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

file_put_contents('debug_generate_plan.log', "Iniciando distribución...\n", FILE_APPEND);

while ($current_date <= $fecha_fin && $tema_index < count($temas)) {
    $day_of_week = $current_date->format('l');
    $formatted_date = $current_date->format('Y-m-d');

    // Excluir días feriados y de evaluación
    if (in_array($formatted_date, $feriados) || in_array($formatted_date, $evaluaciones)) {
        file_put_contents('debug_generate_plan.log', "Saltando fecha $formatted_date (feriado/evaluación)\n", FILE_APPEND);
        $current_date->modify('+1 day');
        continue;
    }

    // Traducir el día de la semana
    $day_of_week_es = $day_translation[$day_of_week] ?? $day_of_week;

    file_put_contents('debug_generate_plan.log', "Procesando fecha: $formatted_date ($day_of_week_es)\n", FILE_APPEND);

    // Verificar si el día está en los días seleccionados
    if (in_array($day_of_week_es, $dias_normalizados)) {
        // Obtener horas disponibles para este día
        $horas_disponibles_dia = isset($horas_limpias[$day_of_week_es]) ? $horas_limpias[$day_of_week_es] : 0;
        
        file_put_contents('debug_generate_plan.log', "Horas disponibles para $day_of_week_es: $horas_disponibles_dia\n", FILE_APPEND);

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

                    file_put_contents('debug_generate_plan.log', "Asignando $horas_a_asignar horas al tema: {$tema_actual['tema']}\n", FILE_APPEND);
                    file_put_contents('debug_generate_plan.log', "Horas restantes del tema: {$tema_actual['horas_restantes']}\n", FILE_APPEND);

                    // Si el tema se completó, agregarlo al array para insertar en BD
                    if ($tema_actual['horas_restantes'] <= 0) {
                        file_put_contents('debug_generate_plan.log', "Tema completado: {$tema_actual['tema']}\n", FILE_APPEND);
                        
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
    } else {
        file_put_contents('debug_generate_plan.log', "Día $day_of_week_es no está en días seleccionados\n", FILE_APPEND);
    }

    $current_date->modify('+1 day');
}

// Insertar los temas completados en la base de datos
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
        file_put_contents('debug_generate_plan.log', "Inserción exitosa en BD para tema ID: {$tema_bd['tema_id']}\n", FILE_APPEND);
    } else {
        file_put_contents('debug_generate_plan.log', "Error en inserción: " . $stmt_insert->error . "\n", FILE_APPEND);
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

// Depuración: registrar el plan generado
file_put_contents('debug_generate_plan.log', "Plan generado:\n", FILE_APPEND);
file_put_contents('debug_generate_plan.log', print_r($plan, true), FILE_APPEND);
file_put_contents('debug_generate_plan.log', "Total de temas en plan: " . count($plan) . "\n", FILE_APPEND);

// Verificar si se generó algún plan
if (empty($plan)) {
    file_put_contents('debug_generate_plan.log', "ERROR: No se pudo generar ningún plan\n", FILE_APPEND);
    die("Error: No se pudo generar el plan. Revise los parámetros de entrada.");
}

// Mostrar el plan
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'plan' => $plan,
    'total_temas' => count($plan),
    'temas_procesados' => $tema_index
], JSON_PRETTY_PRINT);
exit();
