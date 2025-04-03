<?php
session_start(); // Iniciar sesión

// Verificar si el usuario ha iniciado sesión
if (isset($_SESSION["correo_electronico"]) && isset($_SESSION["id_usuario"])) {
    $nickname = $_SESSION["correo_electronico"]; // Puedes cambiar esto si tienes un campo específico para el nickname
    $mensaje = "Usted está iniciado como: $nickname";
} else {
    $mensaje = "Usted no tiene sesión";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<link rel="stylesheet" href="../styles/perfil.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400&display=swap">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil</title>

</head>
<body>
    <div class="perfil-container">
        <p class="perfil-mensaje"><?php echo $mensaje; ?></p>
    </div>
</body>
</html>