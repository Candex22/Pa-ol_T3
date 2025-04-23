<?php
session_start();
require_once('check_session.php');

$nickname = $_SESSION["correo_electronico"];
$mensaje = "Usted está iniciado como: $nickname";
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