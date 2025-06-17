<?php
session_start();
require_once('admin_check.php');
require_once('tool_management.php');

$conn = connectDB();

// Verificar si se ha enviado una acción
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    $action = $_POST['action'];
    $id_usuario_actual = $_SESSION['id_usuario']; // ID del administrador actual
    
    // Evitar que el administrador se quite permisos a sí mismo
    if ($user_id == $id_usuario_actual && $action === 'quitar_admin') {
        $_SESSION['admin_message'] = "No puede quitarse permisos de administrador a usted mismo.";
        $_SESSION['admin_message_type'] = "error";
    } else {
        if ($action === 'dar_admin') {
            // Dar permisos de administrador
            $stmt = $conn->prepare("UPDATE usuario SET rol = 'administrador' WHERE id_user = ? AND estado = 'activo'");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            
            $_SESSION['admin_message'] = "Permisos de administrador otorgados correctamente.";
            $_SESSION['admin_message_type'] = "success";
        } 
        elseif ($action === 'quitar_admin') {
            // Quitar permisos de administrador
            $stmt = $conn->prepare("UPDATE usuario SET rol = 'usuario' WHERE id_user = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            
            $_SESSION['admin_message'] = "Permisos de administrador revocados correctamente.";
            $_SESSION['admin_message_type'] = "success";
        }
    }
    
    // Redireccionar para evitar reenvío del formulario
    header("Location: admin_roles.php");
    exit();
}

// Obtener todos los usuarios activos (excepto el actual)
$id_usuario_actual = $_SESSION['id_usuario'];
$stmt = $conn->prepare("SELECT id_user, name_user, nombre, apellido, correo_electronico, rol FROM usuario WHERE id_user != ? AND estado = 'activo' ORDER BY rol DESC, nombre");
$stmt->bind_param("i", $id_usuario_actual);
$stmt->execute();
$result = $stmt->get_result();
$usuarios = [];

while ($row = $result->fetch_assoc()) {
    $usuarios[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Roles - Administración</title>
    <link rel="stylesheet" href="../styles/register.css">
    <link rel="stylesheet" href="../styles/admin_users.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="admin-container">
        <div class="header-section">
            <h1 class="admin-title">Gestión de Roles de Administrador</h1>
            <a href="admin_panel.php" class="btn-secondary"><i class="fas fa-arrow-left"></i> Volver al panel</a>
        </div>
        
        <?php if (isset($_SESSION['admin_message'])): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: '<?php echo $_SESSION['admin_message_type']; ?>',
                        title: 'Notificación',
                        text: '<?php echo $_SESSION['admin_message']; ?>',
                        confirmButtonColor: '<?php echo $_SESSION['admin_message_type'] === "success" ? "#28a745" : "#dc3545"; ?>',
                        confirmButtonText: 'Aceptar'
                    });
                });
            </script>
            <?php 
            unset($_SESSION['admin_message']);
            unset($_SESSION['admin_message_type']);
            ?>
        <?php endif; ?>
        
        <?php if (count($usuarios) > 0): ?>
        <p>Gestione los permisos de administrador para los usuarios activos del sistema:</p>
        
        <div class="alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>Importante:</strong> Solo los usuarios con estado "Activo" pueden ser promovidos a administradores. Los administradores pueden gestionar usuarios, aprobar registros y asignar otros administradores.
        </div>
        
        <table class="users-table">
            <thead>
                <tr>
                    <th>Nombre Completo</th>
                    <th>Usuario</th>
                    <th>Correo</th>
                    <th>Rol Actual</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                <tr>
                    <td><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></td>
                    <td><?php echo htmlspecialchars($usuario['name_user']); ?></td>
                    <td><?php echo htmlspecialchars($usuario['correo_electronico']); ?></td>
                    <td>
                        <?php if ($usuario['rol'] === 'administrador'): ?>
                            <span class="badge badge-admin"><i class="fas fa-user-shield"></i> Administrador</span>
                        <?php else: ?>
                            <span class="badge badge-user"><i class="fas fa-user"></i> Usuario</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($usuario['rol'] === 'usuario'): ?>
                            <form method="POST" action="admin_roles.php" style="display: inline-block;">
                                <input type="hidden" name="user_id" value="<?php echo $usuario['id_user']; ?>">
                                <input type="hidden" name="action" value="dar_admin">
                                <button type="submit" class="btn-promote" onclick="return confirm('¿Está seguro de otorgar permisos de administrador a este usuario?')">
                                    <i class="fas fa-user-shield"></i> Hacer Administrador
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST" action="admin_roles.php" style="display: inline-block;">
                                <input type="hidden" name="user_id" value="<?php echo $usuario['id_user']; ?>">
                                <input type="hidden" name="action" value="quitar_admin">
                                <button type="submit" class="btn-demote" onclick="return confirm('¿Está seguro de revocar los permisos de administrador a este usuario?')">
                                    <i class="fas fa-user-times"></i> Quitar Admin
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-users-cog"></i>
            <h3>No hay usuarios disponibles</h3>
            <p>En este momento no hay otros usuarios activos para gestionar roles.</p>
        </div>
        <?php endif; ?>
    </div>

    <style>
        .alert-info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-info i {
            margin-right: 8px;
        }
        
        .btn-promote {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
        }
        
        .btn-promote:hover {
            background-color: #218838;
        }
        
        .btn-demote {
            background-color: #ffc107;
            color: #212529;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
        }
        
        .btn-demote:hover {
            background-color: #e0a800;
        }
        
        .badge-admin {
            background-color: #dc3545;
            color: white;
        }
        
        .badge-user {
            background-color: #6c757d;
            color: white;
        }
    </style>
</body>
</html>