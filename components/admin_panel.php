<?php
session_start();
require_once('admin_check.php');

// Contar el número de usuarios pendientes
require_once('tool_management.php');
$conn = connectDB();

$stmt = $conn->prepare("SELECT COUNT(*) as total_pendientes FROM usuario WHERE estado = 'pendiente'");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_pendientes = $row['total_pendientes'];

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    <link rel="stylesheet" href="../styles/register.css">
    <link rel="stylesheet" href="../styles/panel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <div class="header-section">
            <h1 class="admin-title">Panel de Administración</h1>
            <a href="../index.php" class="btn-secondary"><i class="fas fa-home"></i> Volver al inicio</a>
        </div>
        
        <p>Bienvenido/a, <strong><?php echo htmlspecialchars($_SESSION['name_user']); ?></strong>. Desde aquí puede gestionar los usuarios del sistema.</p>
        
        <div class="admin-menu">
            <div class="admin-card">
                <i class="fas fa-user-clock"></i>
                <h3>Usuarios Pendientes <?php if($total_pendientes > 0): ?><span class="badge"><?php echo $total_pendientes; ?></span><?php endif; ?></h3>
                <p>Aprobar o rechazar solicitudes de registro pendientes.</p>
                <a href="admin_pending.php">Gestionar pendientes</a>
            </div>
            
            <div class="admin-card">
                <i class="fas fa-users"></i>
                <h3>Gestión de Usuarios</h3>
                <p>Ver, editar, activar o desactivar usuarios del sistema.</p>
                <a href="admin_users.php">Gestionar usuarios</a>
            </div>
            
            <div class="admin-card">
                <i class="fas fa-user-shield"></i>
                <h3>Asignar Administradores</h3>
                <p>Otorgar o revocar permisos de administrador a usuarios.</p>
                <a href="admin_roles.php">Gestionar roles</a>
            </div>
            
            <div class="admin-card">
                <i class="fas fa-user-circle"></i>
                <h3>Mi Perfil</h3>
                <p>Ver y editar la información de su perfil.</p>
                <a href="perfil.php">Ver perfil</a>
            </div>
        </div>
    </div>
</body>
</html>