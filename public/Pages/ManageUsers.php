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

// Verificar si se proporcionó el ID de la materia
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: Home.php");
    exit();
}

$materia_id = $_GET['id'];

// Conectar a la base de datos
require_once '../Config/dbConection.php';

// Obtener información de la materia
$sql = "SELECT nombre FROM materia WHERE materia_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$materia_id]);
$materia = $stmt->fetch();

if (!$materia) {
    header("Location: Home.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Usuarios - <?php echo htmlspecialchars($materia['nombre']); ?></title>
    <link rel="stylesheet" href="../CSS/Global.css">
    <link rel="stylesheet" href="../CSS/Home.css">
    <link rel="stylesheet" href="../CSS/VentanaEmergente.css">
    <style>
        .manage-users-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .section {
            background: white;
            margin: 20px 0;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .section h3 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        
        .user-form {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        
        .user-form select, .user-form button {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .user-form button {
            background: #007bff;
            color: white;
            cursor: pointer;
            border: none;
        }
        
        .user-form button:hover {
            background: #0056b3;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .users-table th, .users-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .users-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .btn-remove {
            background: #dc3545;
            color: white;
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            border: none;
            cursor: pointer;
        }
        
        .btn-remove:hover {
            background: #c82333;
        }
        
        .no-users {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 20px;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div>
        <nav>
            <ul>
                <li><a href="Home.php">Inicio</a></li>
                <li><a href="PlanSubject.php">Crear materia</a></li>
                <li><a href="CreateCycle.php">Crear Ciclo</a></li>
                <li><a href="NewHoliday.php">Feriados +</a></li>
                <li><a href="Register.php">Registrar usuarios</a></li>
                <li><a href="ViewUsers.php">Ver usuarios</a></li>
                <li><a href="../Logic/LogOut.php">Cerrar sesión</a></li>
            </ul>
        </nav>
    </div>

    <div class="manage-users-container">
        <a href="Home.php" class="back-link">← Volver al inicio</a>
        
        <h1>Gestionar Usuarios</h1>
        <h2>Materia: <?php echo htmlspecialchars($materia['nombre']); ?></h2>

        <!-- Sección para asignar usuarios -->
        <div class="section">
            <h3>Asignar Usuario</h3>
            <div class="user-form">
                <select id="usuario-select">
                    <option value="">Seleccionar usuario...</option>
                </select>
                <button onclick="asignarUsuario()">Asignar Usuario</button>
            </div>
        </div>

        <!-- Sección de usuarios asignados -->
        <div class="section">
            <h3>Usuarios Asignados</h3>
            <div id="usuarios-asignados">
                <p>Cargando usuarios asignados...</p>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación -->
    <div id="modal-confirmar" class="modal" role="dialog" aria-labelledby="modal-title" aria-modal="true">
        <div class="modal-content">
            <h3 id="modal-title">Confirmar acción</h3>
            <p id="modal-message"></p>
            <div class="modal-buttons">
                <button id="btn-confirmar" class="btn-confirm">Confirmar</button>
                <button id="btn-cancelar" class="btn-cancel">Cancelar</button>
            </div>
        </div>
    </div>

    <script>
        const materiaId = <?php echo $materia_id; ?>;
        
        document.addEventListener('DOMContentLoaded', () => {
            cargarUsuariosDisponibles();
            cargarUsuariosAsignados();
        });

        // Cargar usuarios disponibles para asignar
        function cargarUsuariosDisponibles() {
            fetch(`../API/Users/GetAvailableUsers.php?materia_id=${materiaId}`)
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('usuario-select');
                    select.innerHTML = '<option value="">Seleccionar usuario...</option>';
                    
                    data.forEach(usuario => {
                        const option = document.createElement('option');
                        option.value = usuario.id;
                        option.textContent = `${usuario.nombre} ${usuario.apellido} (${usuario.email})`;
                        select.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error al cargar usuarios disponibles:', error);
                });
        }

        // Cargar usuarios asignados
        function cargarUsuariosAsignados() {
            fetch(`../API/Users/GetAssignedUsers.php?materia_id=${materiaId}`)
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('usuarios-asignados');
                    
                    if (data.length > 0) {
                        let table = `
                            <table class="users-table">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Apellido</th>
                                        <th>Usuario</th>
                                        <th>Ciclo</th>
                                        <th>Fecha de Asignación</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                        `;
                        
                        data.forEach(usuario => {
                            table += `
                                <tr>
                                    <td>${usuario.nombre}</td>
                                    <td>${usuario.apellido}</td>
                                    <td>${usuario.email}</td>
                                    <td>${usuario.ciclo_nombre}</td>
                                    <td>${usuario.fecha_asignacion}</td>
                                    <td>
                                        <button class="btn-remove" onclick="confirmarDesasignar(${usuario.id}, '${usuario.nombre} ${usuario.apellido}')">
                                            Desasignar
                                        </button>
                                    </td>
                                </tr>
                            `;
                        });
                        
                        table += '</tbody></table>';
                        container.innerHTML = table;
                    } else {
                        container.innerHTML = '<div class="no-users">No hay usuarios asignados a esta materia.</div>';
                    }
                })
                .catch(error => {
                    console.error('Error al cargar usuarios asignados:', error);
                    document.getElementById('usuarios-asignados').innerHTML = 
                        '<div class="error">Error al cargar los usuarios asignados.</div>';
                });
        }

        // Asignar usuario
        function asignarUsuario() {
            const select = document.getElementById('usuario-select');
            const usuarioId = select.value;
            
            if (!usuarioId) {
                alert('Por favor, selecciona un usuario.');
                return;
            }
            
            fetch('../API/Users/AssignUserToSubject.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    usuario_id: usuarioId,
                    materia_id: materiaId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Usuario asignado correctamente.');
                    cargarUsuariosDisponibles();
                    cargarUsuariosAsignados();
                } else {
                    alert('Error al asignar usuario: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al asignar usuario.');
            });
        }

        // Confirmar desasignación
        function confirmarDesasignar(usuarioId, nombreUsuario) {
            const modal = document.getElementById('modal-confirmar');
            const message = document.getElementById('modal-message');
            
            message.textContent = `¿Estás seguro de que deseas desasignar a ${nombreUsuario} de esta materia?`;
            modal.style.display = 'flex';
            
            document.getElementById('btn-confirmar').onclick = () => {
                desasignarUsuario(usuarioId);
                modal.style.display = 'none';
            };
            
            document.getElementById('btn-cancelar').onclick = () => {
                modal.style.display = 'none';
            };
        }

        // Desasignar usuario
        function desasignarUsuario(usuarioId) {
            fetch('../API/Users/UnassignUserFromSubject.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    usuario_id: usuarioId,
                    materia_id: materiaId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Usuario desasignado correctamente.');
                    cargarUsuariosDisponibles();
                    cargarUsuariosAsignados();
                } else {
                    alert('Error al desasignar usuario: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al desasignar usuario.');
            });
        }

        // Cerrar modal al hacer clic fuera
        window.addEventListener('click', (event) => {
            const modal = document.getElementById('modal-confirmar');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    </script>
</body>
</html>