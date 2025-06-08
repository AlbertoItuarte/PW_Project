<?php
session_start();
require_once '../Config/dbConection.php';

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

if($_SESSION['user_type'] != "Admin") {
    header("Location: Home.php");
    exit();
}

// Verificar si se proporcionó un ID de materia
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: Home.php");
    exit();
}

$materiaId = intval($_GET['id']);
$userId = $_SESSION['user_id'];

// Verificar que la materia pertenezca al usuario actual (PDO/PostgreSQL)
$sql = "SELECT m.*, mc.materia_ciclo_id, mc.horas_teoricas, mc.horas_practicas
        FROM materia m
        INNER JOIN materia_ciclo mc ON m.materia_id = mc.materia_id
        WHERE m.materia_id = ? AND mc.usuario_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$materiaId, $userId]);

if ($stmt->rowCount() === 0) {
    // La materia no existe o no pertenece al usuario
    header("Location: Home.php");
    exit();
}

$materia = $stmt->fetch();
$materiaCicloId = $materia['materia_ciclo_id'];

// Obtener unidades
$unidades = [];
$sqlUnidades = "SELECT * FROM unidad WHERE materia_ciclo_id = ? ORDER BY numero_unidad";
$stmt = $pdo->prepare($sqlUnidades);
$stmt->execute([$materiaCicloId]);

while ($unidad = $stmt->fetch()) {
    // Obtener temas para cada unidad
    $temas = [];
    $sqlTemas = "SELECT * FROM tema WHERE unidad_id = ? ORDER BY orden_tema";
    $stmtTemas = $pdo->prepare($sqlTemas);
    $stmtTemas->execute([$unidad['unidad_id']]);
    
    while ($tema = $stmtTemas->fetch()) {
        $temas[] = $tema;
    }
    
    $unidad['temas'] = $temas;
    $unidades[] = $unidad;
}

// Mostrar mensajes de éxito o error
if (isset($_GET['success'])) {
    echo '<div class="alert alert-success">¡Materia actualizada correctamente!</div>';
}
if (isset($_GET['error'])) {
    echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($_GET['error']) . '</div>';
}

// Guardar cambios de materia y unidades
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->beginTransaction();
    try {
        // Actualizar información de la materia
        $nombreMateria = $_POST['materiaNombre'];
        $horasTeoricas = floatval($_POST['horasTeoricas']);
        $horasPracticas = floatval($_POST['horasPracticas']);

        // Actualizar materia
        $sqlMateria = "UPDATE materia SET nombre = ? WHERE materia_id = ?";
        $stmtMateria = $pdo->prepare($sqlMateria);
        $stmtMateria->execute([$nombreMateria, $materiaId]);

        // Actualizar materia_ciclo con las horas
        $sqlMateriaCiclo = "UPDATE materia_ciclo SET horas_teoricas = ?, horas_practicas = ? WHERE materia_ciclo_id = ?";
        $stmtMateriaCiclo = $pdo->prepare($sqlMateriaCiclo);
        $stmtMateriaCiclo->execute([$horasTeoricas, $horasPracticas, $materiaCicloId]);

        // Procesar unidades y temas
        if (isset($_POST['unidades']) && is_array($_POST['unidades'])) {
            foreach ($_POST['unidades'] as $unidadData) {
                if (empty($unidadData['nombre'])) continue;

                // Validar que numero_unidad sea mayor que 0
                if (!isset($unidadData['numero_unidad']) || intval($unidadData['numero_unidad']) <= 0) {
                    $pdo->rollback();
                    header("Location: EditSubject.php?id={$materiaId}&error=" . urlencode("El número de unidad debe ser mayor que 0."));
                    exit();
                }

                $numeroUnidad = intval($unidadData['numero_unidad']);
                $nombreUnidad = $unidadData['nombre'];

                // Determinar si es una nueva unidad o una existente
                if (isset($unidadData['id']) && intval($unidadData['id']) > 0) {
                    // Actualizar unidad existente
                    $unidadId = intval($unidadData['id']);
                    $sql = "UPDATE unidad SET nombre = ?, numero_unidad = ? WHERE unidad_id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$nombreUnidad, $numeroUnidad, $unidadId]);
                } else {
                    // Insertar nueva unidad
                    $sql = "INSERT INTO unidad (materia_ciclo_id, nombre, numero_unidad, fecha_creacion) VALUES (?, ?, ?, CURRENT_TIMESTAMP) RETURNING unidad_id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$materiaCicloId, $nombreUnidad, $numeroUnidad]);
                    $result = $stmt->fetch();
                    $unidadId = $result['unidad_id'];
                }

                // Procesar temas de la unidad
                if (isset($unidadData['temas']) && is_array($unidadData['temas'])) {
                    foreach ($unidadData['temas'] as $temaIndex => $temaData) {
                        if (empty($temaData['nombre'])) continue;

                        $nombreTema = $temaData['nombre'];
                        $horasEstimadas = floatval($temaData['horas']);
                        $ordenTema = $temaIndex + 1;

                        // Determinar si es un nuevo tema o uno existente
                        if (isset($temaData['id']) && intval($temaData['id']) > 0) {
                            // Actualizar tema existente
                            $temaId = intval($temaData['id']);
                            $sqlTema = "UPDATE tema SET nombre = ?, horas_estimadas = ?, orden_tema = ? WHERE tema_id = ?";
                            $stmtTema = $pdo->prepare($sqlTema);
                            $stmtTema->execute([$nombreTema, $horasEstimadas, $ordenTema, $temaId]);
                        } else {
                            // Insertar nuevo tema
                            $sqlTema = "INSERT INTO tema (nombre, orden_tema, horas_estimadas, unidad_id, fecha_creacion) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)";
                            $stmtTema = $pdo->prepare($sqlTema);
                            $stmtTema->execute([$nombreTema, $ordenTema, $horasEstimadas, $unidadId]);
                        }
                    }
                }
            }
        }

        $pdo->commit();
        header("Location: EditSubject.php?id={$materiaId}&success=1");
        exit();
    } catch (Exception $e) {
        $pdo->rollback();
        header("Location: EditSubject.php?id={$materiaId}&error=" . urlencode("Error al actualizar la materia: " . $e->getMessage()));
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Editar Materia</title>
  <link rel="stylesheet" href="../CSS/PlanSubject.css">
  <link rel="stylesheet" href="../CSS/Global.css">
  <link rel="stylesheet" href="../CSS/EditSubject.css">
</head>
<body>
   <div>
        <nav>
            <ul>
                <li><a href="Home.php">Inicio</a></li>
                <li><a href="PlanSubject.php" id="btn-create-materia">Crear materia</a></li>
                <li><a href="CreateCycle.php" id="btn-create-ciclo">Gestionar Ciclo</a></li>
                <li><a href="ViewUsers.php">Usuarios</a></li>
                <li><a href="../Logic/LogOut.php">Cerrar sesión</a></li>

            </ul>
        </nav>
    </div>

  <h2>Editar Materia</h2>
  
  <form id="materiaForm" method="post">
    <input type="hidden" name="materia_id" value="<?php echo $materiaId; ?>">
    <input type="hidden" name="materia_ciclo_id" value="<?php echo $materiaCicloId; ?>">

    <div class="form-row">
      <div>
        <label>Nombre de la Materia:</label>
        <input type="text" id="materiaNombre" name="materiaNombre" value="<?php echo htmlspecialchars($materia['nombre']); ?>" required />
      </div>
      <div>
        <label>Horas teóricas:</label>
        <input type="number" id="horasTeoricas" name="horasTeoricas" value="<?php echo $materia['horas_teoricas']; ?>" min="0" step="0.5" required />
      </div>
      <div>
        <label>Horas prácticas:</label>
        <input type="number" id="horasPracticas" name="horasPracticas" value="<?php echo $materia['horas_practicas']; ?>" min="0" step="0.5" required />
      </div>
    </div>

    <div id="unidadesContainer">
      <?php foreach ($unidades as $index => $unidad): ?>
        <div class="unidad-container" data-id="<?php echo $unidad['unidad_id']; ?>">
          <input type="hidden" name="unidades[<?php echo $index; ?>][id]" value="<?php echo $unidad['unidad_id']; ?>">
          
          <div class="unidad-header" tabindex="0">
            <span>Unidad <?php echo $index + 1; ?>: <?php echo htmlspecialchars($unidad['nombre']); ?></span>
            <span class="unidad-toggle">▼</span>
          </div>
          <div class="unidad-content">
            <label>Número de Unidad:</label>
            <input type="number" name="unidades[<?php echo $index; ?>][numero_unidad]" class="unidad-numero" value="<?php echo $unidad['numero_unidad']; ?>" min="1" required />

            <label>Nombre de la Unidad:</label>
            <input type="text" name="unidades[<?php echo $index; ?>][nombre]" class="unidad-nombre" value="<?php echo htmlspecialchars($unidad['nombre']); ?>" required />

            <div class="temas-container" id="temas-container-<?php echo $index; ?>">
              <?php foreach ($unidad['temas'] as $temaIndex => $tema): ?>
                <div class="tema-container" data-id="<?php echo $tema['tema_id']; ?>">
                  <input type="hidden" name="unidades[<?php echo $index; ?>][temas][<?php echo $temaIndex; ?>][id]" value="<?php echo $tema['tema_id']; ?>">
                  
                  <h4>Tema <?php echo $temaIndex + 1; ?></h4>
                  <label>Nombre del Tema:</label>
                  <input type="text" name="unidades[<?php echo $index; ?>][temas][<?php echo $temaIndex; ?>][nombre]" class="tema-nombre" value="<?php echo htmlspecialchars($tema['nombre']); ?>" required />

                  <label>Horas necesarias para cubrir el tema:</label>
                  <input type="number" name="unidades[<?php echo $index; ?>][temas][<?php echo $temaIndex; ?>][horas]" class="tema-horas" value="<?php echo $tema['horas_estimadas']; ?>" min="0.5" step="0.5" required />
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <button type="submit" id="guardarTodo">💾 Guardar Cambios</button>
  </form>
  <script>
    document.querySelectorAll('.unidad-header').forEach(header => {
      header.addEventListener('click', function() {
        const container = this.parentElement;
        container.classList.toggle('active');
      });
      header.addEventListener('keypress', function(e) {
        if (e.key === "Enter" || e.key === " ") {
          e.preventDefault();
          this.click();
        }
      });
    });
  </script>
</body>
</html>