<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form action="../API/Holidays/InsertHoliday.php" method="POST">
        <select name="ciclo_id" id="ciclo">
            <option value="default">Selecciona una opción</option>
            <?php
            require_once '../Config/dbConection.php';
            
            try {
                // Asumiendo que tienes una tabla 'ciclos' o similar
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
        <input type="date" name="dia" id="dia" required>
        <input type="text" name="causa" id="causa" placeholder="Causa del feriado" required>
        <input type="submit" value="Crear Feriado">
    </form>
</body>
</html>