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

// Verificar si se proporcionó el ID del usuario
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ViewUsers.php");
    exit();
}

$usuario_id = $_GET['id'];

// Conectar a la base de datos
require_once '../Config/dbConection.php';

// Obtener información del usuario
$sql = "SELECT usuario_id, nombre, apellido_paterno, usuario, tipo, activo FROM usuario WHERE usuario_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header("Location: ViewUsers.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario - <?php echo htmlspecialchars($usuario['nombre']); ?></title>
    <link rel="stylesheet" href="../CSS/Global.css">
    <link rel="stylesheet" href="../CSS/Home.css">
    <link rel="stylesheet" href="../CSS/VentanaEmergente.css">
    <style>
        .edit-user-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin: 0;
        }
        
        .form-buttons {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
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
        
        .alert {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
            display: none;
        }
        
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .admin-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .password-section {
            border-top: 1px solid #ddd;
            padding-top: 20px;
            margin-top: 20px;
        }
        
        .password-section h3 {
            margin-bottom: 15px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="edit-user-container">
        <a href="ViewUsers.php" class="back-link">← Volver a la lista de usuarios</a>
        
        <div class="form-container">
            <h1>Editar Usuario</h1>
            
            <?php if ($usuario['tipo'] === 'Admin'): ?>
            <div class="admin-warning">
                <strong>¡Atención!</strong> Estás editando un usuario administrador. Ten cuidado al modificar estos datos.
            </div>
            <?php endif; ?>
            
            <div id="alert-container"></div>
            
            <form id="edit-user-form">
                <input type="hidden" id="usuario-id" value="<?php echo $usuario['usuario_id']; ?>">
                
                <div class="form-group">
                    <label for="nombre">Nombre *</label>
                    <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="apellido">Apellido Paterno *</label>
                    <input type="text" id="apellido" name="apellido" value="<?php echo htmlspecialchars($usuario['apellido_paterno']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="usuario">Usuario *</label>
                    <input type="text" id="usuario" name="usuario" value="<?php echo htmlspecialchars($usuario['usuario']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="tipo">Tipo de Usuario</label>
                    <select id="tipo" name="tipo" <?php echo ($usuario['usuario_id'] == $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                        <option value="Usuario" <?php echo ($usuario['tipo'] === 'Usuario') ? 'selected' : ''; ?>>Usuario</option>
                        <option value="Admin" <?php echo ($usuario['tipo'] === 'Admin') ? 'selected' : ''; ?>>Administrador</option>
                    </select>
                    <?php if ($usuario['usuario_id'] == $_SESSION['user_id']): ?>
                    <small style="color: #666; font-size: 12px;">No puedes cambiar tu propio tipo de usuario</small>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="activo" name="activo" <?php echo $usuario['activo'] ? 'checked' : ''; ?> 
                               <?php echo ($usuario['usuario_id'] == $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                        <label for="activo">Usuario Activo</label>
                    </div>
                    <?php if ($usuario['usuario_id'] == $_SESSION['user_id']): ?>
                    <small style="color: #666; font-size: 12px;">No puedes desactivar tu propio usuario</small>
                    <?php endif; ?>
                </div>
                
                <div class="password-section">
                    <h3>Cambiar Contraseña (Opcional)</h3>
                    
                    <div class="form-group">
                        <label for="nueva-password">Nueva Contraseña</label>
                        <input type="password" id="nueva-password" name="nueva-password" placeholder="Dejar en blanco para mantener la actual">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmar-password">Confirmar Nueva Contraseña</label>
                        <input type="password" id="confirmar-password" name="confirmar-password" placeholder="Confirmar nueva contraseña">
                    </div>
                </div>
                
                <div class="form-buttons">
                    <a href="ViewUsers.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('edit-user-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const nuevaPassword = document.getElementById('nueva-password').value;
            const confirmarPassword = document.getElementById('confirmar-password').value;
            
            // Validar contraseñas si se ingresaron
            if (nuevaPassword || confirmarPassword) {
                if (nuevaPassword !== confirmarPassword) {
                    showAlert('Las contraseñas no coinciden', 'error');
                    return;
                }
                
                if (nuevaPassword.length < 6) {
                    showAlert('La contraseña debe tener al menos 6 caracteres', 'error');
                    return;
                }
            }
            
            // Preparar datos para enviar
            const data = {
                usuario_id: document.getElementById('usuario-id').value,
                nombre: document.getElementById('nombre').value,
                apellido: document.getElementById('apellido').value,
                usuario: document.getElementById('usuario').value,
                tipo: document.getElementById('tipo').value,
                activo: document.getElementById('activo').checked
            };
            
            // Agregar contraseña solo si se ingresó una nueva
            if (nuevaPassword) {
                data.nueva_password = nuevaPassword;
            }
            
            try {
                const response = await fetch('../API/Users/UpdateUser.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Usuario actualizado correctamente', 'success');
                    
                    // Si se cambió el usuario actual, redirigir al login
                    if (data.usuario_id == '<?php echo $_SESSION['user_id']; ?>' && (!data.activo || data.tipo !== 'Admin')) {
                        setTimeout(() => {
                            window.location.href = 'Login.php';
                        }, 2000);
                    }
                } else {
                    showAlert('Error al actualizar usuario: ' + result.message, 'error');
                }
            } catch (error) {
                showAlert('Error de conexión: ' + error.message, 'error');
            }
        });
        
        function showAlert(message, type) {
            const container = document.getElementById('alert-container');
            container.innerHTML = `<div class="alert alert-${type}" style="display: block;">${message}</div>`;
            
            // Auto-ocultar después de 5 segundos para mensajes de éxito
            if (type === 'success') {
                setTimeout(() => {
                    container.innerHTML = '';
                }, 5000);
            }
        }
        
        // Validación en tiempo real de contraseñas
        document.getElementById('confirmar-password').addEventListener('input', function() {
            const nueva = document.getElementById('nueva-password').value;
            const confirmar = this.value;
            
            if (confirmar && nueva !== confirmar) {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = '#ddd';
            }
        });
    </script>
</body>
</html>