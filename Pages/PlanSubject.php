<?php
session_start();
// Verificar si el usuario ha iniciado sesión
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
  
  <form id="programaForm" method="post" action="../Logic/SaveSubject.php">
    <label>Nombre de la Materia:</label>
    <input type="text" id="materiaNombre" name="materiaNombre" placeholder="Ej. Matemáticas" required />

    <label>Horas teóricas:</label>
    <input type="number" id="horasTeoricas" name="horasTeoricas" placeholder="Ej. 3" min="0" required />
    
    <label>Horas prácticas:</label>
    <input type="number" id="horasPracticas" name="horasPracticas" placeholder="Ej. 1" min="0" required />

    <div id="unidadesContainer"></div>

    <button type="button" id="btnAgregarUnidad">➕ Añadir Unidad</button> 

    <hr />
    <button type="submit" id="guardarTodo">💾 Guardar Todo</button>
  </form>

  <script>
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
      `;

      unidadesContainer.appendChild(unidadDiv);

      const btnTema = unidadDiv.querySelector(".btnAgregarTema");
      btnTema.addEventListener("click", (e) => agregarTema(e.target.dataset.unidad));
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
      `;
      temasContainer.appendChild(temaDiv);
    }

    programaForm.addEventListener("submit", function(e) {
      // Validación básica
      if (contadorUnidades === 0) {
        e.preventDefault();
        alert("⚠️ Debes agregar al menos una unidad.");
        return false;
      }
      
      // Verificar que cada unidad tenga al menos un tema
      const unidadDivs = document.querySelectorAll(".unidad-container");
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
