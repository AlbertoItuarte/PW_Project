
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

require_once '../Config/dbConection.php';

// Obtener el ID del usuario desde la sesión
$user_id = $_SESSION['user_id'];

// Obtener las materias asignadas al usuario
$sql = "SELECT umc.materia_ciclo_id, m.nombre AS materia
        FROM usuario_materia_ciclo umc
        INNER JOIN materia_ciclo mc ON umc.materia_ciclo_id = mc.materia_ciclo_id
        INNER JOIN materia m ON mc.materia_id = m.materia_id
        WHERE umc.usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$materias = $result->fetch_all(MYSQLI_ASSOC);

// Obtener las unidades para la primera materia (se actualizará dinámicamente)
$unidades = [];
if (!empty($materias)) {
    $sql = "SELECT unidad_id, nombre, numero_unidad FROM unidad WHERE materia_ciclo_id = ? ORDER BY numero_unidad";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $materias[0]['materia_ciclo_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $unidades = $result->fetch_all(MYSQLI_ASSOC);
}

// Obtener los días feriados y vacaciones
$sql = "SELECT dia FROM feriados WHERE ciclo_id = (SELECT ciclo_id FROM ciclo ORDER BY fecha_inicio DESC LIMIT 1)";
$feriados_result = $conn->query($sql);
$feriados = [];
while ($row = $feriados_result->fetch_assoc()) {
    $feriados[] = $row['dia'];
}

$sql = "SELECT fecha_inicio, fecha_fin FROM vacaciones WHERE ciclo_id = (SELECT ciclo_id FROM ciclo ORDER BY fecha_inicio DESC LIMIT 1)";
$vacaciones_result = $conn->query($sql);
$vacaciones = [];
while ($row = $vacaciones_result->fetch_assoc()) {
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
    <style>
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .plan-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .plan-table th, .plan-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .plan-table th {
            background-color: #f2f2f2;
        }
        .evaluation-item {
            margin: 10px 0;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .evaluation-item label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .evaluation-item input[type="date"] {
            width: 100%;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <h1>Planificar Materia</h1>
    
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

    <form action="../Logic/GeneratePlan.php" method="POST">
        <label for="materia_ciclo_id">Selecciona la Materia:</label>
        <select id="materia_ciclo_id" name="materia_ciclo_id" required onchange="cargarUnidades()">
            <?php foreach ($materias as $materia): ?>
                <option value="<?= $materia['materia_ciclo_id'] ?>"><?= htmlspecialchars($materia['materia']) ?></option>
            <?php endforeach; ?>
        </select>

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

        <h2>Fechas de Evaluación por Unidad</h2>
        <div id="evaluaciones">
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

        // Función para cargar unidades cuando cambia la materia
        function cargarUnidades() {
            const materiaCicloId = document.getElementById('materia_ciclo_id').value;
            
            fetch('../Logic/GetUnidades.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'materia_ciclo_id=' + materiaCicloId
            })
            .then(response => response.json())
            .then(data => {
                const evaluacionesDiv = document.getElementById('evaluaciones');
                evaluacionesDiv.innerHTML = '';
                
                data.forEach(unidad => {
                    const div = document.createElement('div');
                    div.className = 'evaluation-item';
                    div.innerHTML = `
                        <label for="evaluacion_${unidad.unidad_id}">
                            Evaluación - ${unidad.nombre}:
                        </label>
                        <input type="date" 
                               id="evaluacion_${unidad.unidad_id}" 
                               name="evaluaciones[${unidad.unidad_id}]" 
                               class="fecha-evaluacion">
                    `;
                    evaluacionesDiv.appendChild(div);
                });
                
                // Reactivar validación de fechas
                activarValidacionFechas();
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        // Función para activar validación de fechas
        function activarValidacionFechas() {
            document.querySelectorAll('.fecha-evaluacion').forEach(input => {
                input.addEventListener('change', function() {
                    const fecha = this.value;
                    if (feriados.includes(fecha)) {
                        alert('La fecha seleccionada es un día feriado. Por favor, selecciona otra fecha.');
                        this.value = '';
                        return;
                    }
                    
                    vacaciones.forEach(vac => {
                        if (fecha >= vac.inicio && fecha <= vac.fin) {
                            alert('La fecha seleccionada está dentro de un período de vacaciones. Por favor, selecciona otra fecha.');
                            this.value = '';
                        }
                    });
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