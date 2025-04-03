<?php
session_start(); // Iniciar sesión al comienzo del archivo

require_once('tool_management.php');

$conn = connectDB();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = isset($_POST["correo_electronico"]) ? $_POST["correo_electronico"] : "";
    $contrasena = isset($_POST["contrasena"]) ? $_POST["contrasena"] : "";

    // Validar campos vacíos
    if (empty($correo) || empty($contrasena)) {
        die("Error: Todos los campos son obligatorios.");
    }

    // Validar formato de correo
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        die("Error: Formato de correo electrónico no válido.");
    }

    // Consultar el usuario en la base de datos
    $stmt = $conn->prepare("SELECT id_user, contrasena FROM usuario WHERE correo_electronico = ?");
    if (!$stmt) {
        die("Error en la preparación: " . $conn->error);
    }

    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id_usuario, $stored_password);
        $stmt->fetch();

        // Verificar la contraseña ingresada con la almacenada
        if ($contrasena === $stored_password) {
            // Iniciar sesión
            $_SESSION["id_usuario"] = $id_usuario;
            $_SESSION["correo_electronico"] = $correo;
// Después de verificar la contraseña en login.php
$_SESSION["id_usuario"] = $id_usuario;
$_SESSION["name_user"] = $name_user; // Asegúrate de que $name_user contiene el nickname del usuario
$_SESSION["correo_electronico"] = $correo;
            // Redirigir al usuario a la página principal
            header("Location: ../index.php");
            exit();
        } else {
            die("Error: Contraseña incorrecta.");
        }
    } else {
        die("Error: No se encontró una cuenta con ese correo electrónico.");
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link type="text">
    <link rel="stylesheet" href="../styles/login.css">
    <title>Sistema de Pañol - Iniciar Sesión</title>

</head>
<body>
    <div class="login-container">
        <h2 class="login-title">Iniciar Sesión</h2>
        <form action="login.php" method="POST">
            <div class="form-group">
                <label class="form-label" for="correo_electronico">Correo Electrónico</label>
                <input type="email" class="form-control" id="correo_electronico" name="correo_electronico" placeholder="Ingrese su correo electrónico">
            </div>
            <div class="form-group">
                <label class="form-label" for="contrasena">Contraseña</label>
                <input type="password" class="form-control" id="contrasena" name="contrasena" placeholder="Ingrese su contraseña">
            </div>
            <button type="submit" class="btn">Iniciar Sesión</button>
        </form>
        <div class="form-footer">
            ¿No tiene una cuenta? <a href="register.php">Regístrese aquí</a>
        </div>
    </div>
</body>
</html>