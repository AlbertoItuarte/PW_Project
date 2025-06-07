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
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .loading {
            text-align: center;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .user-type {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .admin { background-color: #dc3545; color: white; }
        .user { background-color: #28a745; color: white; }
        .error {
            color: #dc3545;
            text-align: center;
            padding: 20px;
        }
        .no-users {
            text-align: center;
            color: #666;
            padding: 40px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gestión de Usuarios</h1>
        <div id="loading" class="loading">Cargando usuarios...</div>
        <div id="error" class="error" style="display: none;"></div>
        <div id="users-container" style="display: none;">
            <table id="users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Email</th>
                        <th>Tipo</th>
                        <th>Fecha Creación</th>
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
                    <td>${user.id}</td>
                    <td>${user.nombre || 'N/A'}</td>
                    <td>${user.apellido || 'N/A'}</td>
                    <td>${user.email}</td>
                    <td><span class="user-type ${userTypeClass}">${user.tipo || 'Usuario'}</span></td>
                    <td>${formatDate(user.fecha_creacion)}</td>
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