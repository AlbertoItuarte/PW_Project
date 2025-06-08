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
                        <th>Tipo</th>
                    </tr>
                </thead>
                <tbody id="users-tbody">
                </tbody>
            </table>
        </div>
        <div id="no-users" class="no-users" style="display: none;">
            No se encontraron usuarios registrados.
        </div>
        <!-- Botón para registrar usuarios -->
        <div style="text-align:center; margin-top: 30px;">
            <a href="Register.php" class="back-button">Registrar usuarios</a>
        </div>
    </div>

    <script>
        async function loadUsers() {
            try {
                const response = await fetch('../API/Users/getusers.php');
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
                
                row.innerHTML = `
                    <td>${user.nombre || 'N/A'}</td>
                    <td>${user.apellido_paterno || 'N/A'}</td>
                    <td><span class="user-type ${userTypeClass}">${user.tipo || 'Usuario'}</span></td>
                `;
                
                tbody.appendChild(row);
            });
            
            document.getElementById('users-container').style.display = 'block';
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

        // Cargar usuarios al cargar la página
        document.addEventListener('DOMContentLoaded', loadUsers);
    </script>
</body>
</html>