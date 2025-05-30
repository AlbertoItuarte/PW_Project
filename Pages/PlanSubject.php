<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}
if ($_SESSION['user_type'] != "Admin") {
    header("Location: HomeUser.php");
    exit();
}

if (isset($_GET['success'])) {
    echo '<div class="alert alert-success">¡Materia guardada correctamente!</div>';
}
if (isset($_GET['error'])) {
    echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($_GET['error']) . '</div>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Planificación de Materias</title>
  <link rel="stylesheet" href="../CSS/PlanSubject.css">
  <link rel="stylesheet" href="../CSS/Global.css">
</head>
<body>
  <h2>Crear Materia</h2>
  
  <form id="programaForm" method="post" action="../API/Subject/SaveSubject.php">

    <label>Selecciona el Ciclo:</label>
    <select id="cicloSelect" name="ciclo" required>
      <?php
      require_once '../Config/dbConection.php';
      $sql = "SELECT ciclo_id, nombre FROM ciclo";
      $result = $conn->query($sql);
      if ($result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {
              echo '<option value="' . htmlspecialchars($row['ciclo_id']) . '">' . htmlspecialchars($row['nombre']) . '</option>';
          }
      } else {
          echo '<option value="">No hay ciclos disponibles</option>';
      }
      ?>
    </select>

    <label>Nombre de la Materia:</label>
    <input type="text" id="materiaNombre" name="nombreMateria" placeholder="Ej. Matemáticas" required />

    <label>Código de la materia:</label>
    <input type="text" id="codigoMateria" name="codigoMateria" placeholder="Ej. MAT101" required />

    <label>Descripción de la materia:</label>
    <textarea id="descripcionMateria" name="descripcionMateria" rows="4" placeholder="Descripción de la materia..." required></textarea>

    <label>Horas Teóricas:</label>
    <input type="number" id="horasTeoricas" name="horasTeoricas" placeholder="Ej. 3" min="0" required />

    <label>Horas Prácticas:</label>
    <input type="number" id="horasPracticas" name="horasPracticas" placeholder="Ej. 2" min="0" required />
    
    <div id="unidadesContainer"></div>

    <button type="button" id="btnAgregarUnidad">➕ Añadir Unidad</button> 

    <hr />
    <button type="submit" id="guardarTodo">💾 Guardar Todo</button>
  </form>

  <script>
    if (window.location.search.includes("success=1")) {
      document.getElementById("programaForm").reset();
      document.getElementById("unidadesContainer").innerHTML = "";
      contadorUnidades = 0;
      console.log("Formulario enviado correctamente. Campos limpiados.");
    }

    let contadorUnidades = 0;

    const unidadesContainer = document.getElementById("unidadesContainer");
    const btnAgregarUnidad = document.getElementById("btnAgregarUnidad");
    const programaForm = document.getElementById("programaForm");

    btnAgregarUnidad.addEventListener("click", () => {
      contadorUnidades++;
      const unidadDiv = document.createElement("div");
      unidadDiv.className = "unidad-container";
      unidadDiv.setAttribute("data-unidad-id", contadorUnidades);

      unidadDiv.innerHTML = `
        <h3>Unidad ${contadorUnidades}</h3>
        <label>Nombre de la Unidad:</label>
        <input type="text" name="unidades[${contadorUnidades}][nombre]" class="unidad-nombre" placeholder="Ej. Álgebra" required />

        <div class="temas-container" id="temas-container-${contadorUnidades}"></div>

        <button type="button" class="btnAgregarTema" data-unidad="${contadorUnidades}">➕ Añadir Tema</button>
        <button type="button" class="btnEliminarUnidad">✘ Eliminar Unidad</button>
        <hr />
      `;

      unidadesContainer.appendChild(unidadDiv);

      const btnTema = unidadDiv.querySelector(".btnAgregarTema");
      btnTema.addEventListener("click", (e) => agregarTema(e.target.dataset.unidad));

      const btnEliminarUnidad = unidadDiv.querySelector(".btnEliminarUnidad");
      btnEliminarUnidad.addEventListener("click", () => {
        unidadDiv.remove();
        reindexarUnidades();
      });
    });

    function agregarTema(unidadId) {
      const temasContainer = document.getElementById(`temas-container-${unidadId}`);
      const temaCount = temasContainer.children.length;
      
      const temaDiv = document.createElement("div");
      temaDiv.className = "tema-container";
      temaDiv.innerHTML = `
        <h4>Tema ${temaCount + 1}</h4>
        <label>Nombre del Tema:</label>
        <input type="text" name="unidades[${unidadId}][temas][${temaCount}][nombre]" class="tema-nombre" placeholder="Ej. Ecuaciones cuadráticas" required />

        <label>Horas necesarias para cubrir el tema:</label>
        <input type="number" name="unidades[${unidadId}][temas][${temaCount}][horas]" class="tema-horas" placeholder="Ej. 2" min="1" step="0.5" required />

        <button type="button" class="btnEliminarTema">✘ Eliminar Tema</button>
      `;

      temasContainer.appendChild(temaDiv);

      const btnEliminarTema = temaDiv.querySelector(".btnEliminarTema");
      btnEliminarTema.addEventListener("click", () => {
        temaDiv.remove();
        reindexarTemas(unidadId);
      });
    }

    function reindexarUnidades() {
      const unidadDivs = document.querySelectorAll(".unidad-container");
      unidadDivs.forEach((unidadDiv, index) => {
        const nuevoIndex = index + 1;
        unidadDiv.setAttribute("data-unidad-id", nuevoIndex);
        
        // Actualizar el título
        unidadDiv.querySelector("h3").textContent = `Unidad ${nuevoIndex}`;
        
        // Actualizar los names de los inputs de la unidad
        const inputNombre = unidadDiv.querySelector(".unidad-nombre");
        inputNombre.name = `unidades[${nuevoIndex}][nombre]`;
        
        // Actualizar el botón de agregar tema
        const btnAgregarTema = unidadDiv.querySelector(".btnAgregarTema");
        btnAgregarTema.setAttribute("data-unidad", nuevoIndex);
        
        // Actualizar el ID del contenedor de temas
        const temasContainer = unidadDiv.querySelector(".temas-container");
        temasContainer.id = `temas-container-${nuevoIndex}`;
        
        // Reindexar todos los temas de esta unidad
        reindexarTemas(nuevoIndex);
      });
      
      contadorUnidades = unidadDivs.length;
    }

    function reindexarTemas(unidadId) {
      const temasContainer = document.getElementById(`temas-container-${unidadId}`);
      const temaDivs = temasContainer.querySelectorAll(".tema-container");
      
      temaDivs.forEach((temaDiv, index) => {
        // Actualizar el título del tema
        temaDiv.querySelector("h4").textContent = `Tema ${index + 1}`;
        
        // Actualizar los names de los inputs del tema
        const inputNombre = temaDiv.querySelector(".tema-nombre");
        const inputHoras = temaDiv.querySelector(".tema-horas");
        
        inputNombre.name = `unidades[${unidadId}][temas][${index}][nombre]`;
        inputHoras.name = `unidades[${unidadId}][temas][${index}][horas]`;
      });
    }

    programaForm.addEventListener("submit", function(e) {
      const unidadDivs = document.querySelectorAll(".unidad-container");
      if (unidadDivs.length === 0) {
        e.preventDefault();
        alert("⚠️ Debes agregar al menos una unidad.");
        return false;
      }

      for (let i = 0; i < unidadDivs.length; i++) {
        const temas = unidadDivs[i].querySelectorAll(".tema-container");
        if (temas.length === 0) {
          e.preventDefault();
          alert(`⚠️ La unidad ${i+1} debe tener al menos un tema.`);
          return false;
        }
      }

      return true;
    });
  </script>
</body>
</html>