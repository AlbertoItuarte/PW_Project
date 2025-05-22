<?php
session_start();
require_once '../Config/dbConection.php';

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

// Verificar si se proporcionó un ID de materia
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: Home.php");
    exit();
}

$programaId = intval($_GET['id']);
$userId = $_SESSION['user_id'];

// Verificar que la materia pertenezca al usuario actual
$sql = "SELECT p.*, pu.id as plan_id 
        FROM programa p
        INNER JOIN plan_usuario pu ON p.id = pu.programa_id
        WHERE p.id = ? AND pu.usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $programaId, $userId);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    // La materia no existe o no pertenece al usuario
    header("Location: Home.php");
    exit();
}

$programa = $resultado->fetch_assoc();
$planId = $programa['plan_id'];

// Obtener unidades
$unidades = [];
$sqlUnidades = "SELECT * FROM unidad WHERE programa_id = ? ORDER BY id";
$stmt = $conn->prepare($sqlUnidades);
$stmt->bind_param("i", $programaId);
$stmt->execute();
$resultadoUnidades = $stmt->get_result();

while ($unidad = $resultadoUnidades->fetch_assoc()) {
    // Obtener temas para cada unidad
    $temas = [];
    $sqlTemas = "SELECT t.*, tu.horas_estimadas 
                FROM tema t
                LEFT JOIN tema_usuario tu ON t.id = tu.tema_id AND tu.plan_id = ?
                WHERE t.unidad_id = ?
                ORDER BY t.id";
    $stmtTemas = $conn->prepare($sqlTemas);
    $stmtTemas->bind_param("ii", $planId, $unidad['id']);
    $stmtTemas->execute();
    $resultadoTemas = $stmtTemas->get_result();
    
    while ($tema = $resultadoTemas->fetch_assoc()) {
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Editar Materia</title>
  <link rel="stylesheet" href="../CSS/PlanSubject.css">
  <link rel="stylesheet" href="../CSS/Global.css">
</head>
<body>
  <nav>
    <ul>
      <li><a href="Home.php">Inicio</a></li>
      <li><a href="./PlanSubject.php">Crear materia</a></li>
      <li><a href="../Logic/LogOut.php">Cerrar sesión</a></li>
    </ul>
  </nav>

  <h2>Editar Materia</h2>
  
  <form id="programaForm" method="post" action="../Logic/UpdateSubject.php">
    <input type="hidden" name="programa_id" value="<?php echo $programaId; ?>">
    <input type="hidden" name="plan_id" value="<?php echo $planId; ?>">
    
    <label>Nombre de la Materia:</label>
    <input type="text" id="materiaNombre" name="materiaNombre" value="<?php echo htmlspecialchars($programa['materia']); ?>" required />

    <label>Horas teóricas:</label>
    <input type="number" id="horasTeoricas" name="horasTeoricas" value="<?php echo $programa['horas_teoricas']; ?>" min="0" required />
    
    <label>Horas prácticas:</label>
    <input type="number" id="horasPracticas" name="horasPracticas" value="<?php echo $programa['horas_practicas']; ?>" min="0" required />

    <div id="unidadesContainer">
      <?php foreach ($unidades as $index => $unidad): ?>
        <div class="unidad-container" data-id="<?php echo $unidad['id']; ?>">
          <input type="hidden" name="unidades[<?php echo $index; ?>][id]" value="<?php echo $unidad['id']; ?>">
          
          <h3>Unidad <?php echo $index + 1; ?></h3>
          <label>Nombre de la Unidad:</label>
          <input type="text" name="unidades[<?php echo $index; ?>][nombre]" class="unidad-nombre" value="<?php echo htmlspecialchars($unidad['nombre']); ?>" required />

          <div class="temas-container" id="temas-container-<?php echo $index; ?>">
            <?php foreach ($unidad['temas'] as $temaIndex => $tema): ?>
              <div class="tema-container" data-id="<?php echo $tema['id']; ?>">
                <input type="hidden" name="unidades[<?php echo $index; ?>][temas][<?php echo $temaIndex; ?>][id]" value="<?php echo $tema['id']; ?>">
                
                <h4>Tema <?php echo $temaIndex + 1; ?></h4>
                <label>Nombre del Tema:</label>
                <input type="text" name="unidades[<?php echo $index; ?>][temas][<?php echo $temaIndex; ?>][nombre]" class="tema-nombre" value="<?php echo htmlspecialchars($tema['nombre']); ?>" required />

                <label>Horas necesarias para cubrir el tema:</label>
                <input type="number" name="unidades[<?php echo $index; ?>][temas][<?php echo $temaIndex; ?>][horas]" class="tema-horas" value="<?php echo $tema['horas_estimadas']; ?>" min="1" required />
                
                <button type="button" class="btnEliminarTema" data-id="<?php echo $tema['id']; ?>">❌ Eliminar Tema</button>
              </div>
            <?php endforeach; ?>
          </div>

          <button type="button" class="btnAgregarTema" data-unidad="<?php echo $index; ?>">➕ Añadir Tema</button>
          <button type="button" class="btnEliminarUnidad" data-id="<?php echo $unidad['id']; ?>">❌ Eliminar Unidad</button>
        </div>
      <?php endforeach; ?>
    </div>

    <button type="button" id="btnAgregarUnidad">➕ Añadir Unidad</button> 

    <hr />
    <button type="submit" id="guardarTodo">💾 Guardar Cambios</button>
  </form>

  <script>
    let contadorUnidades = <?php echo count($unidades); ?>;

    const unidadesContainer = document.getElementById("unidadesContainer");
    const btnAgregarUnidad = document.getElementById("btnAgregarUnidad");
    const programaForm = document.getElementById("programaForm");

    // Agregar una nueva unidad
    btnAgregarUnidad.addEventListener("click", () => {
      const index = contadorUnidades;
      contadorUnidades++;
      
      const unidadDiv = document.createElement("div");
      unidadDiv.className = "unidad-container";
      unidadDiv.dataset.new = "true";

      unidadDiv.innerHTML = `
        <h3>Unidad ${contadorUnidades}</h3>
        <input type="hidden" name="unidades[${index}][new]" value="true">
        <label>Nombre de la Unidad:</label>
        <input type="text" name="unidades[${index}][nombre]" class="unidad-nombre" placeholder="Ej. Álgebra" required />

        <div class="temas-container" id="temas-container-${index}"></div>

        <button type="button" class="btnAgregarTema" data-unidad="${index}">➕ Añadir Tema</button>
        <button type="button" class="btnEliminarUnidad" data-new="true">❌ Eliminar Unidad</button>
      `;

      unidadesContainer.appendChild(unidadDiv);

      const btnTema = unidadDiv.querySelector(".btnAgregarTema");
      btnTema.addEventListener("click", () => agregarTema(index));
      
      const btnEliminar = unidadDiv.querySelector(".btnEliminarUnidad");
      btnEliminar.addEventListener("click", (e) => {
        unidadDiv.remove();
      });
    });

    // Agregar un nuevo tema a una unidad existente
    function agregarTema(unidadIndex) {
      const temasContainer = document.getElementById(`temas-container-${unidadIndex}`);
      const temaCount = temasContainer.childElementCount;
      
      const temaDiv = document.createElement("div");
      temaDiv.className = "tema-container";
      temaDiv.dataset.new = "true";
      
      temaDiv.innerHTML = `
        <input type="hidden" name="unidades[${unidadIndex}][temas][${temaCount}][new]" value="true">
        <h4>Tema ${temaCount + 1}</h4>
        <label>Nombre del Tema:</label>
        <input type="text" name="unidades[${unidadIndex}][temas][${temaCount}][nombre]" class="tema-nombre" placeholder="Ej. Ecuaciones cuadráticas" required />

        <label>Horas necesarias para cubrir el tema:</label>
        <input type="number" name="unidades[${unidadIndex}][temas][${temaCount}][horas]" class="tema-horas" placeholder="Ej. 2" min="1" required />
        
        <button type="button" class="btnEliminarTema" data-new="true">❌ Eliminar Tema</button>
      `;
      
      temasContainer.appendChild(temaDiv);
      
      const btnEliminar = temaDiv.querySelector(".btnEliminarTema");
      btnEliminar.addEventListener("click", () => {
        temaDiv.remove();
      });
    }

    // Agregar event listeners para los botones de eliminar existentes
    document.querySelectorAll('.btnEliminarTema').forEach(btn => {
      btn.addEventListener('click', function() {
        const temaContainer = this.closest('.tema-container');
        if (this.dataset.id) {
          // Si es un tema existente, agregar campo oculto para marcarlo como eliminado
          const unidadIndex = this.closest('.unidad-container').querySelector('.btnAgregarTema').dataset.unidad;
          const hiddenInput = document.createElement('input');
          hiddenInput.type = 'hidden';
          hiddenInput.name = 'temas_eliminar[]';
          hiddenInput.value = this.dataset.id;
          programaForm.appendChild(hiddenInput);
        }
        temaContainer.remove();
      });
    });

    document.querySelectorAll('.btnEliminarUnidad').forEach(btn => {
      btn.addEventListener('click', function() {
        const unidadContainer = this.closest('.unidad-container');
        if (this.dataset.id) {
          // Si es una unidad existente, agregar campo oculto para marcarla como eliminada
          const hiddenInput = document.createElement('input');
          hiddenInput.type = 'hidden';
          hiddenInput.name = 'unidades_eliminar[]';
          hiddenInput.value = this.dataset.id;
          programaForm.appendChild(hiddenInput);
        }
        unidadContainer.remove();
      });
    });

    // Agregar event listeners para botones de agregar tema existentes
    document.querySelectorAll('.btnAgregarTema').forEach(btn => {
      btn.addEventListener('click', function() {
        agregarTema(this.dataset.unidad);
      });
    });

    // Validación del formulario
    programaForm.addEventListener("submit", function(e) {
      // Verificar que haya al menos una unidad
      const unidadDivs = document.querySelectorAll(".unidad-container");
      if (unidadDivs.length === 0) {
        e.preventDefault();
        alert("⚠️ Debes tener al menos una unidad.");
        return false;
      }
      
      // Verificar que cada unidad tenga al menos un tema
      let valid = true;
      unidadDivs.forEach((unidadDiv, index) => {
        const temas = unidadDiv.querySelectorAll(".tema-container");
        if (temas.length === 0) {
          e.preventDefault();
          alert(`⚠️ La unidad ${index+1} debe tener al menos un tema.`);
          valid = false;
        }
      });
      
      return valid;
    });
  </script>
</body>
</html>