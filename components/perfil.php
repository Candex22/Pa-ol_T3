<?php
session_start();
require_once('check_session.php');
require_once('tool_management.php');

$conn = connectDB();
$correo = $_SESSION["correo_electronico"];
$usuario = null;
if ($stmt = $conn->prepare("SELECT nombre, apellido, correo_electronico, name_user FROM usuario WHERE correo_electronico = ? LIMIT 1")) {
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="stylesheet" href="../styles/perfil.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil</title>
</head>
<body>
    <div class="perfil-container">
        <a href="../index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al inicio
        </a>
        <br><br>
        <p class="perfil-mensaje">Usted está iniciado como: <b><?php echo htmlspecialchars($usuario['name_user']); ?></b></p>
        <?php if ($usuario): ?>
        <div class="perfil-extra">
            <h3>Información de usuario</h3><br>
            <p><b>Nombre:</b> <?php echo htmlspecialchars($usuario['nombre']); ?></p>
            <p><b>Apellido:</b> <?php echo htmlspecialchars($usuario['apellido']); ?></p>
            <p><b>Correo:</b> <?php echo htmlspecialchars($usuario['correo_electronico']); ?></p>
        </div>
        <?php else: ?>
        <div class="perfil-extra">
            <p>No se pudo cargar la información del usuario.</p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>