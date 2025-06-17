<?php
session_start();
require_once('admin_check.php');
require_once('tool_management.php');

$conn = connectDB();

// Verificar si se ha enviado una acción
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    $action = $_POST['action'];
    
    if ($action === 'aprobar') {
        // Aprobar usuario
        $stmt = $conn->prepare("UPDATE usuario SET estado = 'activo' WHERE id_user = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        
        $_SESSION['admin_message'] = "Usuario aprobado correctamente.";
        $_SESSION['admin_message_type'] = "success";
    } 
    elseif ($action === 'rechazar') {
        // Rechazar usuario (eliminarlo)
        $stmt = $conn->prepare("DELETE FROM usuario WHERE id_user = ? AND estado = 'pendiente'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        
        $_SESSION['admin_message'] = "Usuario rechazado correctamente.";
        $_SESSION['admin_message_type'] = "success";
    }
    
    // Redireccionar para evitar reenvío del formulario
    header("Location: admin_pending.php");
    exit();
}

// Obtener los usuarios pendientes
$stmt = $conn->prepare("SELECT id_user, name_user, nombre, apellido, correo_electronico FROM usuario WHERE estado = 'pendiente' ORDER BY id_user DESC");
$stmt->execute();
$result = $stmt->get_result();
$usuarios_pendientes = [];

while ($row = $result->fetch_assoc()) {
    $usuarios_pendientes[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios Pendientes - Administración</title>
    <link rel="stylesheet" href="../styles/register.css">
    <link rel="stylesheet" href="../styles/admin_pend.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="admin-container">
        <div class="header-section">
            <h1 class="admin-title">Usuarios Pendientes de Aprobación</h1>
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
        
        <?php if (count($usuarios_pendientes) > 0): ?>
        <p>Los siguientes usuarios han solicitado registro y están pendientes de aprobación:</p>
        
        <table class="users-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>Usuario</th>
                    <th>Correo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios_pendientes as $usuario): ?>
                <tr>
                    <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($usuario['apellido']); ?></td>
                    <td><?php echo htmlspecialchars($usuario['name_user']); ?></td>
                    <td><?php echo htmlspecialchars($usuario['correo_electronico']); ?></td>
                    <td>
                        <form method="POST" action="admin_pending.php" style="display: inline-block;">
                            <input type="hidden" name="user_id" value="<?php echo $usuario['id_user']; ?>">
                            <input type="hidden" name="action" value="aprobar">
                            <button type="submit" class="btn-approve" onclick="return confirm('¿Está seguro de aprobar a este usuario?')">
                                <i class="fas fa-check"></i> Aprobar
                            </button>
                        </form>
                        
                        <form method="POST" action="admin_pending.php" style="display: inline-block;">
                            <input type="hidden" name="user_id" value="<?php echo $usuario['id_user']; ?>">
                            <input type="hidden" name="action" value="rechazar">
                            <button type="submit" class="btn-reject" onclick="return confirm('¿Está seguro de rechazar a este usuario? Esta acción no se puede deshacer.')">
                                <i class="fas fa-times"></i> Rechazar
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-check-circle"></i>
            <h3>No hay usuarios pendientes</h3>
            <p>En este momento no hay solicitudes de registro pendientes de aprobación.</p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>