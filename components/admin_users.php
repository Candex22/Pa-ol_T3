<?php
session_start();
require_once('check_session.php');
require_once('admin_check.php');
require_once('tool_management.php');

$conn = connectDB();

// Verificar si se ha enviado una acción
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    $action = $_POST['action'];
    $id_usuario_actual = $_SESSION['id_usuario']; // ID del administrador actual
    
    // Evitar que el administrador se desactive a sí mismo
    if ($user_id == $id_usuario_actual && ($action === 'desactivar' || $action === 'eliminar')) {
        $_SESSION['admin_message'] = "No puede desactivar o eliminar su propia cuenta.";
        $_SESSION['admin_message_type'] = "error";
    } else {
        if ($action === 'activar') {
            // Activar usuario
            $stmt = $conn->prepare("UPDATE usuario SET estado = 'activo' WHERE id_user = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            
            $_SESSION['admin_message'] = "Usuario activado correctamente.";
            $_SESSION['admin_message_type'] = "success";
        } 
        elseif ($action === 'desactivar') {
            // Desactivar usuario
            $stmt = $conn->prepare("UPDATE usuario SET estado = 'inactivo' WHERE id_user = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            
            $_SESSION['admin_message'] = "Usuario desactivado correctamente.";
            $_SESSION['admin_message_type'] = "success";
        }
        elseif ($action === 'eliminar') {
            // Eliminar usuario
            $stmt = $conn->prepare("DELETE FROM usuario WHERE id_user = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            
            $_SESSION['admin_message'] = "Usuario eliminado correctamente.";
            $_SESSION['admin_message_type'] = "success";
        }
    }
    
    // Redireccionar para evitar reenvío del formulario
    header("Location: admin_users.php");
    exit();
}

// Obtener los usuarios (excepto el actual)
$id_usuario_actual = $_SESSION['id_usuario'];
$stmt = $conn->prepare("SELECT id_user, name_user, nombre, apellido, correo_electronico, rol, estado FROM usuario WHERE id_user != ? ORDER BY estado, id_user");
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
    <title>Gestión de Usuarios - Administración</title>
    <link rel="stylesheet" href="../styles/register.css">
    <link rel="stylesheet" href="../styles/admin_users.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="admin-container">
        <div class="header-section">
            <h1 class="admin-title">Gestión de Usuarios</h1>
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
        <p>Lista de usuarios registrados en el sistema:</p>
        
        <table class="users-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>Usuario</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                <tr>
                    <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($usuario['apellido']); ?></td>
                    <td><?php echo htmlspecialchars($usuario['name_user']); ?></td>
                    <td><?php echo htmlspecialchars($usuario['correo_electronico']); ?></td>
                    <td>
                        <?php if ($usuario['rol'] === 'administrador'): ?>
                            <span class="badge badge-admin">Administrador</span>
                        <?php else: ?>
                            <span class="badge badge-user">Usuario</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($usuario['estado'] === 'activo'): ?>
                            <span class="badge badge-active">Activo</span>
                        <?php elseif ($usuario['estado'] === 'inactivo'): ?>
                            <span class="badge badge-inactive">Inactivo</span>
                        <?php else: ?>
                            <span class="badge badge-pending">Pendiente</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($usuario['estado'] === 'inactivo' || $usuario['estado'] === 'pendiente'): ?>
                            <form method="POST" action="admin_users.php" style="display: inline-block;">
                                <input type="hidden" name="user_id" value="<?php echo $usuario['id_user']; ?>">
                                <input type="hidden" name="action" value="activar">
                                <button type="submit" class="btn-activate" onclick="return confirm('¿Está seguro de activar a este usuario?')">
                                    <i class="fas fa-check"></i> Activar
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if ($usuario['estado'] === 'activo'): ?>
                            <form method="POST" action="admin_users.php" style="display: inline-block;">
                                <input type="hidden" name="user_id" value="<?php echo $usuario['id_user']; ?>">
                                <input type="hidden" name="action" value="desactivar">
                                <button type="submit" class="btn-deactivate" onclick="return confirm('¿Está seguro de desactivar a este usuario?')">
                                    <i class="fas fa-pause"></i> Desactivar
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <form method="POST" action="admin_users.php" style="display: inline-block;">
                            <input type="hidden" name="user_id" value="<?php echo $usuario['id_user']; ?>">
                            <input type="hidden" name="action" value="eliminar">
                            <button type="submit" class="btn-delete" onclick="return confirm('¿Está seguro de eliminar a este usuario? Esta acción no se puede deshacer.')">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-users"></i>
            <h3>No hay otros usuarios</h3>
            <p>En este momento no hay otros usuarios registrados en el sistema.</p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>