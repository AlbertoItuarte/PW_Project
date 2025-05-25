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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planificar Materia</title>
</head>
<body>
    <h1>Planificar Materia</h1>
    <form action="../Logic/GeneratePlan.php" method="POST">
        <label for="materia_ciclo_id">Selecciona la Materia:</label>
        <select id="materia_ciclo_id" name="materia_ciclo_id" required>
            <?php foreach ($materias as $materia): ?>
                <option value="<?= $materia['materia_ciclo_id'] ?>"><?= htmlspecialchars($materia['materia']) ?></option>
            <?php endforeach; ?>
        </select>

        <h2>Días y Horas Disponibles</h2>
        <div>
            <label><input type="checkbox" name="dias[]" value="Lunes"> Lunes</label>
            <input type="number" name="horas[Lunes]" placeholder="Horas disponibles">
        </div>
        <div>
            <label><input type="checkbox" name="dias[]" value="Martes"> Martes</label>
            <input type="number" name="horas[Martes]" placeholder="Horas disponibles">
        </div>
        <div>
            <label><input type="checkbox" name="dias[]" value="Miércoles"> Miércoles</label>
            <input type="number" name="horas[Miércoles]" placeholder="Horas disponibles">
        </div>
        <div>
            <label><input type="checkbox" name="dias[]" value="Jueves"> Jueves</label>
            <input type="number" name="horas[Jueves]" placeholder="Horas disponibles">
        </div>
        <div>
            <label><input type="checkbox" name="dias[]" value="Viernes"> Viernes</label>
            <input type="number" name="horas[Viernes]" placeholder="Horas disponibles">
        </div>

        <h2>Fechas de Evaluación</h2>
        <div id="evaluaciones">
            <!-- Las fechas de evaluación se generarán dinámicamente -->
        </div>

        <button type="submit">Generar Plan</button>
    </form>

    <script>
        const feriados = <?= json_encode($feriados) ?>;
        const vacaciones = <?= json_encode($vacaciones) ?>;

        // Validar fechas de evaluación
        document.getElementById('evaluaciones').addEventListener('change', (event) => {
            const fecha = event.target.value;
            if (feriados.includes(fecha)) {
                alert('La fecha seleccionada es un día feriado. Por favor, selecciona otra fecha.');
                event.target.value = '';
            }
            vacaciones.forEach(vac => {
                if (fecha >= vac.inicio && fecha <= vac.fin) {
                    alert('La fecha seleccionada está dentro de un período de vacaciones. Por favor, selecciona otra fecha.');
                    event.target.value = '';
                }
            });
        });
    </script>
</body>
</html>