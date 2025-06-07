<?php
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

// Verificar si el usuario es administrador
if ($_SESSION['user_type'] != "Admin") {
    header("Location: HomeUser.php");
    exit();
}

// Conectar a la base de datos
require_once '../Config/dbConection.php';

// Verificar si existen ciclos
$sql = "SELECT COUNT(*) AS total FROM ciclo";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$row = $stmt->fetch();

if ($row['total'] == 0) {
    // Redirigir al administrador a la página de gestión de ciclos
    header("Location: ManageCycles.php");
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
    <link rel="stylesheet" href="../CSS/VentanaEmergente.css">
</head>
<body>
    <div>
        <nav>
            <ul>
                <li><a href="Home.php">Inicio</a></li>
                <li><a href="PlanSubject.php" id="btn-create-materia">Crear materia</a></li>
                <li><a href="CreateCycle.php" id="btn-create-ciclo">Crear Ciclo</a></li>
                <li><a href="NewHoliday.php">Feriados +</a></li>
                <li><a href="Register.php">Registrar usuarios</a></li>
                <li><a href="ViewUsers.php">Ver usuarios</a></li>
                <li><a href="../Logic/LogOut.php">Cerrar sesión</a></li>

            </ul>
        </nav>
    </div>
    <div>
        <h1>Bienvenido, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h1>

        <h2>Tus materias</h2>

        <div id="materias-container">
            <p>Cargando materias...</p>
        </div>
    </div>
    <div id="modal-eliminar" class="modal" role="dialog" aria-labelledby="modal-title" aria-modal="true">
        <div class="modal-content">
            <h3 id="modal-title">¿Estás seguro de que deseas eliminar esta materia?</h3>
            <p id="modal-materia-info"></p> 
            <p>Esta acción no se puede deshacer.</p>
            <div class="modal-buttons">
                <button id="btn-confirmar-eliminar" class="btn-confirm">Aceptar</button>
                <button id="btn-cancelar-eliminar" class="btn-cancel">Cancelar</button>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('modal-eliminar');
        const btnConfirmar = document.getElementById('btn-confirmar-eliminar');
        const btnCancelar = document.getElementById('btn-cancelar-eliminar');
        const modalMateriaInfo = document.getElementById('modal-materia-info');
        let materiaId = null;

        // Abrir la ventana emergente al hacer clic en "Eliminar"
        document.addEventListener('click', (event) => {
            if (event.target.classList.contains('btn-eliminar')) {
                event.preventDefault();
                materiaId = event.target.getAttribute('data-id');
                const materiaNombre = event.target.getAttribute('data-materia');
                modalMateriaInfo.textContent = `Materia: ${materiaNombre}`;
                modal.style.display = 'flex';
            }
        });

        // Confirmar eliminación
        btnConfirmar.addEventListener('click', () => {
            if (materiaId) {
                window.location.href = `../API/Subject/DeleteSubject.php?id=${materiaId}`;
            }
        });

        // Cancelar eliminación
        btnCancelar.addEventListener('click', () => {
            modal.style.display = 'none';
            materiaId = null;
        });

        // Cerrar el modal al hacer clic fuera de él
        window.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.style.display = 'none';
                materiaId = null;
            }
        });
    });

    // Cargar materias
    const materiasContainer = document.getElementById('materias-container');
    fetch('../API/Subject/GetAdminSubjects.php')
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
                                <th>Usuarios Asignados</th>
                                <th>Total Usuarios</th>
                                <th>Fecha de Creación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                data.forEach(materia => {
                    // Determinar el estilo para los usuarios asignados
                    const usuariosStyle = materia.usuarios_asignados === 'Sin asignar' ? 
                        'style="color: #888; font-style: italic;"' : '';
                    
                    table += `
                        <tr>
                            <td>${materia.materia}</td>
                            <td>${materia.horas_teoricas}</td>
                            <td>${materia.horas_practicas}</td>
                            <td ${usuariosStyle}>${materia.usuarios_asignados}</td>
                            <td>${materia.total_usuarios}</td>
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