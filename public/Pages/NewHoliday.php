<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../CSS/Global.css">
    <link rel="stylesheet" href="../CSS/NewHoliday.css">
    <title>Agregar Feriado</title>
</head>
<body>
    <?php include '../Common/Header.html'; ?>
    <h1>Agregar un Feriado</h1>
    <form action="../API/Holidays/InsertHoliday.php" method="POST">
        <select name="ciclo_id" id="ciclo">
            <option value="default">Selecciona una opción</option>
            <?php
            require_once '../Config/dbConection.php';
            try {
                $query = "SELECT id, nombre FROM ciclos ORDER BY nombre";
                $stmt = $pdo->prepare($query);
                $stmt->execute();
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<option value='" . htmlspecialchars($row['id']) . "'>" . 
                         htmlspecialchars($row['nombre']) . "</option>";
                }
            } catch (PDOException $e) {
                echo "<option value=''>Error al cargar datos</option>";
                error_log("Error en NewHoliday.php: " . $e->getMessage());
            } catch (Exception $e) {
                echo "<option value=''>Error de conexión</option>";
                error_log("Error de conexión en NewHoliday.php: " . $e->getMessage());
            }
            ?>
        </select>

        <div class="toggle-radio-group">
            <div class="toggle-radio">
                <input type="radio" name="tipo_feriado" id="feriado_unico" value="unico" checked onclick="toggleRange(false)">
                <label for="feriado_unico">Feriado único</label>
            </div>
            <div class="toggle-radio">
                <input type="radio" name="tipo_feriado" id="feriado_rango" value="rango" onclick="toggleRange(true)">
                <label for="feriado_rango">Rango de fechas (vacaciones, etc)</label>
            </div>
        </div>

        <div id="feriado-unico">
            <input type="date" name="dia" id="dia" required>
        </div>
        <div id="feriado-rango" style="display:none;">
            <input type="date" name="fecha_inicio" id="fecha_inicio">
            <span style="margin: 0 8px;">a</span>
            <input type="date" name="fecha_fin" id="fecha_fin">
        </div>

        <input type="text" name="causa" id="causa" placeholder="Causa del feriado" required>
        <input type="submit" value="Crear Feriado">
    </form>
    <script>
        function toggleRange(showRange) {
            document.getElementById('feriado-unico').style.display = showRange ? 'none' : 'block';
            document.getElementById('feriado-rango').style.display = showRange ? 'block' : 'none';
            // Required attributes
            document.getElementById('dia').required = !showRange;
            document.getElementById('fecha_inicio').required = showRange;
            document.getElementById('fecha_fin').required = showRange;
        }
    </script>
</body>
</html>