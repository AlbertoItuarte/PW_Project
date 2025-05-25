<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
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
    <label>Nombre de la Materia:</label>
    <input type="text" id="materiaNombre" name="nombreMateria" placeholder="Ej. Matemáticas" required />

    <label>Código de la materia:</label>
    <input type="text" id="codigoMateria" name="codigoMateria" placeholder="Ej. MAT101" required />

    <label>Descripción de la materia:</label>
    <textarea id="descripcionMateria" name="descripcionMateria" rows="4" placeholder="Descripción de la materia..." required></textarea>

    <label>Horas Necesarias:</label>
    <input type="number" id="horasTeoricas" name="horasTeoricas" placeholder="Ej. 3" min="0" required />
    
    <div id="unidadesContainer"></div>

    <button type="button" id="btnAgregarUnidad">➕ Añadir Unidad</button> 

    <hr />
    <button type="submit" id="guardarTodo">💾 Guardar Todo</button>
  </form>

  <script>
      if (window.location.search.includes("success=1")) {
    // Limpiar los campos del formulario
    document.getElementById("programaForm").reset();

    // Opcional: Mostrar un mensaje de éxito (si no se muestra ya en PHP)
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

      unidadDiv.innerHTML = `
        <h3>Unidad ${contadorUnidades}</h3>
        <label>Nombre de la Unidad:</label>
        <input type="text" name="unidades[${contadorUnidades}][nombre]" class="unidad-nombre" placeholder="Ej. Álgebra" required />

        <div class="temas-container" id="temas-container-${contadorUnidades}"></div>

        <button type="button" class="btnAgregarTema" data-unidad="${contadorUnidades}">➕ Añadir Tema</button>
        <button type="button" id="btnEliminarUnidad" class="btnEliminarUnidad">✘ Eliminar Unidad</button>
        <hr />
      `;

      unidadesContainer.appendChild(unidadDiv);

      const btnTema = unidadDiv.querySelector(".btnAgregarTema");
      btnTema.addEventListener("click", (e) => agregarTema(e.target.dataset.unidad));

      const btnEliminarUnidad = unidadDiv.querySelector(".btnEliminarUnidad");
      btnEliminarUnidad.addEventListener("click", () => {
        unidadDiv.remove();
      });
    });

    function agregarTema(unidadId) {
      const temasContainer = document.getElementById(`temas-container-${unidadId}`);
      const temaCount = temasContainer.childElementCount + 1;
      
      const temaDiv = document.createElement("div");
      temaDiv.className = "tema-container";
      temaDiv.innerHTML = `
        <h4>Tema ${temaCount}</h4>
        <label>Nombre del Tema:</label>
        <input type="text" name="unidades[${unidadId}][temas][${temaCount}][nombre]" class="tema-nombre" placeholder="Ej. Ecuaciones cuadráticas" required />

        <label>Horas necesarias para cubrir el tema:</label>
        <input type="number" name="unidades[${unidadId}][temas][${temaCount}][horas]" class="tema-horas" placeholder="Ej. 2" min="1" required />

        <button type="button" id="btnEliminarTema" class="btnEliminarTema">✘ Eliminar Tema</button>
      `;

      temasContainer.appendChild(temaDiv);

      const btnEliminarTema = temaDiv.querySelector(".btnEliminarTema");
      btnEliminarTema.addEventListener("click", () => {
        temaDiv.remove();
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
