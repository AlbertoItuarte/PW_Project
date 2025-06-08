<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../CSS/Global.css">
    <link rel="stylesheet" href="../CSS/SelectSubject.css">
    <title>Seleccionar Materias</title>
</head>
<body>
    <?php include '../Common/Header.html'; ?>
    <h1>Seleccionar Materias</h1>
    
    <div id="mensaje-resultado" style="display: none;"></div>
    
    <form id="form-materias">
        <div class="materias-container">
        <?php
        require_once '../Config/dbConection.php';

        // Obtener el ID del usuario desde la sesión
        $user_id = $_SESSION['user_id'];

        // Consultar todas las materias disponibles
        $sql = "SELECT mc.materia_ciclo_id, m.nombre AS materia, m.codigo,
                       (SELECT COUNT(*) FROM usuario_materia_ciclo umc 
                        WHERE umc.materia_ciclo_id = mc.materia_ciclo_id 
                        AND umc.usuario_id = ?) AS veces_asignada
                FROM materia_ciclo mc
                INNER JOIN materia m ON mc.materia_id = m.materia_id
                ORDER BY m.nombre ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);

        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch()) {
                $veces_texto = $row['veces_asignada'] > 0 ? "Asignada {$row['veces_asignada']} vez(es)" : "Disponible";
                $estado_class = $row['veces_asignada'] > 0 ? 'asignada' : 'disponible';
                
                echo "<div class='materia-item {$estado_class}'>
                        <label>
                            <span class='materia-info'>
                                <span class='materia-nombre'>{$row['materia']}</span>
                                <span class='materia-codigo'>({$row['codigo']})</span>
                                <span class='materia-estado'>{$veces_texto}</span>
                            </span>
                        </label>
                        <input type='checkbox' name='materias[]' value='{$row['materia_ciclo_id']}'>
                    </div>";
            }
        } else {
            echo "<p>No hay materias disponibles para selección.</p>";
        }
        ?>
        </div>
        <button type="submit">Cargar Materias</button>
    </form>

    <script>
        document.getElementById('form-materias').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const checkboxes = document.querySelectorAll('input[name="materias[]"]:checked');
            const materias = Array.from(checkboxes).map(cb => cb.value);
            
            if (materias.length === 0) {
                mostrarMensaje('Por favor selecciona al menos una materia.', 'error');
                return;
            }
            
            try {
                const response = await fetch('../API/Subject/AsignSubject.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        usuario_id: <?php echo $_SESSION['user_id']; ?>,
                        materias: materias
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    mostrarMensaje(data.message, 'success');
                    // Recargar la página después de 2 segundos
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    mostrarMensaje(data.message, 'error');
                }
                
            } catch (error) {
                mostrarMensaje('Error al procesar la solicitud: ' + error.message, 'error');
            }
        });
        
        function mostrarMensaje(mensaje, tipo) {
            const div = document.getElementById('mensaje-resultado');
            div.innerHTML = mensaje;
            div.className = `alert alert-${tipo}`;
            div.style.display = 'block';
            
            // Scroll al mensaje
            div.scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>