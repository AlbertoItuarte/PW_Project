<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de la Materia</title>
    <link rel="stylesheet" href="../CSS/Global.css">
    <style>
        /* Estilos aquí */
    </style>
</head>
<body>
    <div class="container">
        <div id="materia-header" class="header"></div>
        <div id="materia-info" class="info-section"></div>
        <h2>Programa de Estudio</h2>
        <div id="unidades-container"></div>
        <div class="actions">
            <a href="Home.php" class="btn btn-secondary">Volver al inicio</a>
        </div>
    </div>

    <script>
        const materiaId = new URLSearchParams(window.location.search).get('id');
        if (!materiaId) {
            alert("ID de materia no proporcionado.");
            window.location.href = "Home.php";
        }

        fetch(`../API/Subject/GetSubjectDetails.php?id=${materiaId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    window.location.href = "Home.php";
                    return;
                }

                // Mostrar información de la materia
                const header = document.getElementById('materia-header');
                header.innerHTML = `<h1>${data.materia.materia}</h1>`;

                const info = document.getElementById('materia-info');
                info.innerHTML = `
                    <table>
                        <tr><th>Horas teóricas</th><td>${data.totales.horas_teoricas} horas</td></tr>
                        <tr><th>Horas prácticas</th><td>${data.totales.horas_practicas} horas</td></tr>
                        <tr><th>Total de horas</th><td>${data.totales.horas_totales} horas</td></tr>
                        <tr><th>Total de horas asignadas a temas</th><td>${data.totales.horas_temas} horas</td></tr>
                    </table>
                `;

                // Mostrar unidades y temas
                const unidadesContainer = document.getElementById('unidades-container');
                if (data.unidades.length > 0) {
                    data.unidades.forEach((unidad, index) => {
                        let unidadHTML = `
                            <div class="unidad">
                                <h3>Unidad ${unidad.numero_unidad}: ${unidad.unidad}</h3>
                                <p>${unidad.descripcion || "Sin descripción"}</p>
                                <h4>Temas:</h4>
                        `;

                        if (unidad.temas.length > 0) {
                            unidad.temas.forEach((tema, temaIndex) => {
                                unidadHTML += `
                                    <div class="tema">
                                        <h5>${tema.orden_tema}. ${tema.tema}</h5>
                                        <p><strong>Horas estimadas:</strong> ${tema.horas_estimadas} horas</p>
                                    </div>
                                `;
                            });
                        } else {
                            unidadHTML += `<p>Esta unidad no tiene temas registrados.</p>`;
                        }

                        unidadHTML += `</div>`;
                        unidadesContainer.innerHTML += unidadHTML;
                    });
                } else {
                    unidadesContainer.innerHTML = `<p>Esta materia no tiene unidades registradas.</p>`;
                }
            })
            .catch(error => {
                console.error("Error al cargar los datos:", error);
                alert("Error al cargar los datos de la materia.");
                window.location.href = "Home.php";
            });
    </script>
</body>
</html>