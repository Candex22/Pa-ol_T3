<?php
session_start(); // Iniciar la sesión al comienzo del archivo

// Verificar si el usuario ya está registrado
if (isset($_SESSION['usuario_registrado']) && $_SESSION['usuario_registrado'] === true) {
    header("Location: ../index.php"); // Redirigir al index si ya está registrado
    exit();
}

require_once('tool_management.php');

$conn = connectDB();

// Verificar si el formulario fue enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = isset($_POST["nombre"]) ? $_POST["nombre"] : "";
    $apellido = isset($_POST["apellido"]) ? $_POST["apellido"] : "";
    $name_user = isset($_POST["nickname"]) ? $_POST["nickname"] : "";
    $correo = isset($_POST["correo_electronico"]) ? $_POST["correo_electronico"] : "";
    $contrasena = isset($_POST["contrasena"]) ? $_POST["contrasena"] : "";

    // Validar campos vacíos
    if (empty($nombre) || empty($apellido) || empty($name_user) || empty($correo) || empty($contrasena)) {
        die("Error: Todos los campos son obligatorios.");
    }

    // Validar formato de correo
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        die("Error: Formato de correo electrónico no válido.");
    }

    // Validar longitud de contraseña
    if (strlen($contrasena) <= 4) {
        die("Error: La contraseña debe tener más de 4 caracteres.");
    }

    // Prepare statement para evitar inyección SQL
    $stmt = $conn->prepare("INSERT INTO usuario (name_user, nombre, apellido, correo_electronico, contrasena) VALUES (?, ?, ?, ?, ?)");

    // Verifica si la consulta es válida
    if (!$stmt) {
        die("Error en la preparación: " . $conn->error);
    }

    // Bind de parámetros (todos son strings)
    $stmt->bind_param("sssss", $name_user, $nombre, $apellido, $correo, $contrasena);

    if ($stmt->execute()) {
        $_SESSION["usuario_registrado"] = true; // Variable de sesión para indicar éxito
        header("Location: ../index.php"); // Redirigir al index después del registro
        exit();
    }

    // Cerrar la conexión
    $stmt->close();
    $conn->close();
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Pañol - Registro</title>
    <link rel="stylesheet" href="../styles/register.css">

</head>
<body>

    <div class="container">
        <div class="form-container">
            <h2 class="form-title">Registro de Usuario</h2>
            
            <form action="register.php" method="POST">
                <div class="form-group">
                    <label class="form-label">Nombre</label>
                    <input type="text" class="form-control" placeholder="Ingrese su nombre completo" name="nombre">
                </div>
                <div class="form-group">
                    <label class="form-label">Apellido</label>
                    <input type="text" class="form-control" placeholder="Ingrese su apellido completo" name="apellido">
                </div>
                <div class="form-group">
                    <label class="form-label">Nombre de usuario</label>
                    <input type="text" class="form-control" placeholder="Elija un nombre de usuario" name="nickname">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Correo electrónico</label>
                    <input type="email" class="form-control" placeholder="Ingrese su correo electrónico" name="correo_electronico">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Contraseña</label>
                    <input type="password" class="form-control" placeholder="Cree una contraseña" name="contrasena">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirme contraseña</label>
                    <input type="password" class="form-control" placeholder="Confirme su contraseña" name="confir_contrasena">
                </div>
                
                <div class="form-actions">
                <button type="submit" class="btn btn-primary">Registrarse</button>
                <button type="button" class="btn btn-warning">Cancelar</button>
                    
                </div>
                
                <div class="form-footer">
                    ¿Ya tiene una cuenta? <a href="./login.php">Iniciar sesión</a>
                </div>
                <?php
                if (isset($_SESSION["registro_exitoso"]) && $_SESSION["registro_exitoso"] == true) {
                    echo '<div class="registro-exitoso">';

                    echo '</div>';
                    unset($_SESSION["registro_exitoso"]); // Eliminar la variable para que no se muestre siempre
                }
    ?>

            </form>
        </div>
    </div>
</body>
</html>