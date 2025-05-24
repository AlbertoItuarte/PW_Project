<?php
session_start();

// Verificar si el usuario ha iniciado sesión
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
    <title>Inicio</title>
    <link rel="stylesheet" href="../CSS/Global.css">
    <link rel="stylesheet" href="../CSS/Home.css">
    <style>
        /* Estilos para la ventana emergente */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .modal-content h3 {
            margin-bottom: 20px;
        }
        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .modal-buttons button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-confirm {
            background-color: #f44336;
            color: white;
        }
        .btn-cancel {
            background-color: #607d8b;
            color: white;
        }
    </style>
</head>
<body>
    <div>
        <nav>
            <ul>
                <li><a href="Home.php">Inicio</a></li>
                <li><a href="./PlanSubject.php">Crear materia</a></li>
                <li><a href="../Logic/LogOut.php">Cerrar sesión</a></li>
            </ul>
        </nav>
    </div>
    <div>
        <h1>Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
        <p>Has iniciado sesión correctamente.</p>
    </div>

    <div>
        <h2>Tus materias</h2>
        <div id="materias-container">
            <p>Cargando materias...</p>
        </div>
    </div>

    <!-- Ventana emergente -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <h3 id="modal-text"></h3>
            <div class="modal-buttons">
                <button id="btn-confirm" class="btn-confirm">Aceptar</button>
                <button id="btn-cancel" class="btn-cancel">Cancelar</button>
            </div>
        </div>
    </div>

    <script>
        // Detectar si la página fue cargada desde el historial del navegador
        window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            // Si la página fue cargada desde la caché, recargarla
            window.location.reload();
        }
        });
        // Obtener elementos del DOM
        const modal = document.getElementById('modal');
        const modalText = document.getElementById('modal-text');
        const btnConfirm = document.getElementById('btn-confirm');
        const btnCancel = document.getElementById('btn-cancel');
        let deleteUrl = '';

        // Manejar clic en "Eliminar"
        document.querySelectorAll('.btn-eliminar').forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                const materia = this.dataset.materia;
                const id = this.dataset.id;

                // Configurar texto y URL de eliminación
                modalText.textContent = `¿Seguro que desea eliminar la materia "${materia}"?`;
                deleteUrl = `../Logic/DeleteSubject.php?id=${id}`;

                // Mostrar ventana emergente
                modal.style.display = 'flex';
            });
        });

        // Confirmar eliminación
        btnConfirm.addEventListener('click', function () {
            window.location.href = deleteUrl;
        });

        // Cancelar eliminación
        btnCancel.addEventListener('click', function () {
            modal.style.display = 'none';
        });

        // Obtener el contenedor donde se mostrarán las materias
        const materiasContainer = document.getElementById('materias-container');

        // Realizar la solicitud a GetAdminSubjects.php
        fetch('../Logic/GetAdminSubjects.php')
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    let table = `
                        <table>
                            <thead>
                                <tr>
                                    <th>Materia</th>
                                    <th>Horas Teóricas</th>
                                    <th>Horas Prácticas</th>
                                    <th>Fecha de Creación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;

                    data.forEach(materia => {
                        table += `
                            <tr>
                                <td>${materia.materia}</td>
                                <td>${materia.horas_teoricas}</td>
                                <td>${materia.horas_practicas}</td>
                                <td>${materia.fecha_asignacion}</td>
                                <td class="actions">
                                    <a href="ViewSubject.php?id=${materia.materia_id}">Ver</a>
                                    <a href="#" class="btn-eliminar" data-id="${materia.materia_id}" data-materia="${materia.materia}">Eliminar</a>
                                    <a href="EditSubject.php?id=${materia.materia_id}">Editar</a>
                                </td>
                            </tr>
                        `;
                    });

                    table += '</tbody></table>';
                    materiasContainer.innerHTML = table;
                } else {
                    materiasContainer.innerHTML = `
                        <div class="no-materias">
                            <p>No tienes materias registradas aún.</p>
                            <p>Haz clic en "Crear materia" para empezar a organizar tu plan de estudios.</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error al cargar las materias:', error);
                materiasContainer.innerHTML = `
                    <div class="error">
                        <p>Error al cargar las materias. Intenta nuevamente más tarde.</p>
                    </div>
                `;
            });
    </script>
</body>
</html>