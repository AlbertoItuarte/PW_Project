<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

require_once '../Config/dbConection.php';

// Obtener el ID de la materia desde la URL
if (!isset($_GET['materia_ciclo_id']) || empty($_GET['materia_ciclo_id'])) {
    header("Location: HomeUser.php");
    exit();
}

$materia_ciclo_id = intval($_GET['materia_ciclo_id']);
$user_id = $_SESSION['user_id'];

// Verificar que la materia pertenece al usuario actual
$sql = "SELECT m.nombre AS materia
        FROM usuario_materia_ciclo umc
        INNER JOIN materia_ciclo mc ON umc.materia_ciclo_id = mc.materia_ciclo_id
        INNER JOIN materia m ON mc.materia_id = m.materia_id
        WHERE umc.usuario_id = ? AND umc.materia_ciclo_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id, $materia_ciclo_id]);

if ($stmt->rowCount() === 0) {
    header("Location: HomeUser.php");
    exit();
}

$materia_info = $stmt->fetch();

// Obtener las fechas del ciclo
$sql = "SELECT c.fecha_inicio, c.fecha_fin 
        FROM ciclo c 
        INNER JOIN materia_ciclo mc ON c.ciclo_id = mc.ciclo_id 
        WHERE mc.materia_ciclo_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$materia_ciclo_id]);
$ciclo_info = $stmt->fetch();

// Obtener las unidades para la materia seleccionada
$sql = "SELECT unidad_id, nombre, numero_unidad FROM unidad WHERE materia_ciclo_id = ? ORDER BY numero_unidad";
$stmt = $pdo->prepare($sql);
$stmt->execute([$materia_ciclo_id]);
$unidades = $stmt->fetchAll();

// Obtener los días feriados y vacaciones
$sql = "SELECT dia FROM feriados WHERE ciclo_id = (SELECT ciclo_id FROM materia_ciclo WHERE materia_ciclo_id = ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$materia_ciclo_id]);
$feriados_result = $stmt->fetchAll();
$feriados = [];
foreach ($feriados_result as $row) {
    $feriados[] = $row['dia'];
}

$sql = "SELECT fecha_inicio, fecha_fin FROM vacaciones WHERE ciclo_id = (SELECT ciclo_id FROM materia_ciclo WHERE materia_ciclo_id = ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$materia_ciclo_id]);
$vacaciones_result = $stmt->fetchAll();
$vacaciones = [];
foreach ($vacaciones_result as $row) {
    $vacaciones[] = ['inicio' => $row['fecha_inicio'], 'fin' => $row['fecha_fin']];
}

// Obtener mensajes de la sesión
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : null;
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : null;
$plan_generado = isset($_SESSION['plan_generado']) ? $_SESSION['plan_generado'] : null;

// Limpiar mensajes de la sesión
unset($_SESSION['error_message']);
unset($_SESSION['success_message']);
unset($_SESSION['plan_generado']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planificar Materia</title>
    <link rel="stylesheet" href="../CSS/Global.css">
    <link rel="stylesheet" href="../CSS/PlanificarMateria.css">
</head>
<body>
    <?php include '../Common/Header.html'; ?>
    
    <div class="materia-info">
        <h2>Planificar: <?= htmlspecialchars($materia_info['materia']) ?></h2>
        <p>Configure los días disponibles, horas por día y fechas de evaluación para generar la planificación.</p>
        <p><strong>Período del ciclo:</strong> <?= htmlspecialchars($ciclo_info['fecha_inicio']) ?> al <?= htmlspecialchars($ciclo_info['fecha_fin']) ?></p>
    </div>
    
    <!-- Mostrar mensajes -->
    <?php if ($error_message): ?>
        <div class="message error">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success_message): ?>
        <div class="message success">
            <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>

    <form action="../API/Plan/GeneratePlan.php" method="POST">
        <!-- Campo oculto para enviar el ID de la materia -->
        <input type="hidden" name="materia_ciclo_id" value="<?= $materia_ciclo_id ?>">

        <h2>Días y Horas Disponibles</h2>
        <div>
            <label><input type="checkbox" name="dias[]" value="Lunes"> Lunes</label>
            <input type="number" name="horas[Lunes]" placeholder="Horas disponibles" min="1">
        </div>
        <div>
            <label><input type="checkbox" name="dias[]" value="Martes"> Martes</label>
            <input type="number" name="horas[Martes]" placeholder="Horas disponibles" min="1">
        </div>
        <div>
            <label><input type="checkbox" name="dias[]" value="Miércoles"> Miércoles</label>
            <input type="number" name="horas[Miércoles]" placeholder="Horas disponibles" min="1">
        </div>
        <div>
            <label><input type="checkbox" name="dias[]" value="Jueves"> Jueves</label>
            <input type="number" name="horas[Jueves]" placeholder="Horas disponibles" min="1">
        </div>
        <div>
            <label><input type="checkbox" name="dias[]" value="Viernes"> Viernes</label>
            <input type="number" name="horas[Viernes]" placeholder="Horas disponibles" min="1">
        </div>
        
<input type="hidden" name="usuario_materia_ciclo_id" value="<?php echo htmlspecialchars($_GET['usuario_materia_ciclo_id']); ?>">
        <h2>Fechas de Evaluación por Unidad</h2>
        <div id="evaluaciones">
            <?php if (!empty($unidades)): ?>
                <?php foreach ($unidades as $unidad): ?>
                    <div class="evaluation-item">
                        <label for="evaluacion_<?= $unidad['unidad_id'] ?>">
                            Evaluación - <?= htmlspecialchars($unidad['nombre']) ?>:
                        </label>
                        <input type="date" 
                               id="evaluacion_<?= $unidad['unidad_id'] ?>" 
                               name="evaluaciones[<?= $unidad['unidad_id'] ?>]" 
                               class="fecha-evaluacion">
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No se encontraron unidades para esta materia. Asegúrate de haber creado la estructura de la materia primero.</p>
            <?php endif; ?>
        </div>

        <button type="submit">Generar Plan</button>
    </form>

    <!-- Mostrar el plan generado -->
    <?php if ($plan_generado): ?>
        <h2>Plan Generado</h2>
        <table class="plan-table">
            <thead>
                <tr>
                    <th>Unidad</th>
                    <th>Tema</th>
                    <th>Fecha Inicio</th>
                    <th>Fecha Fin</th>
                    <th>Horas Estimadas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plan_generado as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['unidad']) ?></td>
                        <td><?= htmlspecialchars($item['tema']) ?></td>
                        <td><?= htmlspecialchars($item['fecha_inicio']) ?></td>
                        <td><?= htmlspecialchars($item['fecha_fin']) ?></td>
                        <td><?= htmlspecialchars($item['horas_estimadas']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <script>
        const feriados = <?= json_encode($feriados) ?>;
        const vacaciones = <?= json_encode($vacaciones) ?>;
        const fechaInicioCiclo = '<?= $ciclo_info['fecha_inicio'] ?>';
        const fechaFinCiclo = '<?= $ciclo_info['fecha_fin'] ?>';

        // Función para activar validación de fechas
        function activarValidacionFechas() {
            document.querySelectorAll('.fecha-evaluacion').forEach(input => {
                // Configurar min y max para el input date
                input.setAttribute('min', fechaInicioCiclo);
                input.setAttribute('max', fechaFinCiclo);
                
                input.addEventListener('change', function() {
                    const fecha = this.value;
                    
                    // Validar que la fecha esté dentro del ciclo
                    if (fecha < fechaInicioCiclo || fecha > fechaFinCiclo) {
                        alert(`La fecha debe estar entre ${fechaInicioCiclo} y ${fechaFinCiclo} (período del ciclo).`);
                        this.value = '';
                        return;
                    }
                    
                    // Validar feriados
                    if (feriados.includes(fecha)) {
                        alert('La fecha seleccionada es un día feriado. Por favor, selecciona otra fecha.');
                        this.value = '';
                        return;
                    }
                    
                    // Validar vacaciones
                    let estaEnVacaciones = false;
                    vacaciones.forEach(vac => {
                        if (fecha >= vac.inicio && fecha <= vac.fin) {
                            estaEnVacaciones = true;
                        }
                    });
                    
                    if (estaEnVacaciones) {
                        alert('La fecha seleccionada está dentro de un período de vacaciones. Por favor, selecciona otra fecha.');
                        this.value = '';
                    }
                });
            });
        }

        // Activar validación al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            activarValidacionFechas();
        });
    </script>
</body>
</html>