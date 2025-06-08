<?php
session_start();
if(!isset($_SESSION["user_id"]) || $_SESSION["user_type"] != "Admin") {
    header("Location: Login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Usuarios</title>
    <link rel="stylesheet" href="../CSS/ViewUsers.css">
    <link rel="stylesheet" href="../CSS/Global.css">
    <link rel="stylesheet" href="../CSS/VentanaEmergente.css">
    <style>
        .actions-column {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        
        .btn-edit, .btn-delete {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            min-width: 60px;
        }
        
        .btn-edit {
            background-color: #007bff;
            color: white;
        }
        
        .btn-edit:hover {
            background-color: #0056b3;
        }
        
        .btn-delete {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background-color: #c82333;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 90%;
        }
        
        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
        .btn-confirm, .btn-cancel {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-confirm {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-cancel {
            background-color: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <?php include '../Common/Header.html'; ?>
    <div class="container">
        <h1>Gestión de Usuarios</h1>
        <div id="loading" class="loading">Cargando usuarios...</div>
        <div id="error" class="error" style="display: none;"></div>
        <div id="users-container" style="display: none;">
            <table id="users-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Usuario</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="users-tbody">
                </tbody>
            </table>
        </div>
        <div id="no-users" class="no-users" style="display: none;">
            No se encontraron usuarios registrados.
        </div>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div id="modal-eliminar" class="modal" role="dialog" aria-labelledby="modal-title" aria-modal="true">
        <div class="modal-content">
            <h3 id="modal-title">¿Estás seguro?</h3>
            <p id="modal-message">¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.</p>
            <div class="modal-buttons">
                <button id="btn-confirmar-eliminar" class="btn-confirm">Eliminar</button>
                <button id="btn-cancelar-eliminar" class="btn-cancel">Cancelar</button>
            </div>
        </div>
    </div>

    <script>
        let usuarioIdAEliminar = null;

        async function loadUsers() {
            try {
                const response = await fetch('../API/Users/GetUsers.php');
                const data = await response.json();
                
                document.getElementById('loading').style.display = 'none';
                
                if (data.success && data.data.length > 0) {
                    displayUsers(data.data);
                } else {
                    document.getElementById('no-users').style.display = 'block';
                }
            } catch (error) {
                document.getElementById('loading').style.display = 'none';
                document.getElementById('error').style.display = 'block';
                document.getElementById('error').textContent = 'Error al cargar usuarios: ' + error.message;
            }
        }

        function displayUsers(users) {
            const tbody = document.getElementById('users-tbody');
            tbody.innerHTML = '';
            
            users.forEach(user => {
                const row = document.createElement('tr');
                
                // Determinar clase CSS para el tipo de usuario
                const userTypeClass = user.tipo && user.tipo.toLowerCase() === 'admin' ? 'admin' : 'user';
                const estadoClass = user.activo ? 'activo' : 'inactivo';
                const estadoTexto = user.activo ? 'Activo' : 'Inactivo';
                
                // Determinar si se puede eliminar
                const canDelete = user.tipo !== 'Admin' && user.usuario_id != <?php echo $_SESSION['user_id']; ?>;
                const deleteButton = canDelete ? 
                    `<button class="btn-delete" onclick="confirmarEliminarUsuario(${user.usuario_id}, '${user.nombre} ${user.apellido_paterno}')">
                        Eliminar
                    </button>` :
                    `<button class="btn-delete" disabled style="opacity: 0.5; cursor: not-allowed;" title="No se puede eliminar administradores">
                        Eliminar
                    </button>`;
                
                row.innerHTML = `
                    <td>${user.nombre || 'N/A'}</td>
                    <td>${user.apellido_paterno || 'N/A'}</td>
                    <td>${user.usuario || 'N/A'}</td>
                    <td><span class="user-type ${userTypeClass}">${user.tipo || 'Usuario'}</span></td>
                    <td><span class="status ${estadoClass}">${estadoTexto}</span></td>
                    <td>
                        <div class="actions-column">
                            <button class="btn-edit" onclick="editarUsuario(${user.usuario_id})">
                                Editar
                            </button>
                            ${deleteButton}
                        </div>
                    </td>
                `;
                
                tbody.appendChild(row);
            });
            
            document.getElementById('users-container').style.display = 'block';
        }

        function editarUsuario(usuarioId) {
            // Redirigir a página de edición (puedes crear EditUser.php)
            window.location.href = `EditUser.php?id=${usuarioId}`;
        }

        function confirmarEliminarUsuario(usuarioId, nombreCompleto) {
            usuarioIdAEliminar = usuarioId;
            document.getElementById('modal-message').textContent = 
                `¿Estás seguro de que deseas eliminar al usuario "${nombreCompleto}"? Esta acción no se puede deshacer.`;
            document.getElementById('modal-eliminar').style.display = 'flex';
        }

        async function eliminarUsuario() {
            if (!usuarioIdAEliminar) return;

            try {
                const response = await fetch('../API/Users/DeleteUser.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        usuario_id: usuarioIdAEliminar
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert('Usuario eliminado correctamente');
                    loadUsers(); // Recargar la lista
                } else {
                    alert('Error al eliminar usuario: ' + data.message);
                }
            } catch (error) {
                alert('Error al eliminar usuario: ' + error.message);
            }

            // Cerrar modal y resetear
            document.getElementById('modal-eliminar').style.display = 'none';
            usuarioIdAEliminar = null;
        }

        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            });
        }

        // Event listeners para el modal
        document.getElementById('btn-confirmar-eliminar').addEventListener('click', eliminarUsuario);
        document.getElementById('btn-cancelar-eliminar').addEventListener('click', () => {
            document.getElementById('modal-eliminar').style.display = 'none';
            usuarioIdAEliminar = null;
        });

        // Cerrar modal al hacer clic fuera
        window.addEventListener('click', (event) => {
            const modal = document.getElementById('modal-eliminar');
            if (event.target === modal) {
                modal.style.display = 'none';
                usuarioIdAEliminar = null;
            }
        });

        // Cargar usuarios al cargar la página
        document.addEventListener('DOMContentLoaded', loadUsers);
    </script>
</body>
</html>