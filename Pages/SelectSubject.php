<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccionar Materias</title>
</head>
<body>
    <h1>Seleccionar Materias</h1>
    <form action="../Logic/AsignSubject.php" method="POST">
        <?php
        require_once '../Config/dbConection.php';

        // Consultar todas las materias disponibles
        $sql = "SELECT id, materia FROM programa";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<div>
                        <input type='checkbox' name='materias[]' value='{$row['id']}'>
                        <label>{$row['materia']}</label>
                      </div>";
            }
        } else {
            echo "<p>No hay materias disponibles.</p>";
        }
        ?>
        <button type="submit">Cargar Materias</button>
    </form>
</body>
</html>